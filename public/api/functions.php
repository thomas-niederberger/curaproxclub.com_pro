<?php

function checkHubSpotB2B($email) {
    $token = $_ENV['hubspotTokenB2B'] ?? '';
    if (empty($token)) {
        error_log('HubSpot B2B token not configured');
        return null;
    }
     
    $url = 'https://api.hubapi.com/crm/v3/objects/contacts/search';
    
    $data = [
        'properties' => ['email', 'associatedcompanyid'],
        'filterGroups' => [
            [
                'filters' => [
                    [
                        'propertyName' => 'hs_additional_emails',
                        'operator' => 'EQ',
                        'value' => $email
                    ]
                ]
            ],
            [
                'filters' => [
                    [
                        'propertyName' => 'email',
                        'operator' => 'EQ',
                        'value' => $email
                    ]
                ]
            ]
        ]
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        error_log("HubSpot B2B API error: HTTP $httpCode - $response");
        return null;
    }
    
    $result = json_decode($response, true);
    
    if (!empty($result['results'][0])) {
        $contact = $result['results'][0];
        return [
            'contact_id' => $contact['id'] ?? null,
            'company_id' => $contact['properties']['associatedcompanyid'] ?? null,
            'properties' => $contact['properties'] ?? []
        ];
    }
    
    return null;
}

function checkHubSpotB2C($email) {
    $token = $_ENV['hubspotTokenB2C'] ?? '';
    if (empty($token)) {
        error_log('HubSpot B2C token not configured');
        return null;
    }
    
    $url = 'https://api.hubapi.com/crm/v3/objects/contacts/search';
    
    $data = [
        'properties' => ['email'],
        'filterGroups' => [
            [
                'filters' => [
                    [
                        'propertyName' => 'hs_additional_emails',
                        'operator' => 'EQ',
                        'value' => $email
                    ]
                ]
            ],
            [
                'filters' => [
                    [
                        'propertyName' => 'email',
                        'operator' => 'EQ',
                        'value' => $email
                    ]
                ]
            ]
        ]
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        error_log("HubSpot B2C API error: HTTP $httpCode - $response");
        return null;
    }
    
    $result = json_decode($response, true);
    
    if (!empty($result['results'][0])) {
        return [
            'contact_id' => $result['results'][0]['id'] ?? null
        ];
    }
    
    return null;
}

function checkShopify($email) {
    $token = $_ENV['shopifyToken'] ?? '';
    if (empty($token)) {
        error_log('Shopify token not configured');
        return null;
    }
    
    $url = 'https://curaproxclub.myshopify.com/admin/api/2024-01/customers/search.json?query=' . urlencode('email:' . $email);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-Shopify-Access-Token: ' . $token
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        error_log("Shopify API error: HTTP $httpCode - $response");
        return null;
    }
    
    $result = json_decode($response, true);
    
    if (!empty($result['customers'][0])) {
        return [
            'customer_id' => $result['customers'][0]['id'] ?? null
        ];
    }
    
    return null;
}

