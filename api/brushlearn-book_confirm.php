<?php
header('Content-Type: application/json');
define('API_REQUEST', true);
require_once __DIR__ . '/../config/config.php';
requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$pdo = getDbConnection();
$data = json_decode(file_get_contents('php://input'), true);

$bookingId = $data['booking_id'] ?? null;
$profileId = $currentProfileId;
$calBookingId = $data['cal_booking_id'] ?? null;
$bookingDate = $data['booking_date'] ?? null;
$contactId = $data['contact_id'] ?? null;
$companyId = $data['company_id'] ?? null;
$calNotes = $data['cal_notes'] ?? '';

if (!$bookingId || !$profileId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

try {
    // Update booking with Cal.com details and set to booked
    $stmt = $pdo->prepare('
        UPDATE ohc_booking 
        SET cal_booking_id = ?, booking_date = ?, status = ?, updated_at = NOW()
        WHERE id = ? AND profile_id = ?
    ');
    $stmt->execute([$calBookingId, $bookingDate, 'booked', $bookingId, $profileId]);
    
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Booking not found']);
        exit;
    }
    
    // Get HubSpot contact/company IDs from profile if not provided
    if (!$contactId || !$companyId) {
        $stmt = $pdo->prepare('SELECT id_hubspot_b2b_contact, id_hubspot_b2b_company FROM profile WHERE id = ?');
        $stmt->execute([$profileId]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($profile) {
            $contactId = $contactId ?: $profile['id_hubspot_b2b_contact'];
            $companyId = $companyId ?: $profile['id_hubspot_b2b_company'];
        }
    }
    
    // Create HubSpot meeting engagement if we have contact ID
    $meetingId = null;
    
    if ($contactId) {
        try {
            $token = $_ENV['hubspotTokenB2B'] ?? '';
            
            if (empty($token)) {
                throw new Exception('HubSpot token not configured');
            }
            
            // Fetch booking details including form answers, customer profile, and professional's info
            $stmt = $pdo->prepare('
                SELECT b.*, l.city, l.state, l.is_virtual, fr.answers, 
                       customer.first_name, customer.last_name,
                       professional.id_hubspot_b2b_contact as professional_hubspot_id,
                       professional.email as professional_email
                FROM ohc_booking b
                LEFT JOIN ohc_location l ON b.location_id = l.id
                LEFT JOIN form_response fr ON b.form_response_id = fr.id
                LEFT JOIN profile customer ON b.profile_id = customer.id
                LEFT JOIN ohc_profile op ON op.location_id = l.id
                LEFT JOIN profile professional ON op.profile_id = professional.id
                WHERE b.id = ?
            ');
            $stmt->execute([$bookingId]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$booking) {
                throw new Exception('Booking not found');
            }
            
            $profileName = trim(($booking['first_name'] ?? '') . ' ' . ($booking['last_name'] ?? ''));
            $professionalHubSpotId = $booking['professional_hubspot_id'] ?? null;
            $professionalEmail = $booking['professional_email'] ?? null;
            
            // Build Cal.com meeting URL
            $calMeetingUrl = $calBookingId ? "https://app.cal.com/booking/{$calBookingId}" : '';
            
            // Build meeting body with HTML formatting
            $meetingBodyParts = [];
            
            if ($booking['city'] && $booking['state']) {
                $locationType = $booking['is_virtual'] ? "Virtual" : "In-Person";
                $meetingBodyParts[] = "<strong>Location:</strong> {$booking['city']}, {$booking['state']} ({$locationType})";
            }
            
            if ($calBookingId) {
                $meetingBodyParts[] = "<strong>Booking ID:</strong> {$calBookingId}<br/>{$calMeetingUrl}";
            }
            
            if ($calNotes) {
                $meetingBodyParts[] = "<strong>Notes from Cal.com:</strong><br/>" . nl2br(htmlspecialchars($calNotes));
            }
            
            // Fetch form questions to get actual labels
            if ($booking['answers']) {
                $answers = json_decode($booking['answers'], true);
                if (!empty($answers)) {
                    // Get form ID from booking (virtual = 1, in-person = 2)
                    $formId = $booking['is_virtual'] ? 1 : 2;
                    $stmt = $pdo->prepare('SELECT questions FROM form WHERE id = ?');
                    $stmt->execute([$formId]);
                    $formData = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    $questionMap = [];
                    if ($formData && $formData['questions']) {
                        $questions = json_decode($formData['questions'], true) ?: [];
                        foreach ($questions as $q) {
                            $questionMap[$q['id']] = $q['label'];
                        }
                    }
                    
                    $formResponsesParts = [];
                    foreach ($answers as $questionId => $answer) {
                        $label = $questionMap[$questionId] ?? $questionId;
                        $formResponsesParts[] = "<strong>{$label}:</strong><br/> " . htmlspecialchars($answer);
                    }
                    
                    if (!empty($formResponsesParts)) {
                        $meetingBodyParts[] = "<br/><br/>" . implode("<br/><br/>", $formResponsesParts);
                    }
                }
            }
            
            $meetingBody = '<div style="" dir="auto" data-top-level="true"><p style="margin:0;">' . 
                          implode('</p><p style="margin:0;">', $meetingBodyParts) . 
                          '</p></div>';
            
            // Convert booking date to ISO 8601 format
            $timestamp = null;
            $endTime = null;
            if ($bookingDate) {
                $dateTime = new DateTime($bookingDate);
                $timestamp = $dateTime->format('Y-m-d\TH:i:s.v\Z');
                $dateTime->modify('+1 hour');
                $endTime = $dateTime->format('Y-m-d\TH:i:s.v\Z');
            }
            
            // Create meeting data
            $meetingData = [
                'properties' => [
                    'hs_timestamp' => $timestamp,
                    'hs_meeting_title' => "Brush & Learn ({$profileName})",
                    'hs_meeting_body' => $meetingBody,
                    'hs_meeting_start_time' => $timestamp,
                    'hs_meeting_end_time' => $endTime,
                    'hs_meeting_external_url' => $calMeetingUrl,
                    'hs_meeting_location' => $booking['is_virtual'] ? 'Remote' : ($booking['city'] . ', ' . $booking['state']),
                    'hubspot_owner_id' => $professionalHubSpotId,
                    'hs_meeting_outcome' => 'SCHEDULED',
                    'hs_internal_meeting_notes' => $professionalEmail ? "Organizer: {$professionalEmail}" : null
                ],
                'associations' => []
            ];
            
            // Add contact association
            $meetingData['associations'][] = [
                'to' => ['id' => $contactId],
                'types' => [[
                    'associationCategory' => 'HUBSPOT_DEFINED',
                    'associationTypeId' => 200
                ]]
            ];
            
            // Add company association if available
            if ($companyId) {
                $meetingData['associations'][] = [
                    'to' => ['id' => $companyId],
                    'types' => [[
                        'associationCategory' => 'HUBSPOT_DEFINED',
                        'associationTypeId' => 188
                    ]]
                ];
            }
            
            // Call HubSpot API
            $ch = curl_init('https://api.hubapi.com/crm/v3/objects/meetings');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $token,
                    'Content-Type: application/json'
                ],
                CURLOPT_POSTFIELDS => json_encode($meetingData)
            ]);
            
            $response = curl_exec($ch);
            $curlError = curl_error($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($curlError) {
                throw new Exception('cURL error: ' . $curlError);
            }
            
            if ($httpCode !== 201) {
                $errorDetails = json_decode($response, true);
                throw new Exception('HubSpot API error: ' . ($errorDetails['message'] ?? $response));
            }
            
            $result = json_decode($response, true);
            $meetingId = $result['id'] ?? null;
            
            if (!$meetingId) {
                throw new Exception('Meeting created but no ID returned');
            }
            
            // Update booking with HubSpot meeting ID
            $stmt = $pdo->prepare('UPDATE ohc_booking SET hubspot_meeting_id = ? WHERE id = ?');
            $stmt->execute([$meetingId, $bookingId]);
            
        } catch (Exception $e) {
            error_log("Failed to create HubSpot meeting: " . $e->getMessage());
        }
    }
    
    // TODO: Send confirmation email here
    // sendBookingConfirmationEmail($profileId, $bookingId);
    
    echo json_encode([
        'success' => true,
        'booking_id' => $bookingId,
        'status' => 'confirmed',
        'message' => 'Booking confirmed successfully'
    ]);
} catch (PDOException $e) {
    error_log('brushlearn-book_confirm error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'An internal error occurred']);
}
