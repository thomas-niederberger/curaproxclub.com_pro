<?php
require_once __DIR__ . '/partials/config.php';

// Handle AJAX request to update profile timestamp
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
	header('Content-Type: application/json');
	
	if (!isset($_SESSION['profile_id'])) {
		http_response_code(401);
		echo json_encode(['success' => false, 'error' => 'Unauthorized']);
		exit;
	}
	
	try {
		$pdo = getDbConnection();
		$stmt = $pdo->prepare('UPDATE profile SET profile_last = NOW() WHERE id = ?');
		$stmt->execute([$_SESSION['profile_id']]);
		
		echo json_encode([
			'success' => true,
			'message' => 'Profile timestamp updated',
			'profile_id' => $_SESSION['profile_id']
		]);
	} catch (Exception $e) {
		http_response_code(500);
		echo json_encode([
			'success' => false,
			'error' => 'Database error: ' . $e->getMessage()
		]);
	}
	exit;
}

function getHubSpotUrlParams($contactId, $formId) {
	$token = $_ENV['hubspotTokenB2B'] ?? '';
	if (empty($token) || empty($contactId) || empty($formId)) return "error=missing_data";

	// 1. Fetch Form Definition
	$ch = curl_init("https://api.hubapi.com/marketing/v3/forms/{$formId}");
	curl_setopt_array($ch, [
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token]
	]);
	$formRes = curl_exec($ch);
	$formDef = json_decode($formRes, true);
	curl_close($ch);

	if (!isset($formDef['fieldGroups'])) return "error=form_not_found";

	$contactFields = [];
	$companyFields = [];

	// 2. Sort fields into Contact vs Company buckets
	foreach ($formDef['fieldGroups'] as $group) {
		foreach ($group['fields'] as $field) {
			$name = $field['name'];
			if (str_starts_with($name, 'hs_') || str_starts_with($name, 'LEGAL_CONSENT')) continue;

			if (($field['objectTypeId'] ?? '') === '0-2') {
				$companyFields[] = $name;
			} else {
				$contactFields[] = $name;
			}
		}
	}

	// 3. Build Valid GraphQL Syntax
	$contactFieldsStr = implode("\n", array_unique($contactFields));
	$companyFieldsStr = !empty($companyFields) ? "associations { company_collection__primary { items { " . implode("\n", array_unique($companyFields)) . " } } }" : "";

	$query = "query GetContactData(\$id: String!) {
		CRM {
			contact(uniqueIdentifier: \"hs_object_id\", uniqueIdentifierValue: \$id) {
				{$contactFieldsStr}
				{$companyFieldsStr}
			}
		}
	}";

	// 4. Execute Query
	$ch = curl_init('https://api.hubapi.com/collector/graphql');
	curl_setopt_array($ch, [
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_POST => true,
		CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token, 'Content-Type: application/json'],
		CURLOPT_POSTFIELDS => json_encode(['query' => $query, 'variables' => ['id' => (string)$contactId]])
	]);
	$graphqlRes = curl_exec($ch);
	$result = json_decode($graphqlRes, true);
	curl_close($ch);
	if (isset($result['errors'])) {
		error_log("GraphQL Syntax Error: " . json_encode($result['errors']));
		return "error=syntax_check_logs";
	}

	$contact = $result['data']['CRM']['contact'] ?? null;
	if (!$contact) return "error=no_contact_data";

	// 5. Flatten results for the URL
	$params = [];
	$getVal = fn($v) => is_array($v) ? ($v['label'] ?? '') : $v;

	foreach ($contactFields as $f) {
		$params[$f] = $getVal($contact[$f] ?? '');
	}

	$company = ($contact['associations']['company_collection__primary']['items'] ?? [])[0] ?? null;
	if ($company) {
		foreach ($companyFields as $f) {
			$params[$f] = $getVal($company[$f] ?? '');
		}
	}
	
	return http_build_query(array_filter($params));
}

$formId = 'eae6b326-2d0c-4534-b652-69dd49011c1f';
$prefillQuery = "";

if (!empty($currentProfile['id_hubspot_b2b_contact'])) {
	$prefillQuery = getHubSpotUrlParams($currentProfile['id_hubspot_b2b_contact'], $formId);
}
?>

<!DOCTYPE html>
<html class="h-full bg-gray-900">
<?php include 'partials/meta.php'; ?>
<body class="antialiased bg-gray-50 dark:bg-gray-900 h-full">
<div class="max-w-[1600px] h-full bg-gray-200 dark:bg-gray-900 border-r border-gray-600 dark:border-gray-600">
<?php include 'partials/header.php'; ?>
<?php include 'partials/sidebar.php'; ?>
<main class="md:ml-64 h-auto pt-20">
<div class="p-8 border-t border-gray-600 dark:border-gray-600">
    <script>
    (function() {
        const query = "<?= $prefillQuery ?>";
        if (query && !window.location.search) {
            const newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + '?' + query;
            window.history.replaceState({ path: newUrl }, '', newUrl);
        }

        window.addEventListener('message', event => {
            if (event.data.type === 'hsFormCallback' && event.data.eventName === 'onFormReady') {
                const urlParams = new URLSearchParams(window.location.search);
                const form = document.querySelector('.hs-form-html form');
                urlParams.forEach((value, key) => {
                    const inputs = form.querySelectorAll(`input[name="${key}"][type="radio"], input[name$="/${key}"][type="radio"], input[name="${key}"][type="checkbox"], input[name$="/${key}"][type="checkbox"]`);
                    inputs.forEach(input => {
                        if (input.value === value) {
                            input.checked = true;
                            input.dispatchEvent(new Event('change', { bubbles: true }));
                            console.log(`✅ Prefilled Radio/Checkbox: ${key} = ${value}`);
                        }
                    });
                });
            }
        });
    })();
    </script>

    <script src="https://js-eu1.hsforms.net/forms/embed/developer/27229630.js" defer></script>
    <div class="hs-form-html" 
         data-region="eu1" 
         data-form-id="<?= $formId ?>" 
         data-portal-id="27229630">
    </div>

    <script>
        window.addEventListener("hs-form-event:on-submission:success", async event => {
            try {
                const response = await fetch('profile-edit.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                const result = await response.json();
                
                if (result.success) {
                    console.log('Profile timestamp updated successfully');
                } else {
                    console.error('Failed to update profile timestamp:', result.error);
                }
            } catch (error) {
                console.error('Error updating profile timestamp:', error);
            }
            
            // Sync HubSpot contact and company IDs (also checks B2C and Shopify)
            try {
                const syncResponse = await fetch('/api/sync_external_ids.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        profile_id: <?= $currentProfile['id'] ?? 0 ?>,
                        email: '<?= htmlspecialchars($currentProfile['email'] ?? '') ?>'
                    })
                });
                const syncResult = await syncResponse.json();
                if (syncResult.success) {
                    console.log('External IDs synced:', syncResult);
                } else {
                    console.error('Failed to sync external IDs:', syncResult.error);
                }
            } catch (error) {
                console.error('Error syncing external IDs:', error);
            }
        });
    </script>
</div>
</main>

<?php include 'partials/footer.php'; ?>
</div>
</body>
</html>