function checkAndStoreExternalIds($pdo, $profileId, $email) {
    $updates = [];
    $params = [];
    
    $stmt = $pdo->prepare('SELECT id_hubspot_b2b_contact, id_hubspot_b2b_company, id_hubspot_b2c_contact, id_shopify_b2c FROM profile WHERE id = ?');
    $stmt->execute([$profileId]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (empty($profile['id_hubspot_b2b_contact']) || empty($profile['id_hubspot_b2b_company'])) {
        $hubspotB2B = checkHubSpotB2B($email);
        if ($hubspotB2B) {
            if (!empty($hubspotB2B['contact_id'])) {
                $updates[] = 'id_hubspot_b2b_contact = ?';
                $params[] = $hubspotB2B['contact_id'];
            }
            if (!empty($hubspotB2B['company_id'])) {
                $updates[] = 'id_hubspot_b2b_company = ?';
                $params[] = $hubspotB2B['company_id'];
            }
        }
    }
    
    if (empty($profile['id_hubspot_b2c_contact'])) {
        $hubspotB2C = checkHubSpotB2C($email);
        if ($hubspotB2C && !empty($hubspotB2C['contact_id'])) {
            $updates[] = 'id_hubspot_b2c_contact = ?';
            $params[] = $hubspotB2C['contact_id'];
        }
    }
    
    if (empty($profile['id_shopify_b2c'])) {
        $shopify = checkShopify($email);
        if ($shopify && !empty($shopify['customer_id'])) {
            $updates[] = 'id_shopify_b2c = ?';
            $params[] = $shopify['customer_id'];
        }
    }
    
    if (!empty($updates)) {
        $params[] = $profileId;
        $sql = 'UPDATE profile SET ' . implode(', ', $updates) . ' WHERE id = ?';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }
    
    return !empty($updates);
}

function getHubSpotB2BData($contactId, $companyId = null) {
    $token = $_ENV['hubspotTokenB2B'] ?? '';
    if (empty($token) || empty($contactId)) {
        return null;
    }
    
    $url = 'https://api.hubapi.com/collector/graphql';
    
    $query = 'query GetContactData($contactId: String!) {
        CRM {
            contact(uniqueIdentifier: "hs_object_id", uniqueIdentifierValue: $contactId) {
                firstname
                lastname
                email
                phone
                jobtitle
                address
                address2
                city
                state_us
                zip
                country
                jobtitle_additional
                jobtitle_dental
                jobtitle_pharmacy
                jobtitle_generalhealth
                jobtitle_pregnancypediatricmedicine
                jobtitle_student
                institution_other
                institution_us
                institution_education_start
                institution_education_end
                license_number
                type_multiplier
                hs_object_id
                id_erp
                associations {
                    company_collection__primary {
                        items {
                            name
                            address
                            address2
                            city
                            state_us
                            zip
                            country
                            phone
                            domain
                            website
                            hs_object_id  
                        }
                    }
                }
            }
        }
    }';
    
    $requestData = [
        'operationName' => 'GetContactData',
        'query' => $query,
        'variables' => [
            'contactId' => (string)$contactId
        ]
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        return null;
    }
    
    $result = json_decode($response, true);
    
    if (isset($result['errors'])) {
        return null;
    }
    
    $contactData = $result['data']['CRM']['contact'] ?? null;
    if (!$contactData) {
        return null;
    }
    
    // Handle country field - it can be a string or array with label/value
    $contactCountry = $contactData['country'] ?? '';
    if (is_array($contactCountry)) {
        $contactCountry = $contactCountry['label'] ?? $contactCountry['value'] ?? '';
    }
    
    $data = [
        'contact' => [
            'firstname' => $contactData['firstname'] ?? '',
            'lastname' => $contactData['lastname'] ?? '',
            'email' => $contactData['email'] ?? '',
            'phone' => $contactData['phone'] ?? '',
            'jobtitle' => $contactData['jobtitle'] ?? '',
            'address' => $contactData['address'] ?? '',
            'address2' => $contactData['address2'] ?? '',
            'city' => $contactData['city'] ?? '',
            'state' => $contactData['state_us'] ?? '',
            'zip' => $contactData['zip'] ?? '',
            'country' => $contactCountry,
            'jobtitle_additional' => $contactData['jobtitle_additional'] ?? '',
            'institution_other' => $contactData['institution_other'] ?? '',
            'institution_us' => $contactData['institution_us'] ?? '',
            'institution_education_start' => $contactData['institution_education_start'] ?? '',
            'institution_education_end' => $contactData['institution_education_end'] ?? '',
            'license_number' => $contactData['license_number'] ?? '',
            'type_multiplier' => $contactData['type_multiplier'] ?? '',
            'id_erp' => $contactData['id_erp'] ?? ''
        ],
        'company' => []
    ];
    
    $companies = $contactData['associations']['company_collection__primary']['items'] ?? [];
    
    if (!empty($companies)) {
        $company = $companies[0];
        
        // Handle company country field - it can be a string or array with label/value
        $companyCountry = $company['country'] ?? '';
        if (is_array($companyCountry)) {
            $companyCountry = $companyCountry['label'] ?? $companyCountry['value'] ?? '';
        }
        
        $data['company'] = [
            'name' => $company['name'] ?? '',
            'address' => $company['address'] ?? '',
            'address2' => $company['address2'] ?? '',
            'city' => $company['city'] ?? '',
            'state' => $company['state_us'] ?? '',
            'zip' => $company['zip'] ?? '',
            'country' => $companyCountry,
            'phone' => $company['phone'] ?? '',
            'domain' => $company['domain'] ?? '',
            'hs_object_id' => $company['hs_object_id'] ?? '',
            'website' => $company['website'] ?? ''
        ];
    }
    
    return $data;
}

/**
 * Fetch page data from database and parse markdown description
 */
function getPageData($url = null) {
    global $parsedown;
    
    // Determine current URL if not provided
    if ($url === null) {
        $url = basename($_SERVER['PHP_SELF']);
    }
    
    $pdo = getDbConnection();
    
    // Updated Query: Added header, description_short, and required_role
    $stmt = $pdo->prepare('SELECT header, name, description, description_short, required_role FROM page WHERE url = ? LIMIT 1');
    $stmt->execute([$url]);
    $pageData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Fallback if page is not found
    if (!$pageData) {
        return [
            'header' => 'Page Not Found',
            'description_html' => '<p>Sorry, this content is not available.</p>',
            'roles' => []
        ];
    }

    // Parse the Markdown into HTML
    if (!$parsedown) {
        require_once 'Parsedown.php';
        $parsedown = new \Parsedown();
    }
    
    $descriptionHtml = $parsedown->text($pageData['description'] ?? '');
    
    // Return the clean data to your template
    return [
        'header'            => $pageData['header'],
        'name'              => $pageData['name'],
        'icon'              => $pageData['icon'] ?? '',
        'description_short' => $pageData['description_short'] ?? '',
        'description_raw'   => $pageData['description'] ?? '',
        'description_html'  => $descriptionHtml,
        'roles'             => json_decode($pageData['required_role'] ?? '[]', true)
    ];
}