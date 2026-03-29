<?php

/**
 * Perform a synchronous HTTP request.
 *
 * @param  string      $url
 * @param  string[]    $headers  e.g. ['Authorization: Bearer xyz', 'Content-Type: application/json']
 * @param  string|null $body     Pass a string body to send a POST; omit for GET.
 * @param  int         $timeout  Seconds before the request is abandoned.
 * @return array{status: int, body: string}
 */
function httpRequest(string $url, array $headers = [], ?string $body = null, int $timeout = 15): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_TIMEOUT        => $timeout,
    ]);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }
    $response = curl_exec($ch);
    $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error    = curl_error($ch);
    curl_close($ch);

    if ($error) {
        error_log("httpRequest cURL error [{$url}]: {$error}");
        return ['status' => 0, 'body' => ''];
    }
    return ['status' => $status, 'body' => $response ?: ''];
}

/**
 * Search a HubSpot portal for a contact by email address.
 *
 * This project uses TWO separate HubSpot installations:
 *   - B2B portal  →  token env key: hubspotTokenB2B
 *   - B2C portal  →  token env key: hubspotTokenB2C
 * Pass the relevant $tokenEnvKey to target the right one.
 *
 * @param  string   $email
 * @param  string   $tokenEnvKey  Environment variable name holding the Bearer token.
 * @param  string[] $properties   HubSpot contact properties to return.
 * @return array|null Raw first result from HubSpot, or null on failure / not found.
 */
function checkHubSpot(string $email, string $tokenEnvKey, array $properties): ?array {
    $token = $_ENV[$tokenEnvKey] ?? '';
    if (empty($token)) {
        error_log("HubSpot token not configured: {$tokenEnvKey}");
        return null;
    }

    $body = json_encode([
        'properties'   => $properties,
        'filterGroups' => [
            ['filters' => [['propertyName' => 'hs_additional_emails', 'operator' => 'EQ', 'value' => $email]]],
            ['filters' => [['propertyName' => 'email',                'operator' => 'EQ', 'value' => $email]]],
        ],
    ]);

    $res = httpRequest(
        'https://api.hubapi.com/crm/v3/objects/contacts/search',
        ['Authorization: Bearer ' . $token, 'Content-Type: application/json'],
        $body
    );

    if ($res['status'] !== 200) {
        error_log("HubSpot search error [{$tokenEnvKey}]: HTTP {$res['status']}");
        return null;
    }

    $result = json_decode($res['body'], true);
    return !empty($result['results'][0]) ? $result['results'][0] : null;
}

/** Thin wrapper — B2B portal (hubspotTokenB2B). */
function checkHubSpotB2B(string $email): ?array {
    $contact = checkHubSpot($email, 'hubspotTokenB2B', ['email', 'associatedcompanyid']);
    if (!$contact) return null;
    return [
        'contact_id' => $contact['id'] ?? null,
        'company_id' => $contact['properties']['associatedcompanyid'] ?? null,
        'properties' => $contact['properties'] ?? [],
    ];
}

/** Thin wrapper — B2C portal (hubspotTokenB2C). */
function checkHubSpotB2C(string $email): ?array {
    $contact = checkHubSpot($email, 'hubspotTokenB2C', ['email']);
    if (!$contact) return null;
    return ['contact_id' => $contact['id'] ?? null];
}

function checkShopify(string $email): ?array {
    $token = $_ENV['shopifyToken'] ?? '';
    if (empty($token)) {
        error_log('Shopify token not configured');
        return null;
    }

    $res = httpRequest(
        'https://curaproxclub.myshopify.com/admin/api/2024-01/customers/search.json?query=' . urlencode('email:' . $email),
        ['Content-Type: application/json', 'X-Shopify-Access-Token: ' . $token]
    );

    if ($res['status'] !== 200) {
        error_log("Shopify API error: HTTP {$res['status']}");
        return null;
    }

    $result = json_decode($res['body'], true);
    return !empty($result['customers'][0]) ? ['customer_id' => $result['customers'][0]['id']] : null;
}

/**
 * Check all three external services and store any newly-found IDs.
 *
 * Runs HubSpot B2B, B2C, and Shopify requests in PARALLEL via curl_multi,
 * cutting login time from ~(t_b2b + t_b2c + t_shopify) down to ~max(t_b2b, t_b2c, t_shopify).
 * Only services that are missing data for this profile are queried.
 */
function checkAndStoreExternalIds($pdo, $profileId, $email): bool {
    $stmt = $pdo->prepare('SELECT id_hubspot_b2b_contact, id_hubspot_b2b_company, id_hubspot_b2c_contact, id_shopify_b2c FROM profile WHERE id = ?');
    $stmt->execute([$profileId]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);

    $needsB2B     = empty($profile['id_hubspot_b2b_contact']) || empty($profile['id_hubspot_b2b_company']);
    $needsB2C     = empty($profile['id_hubspot_b2c_contact']);
    $needsShopify = empty($profile['id_shopify_b2c']);

    if (!$needsB2B && !$needsB2C && !$needsShopify) {
        return false;
    }

    $mh      = curl_multi_init();
    $handles = [];

    // Shared HubSpot search filter (matches primary email or any additional email)
    $hsFilters = [
        'filterGroups' => [
            ['filters' => [['propertyName' => 'hs_additional_emails', 'operator' => 'EQ', 'value' => $email]]],
            ['filters' => [['propertyName' => 'email',                'operator' => 'EQ', 'value' => $email]]],
        ],
    ];
    $hsUrl = 'https://api.hubapi.com/crm/v3/objects/contacts/search';

    if ($needsB2B && !empty($_ENV['hubspotTokenB2B'])) {
        $ch = curl_init($hsUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode(array_merge($hsFilters, ['properties' => ['email', 'associatedcompanyid']])),
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $_ENV['hubspotTokenB2B'], 'Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 15,
        ]);
        $handles['b2b'] = $ch;
        curl_multi_add_handle($mh, $ch);
    }

    if ($needsB2C && !empty($_ENV['hubspotTokenB2C'])) {
        $ch = curl_init($hsUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode(array_merge($hsFilters, ['properties' => ['email']])),
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $_ENV['hubspotTokenB2C'], 'Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 15,
        ]);
        $handles['b2c'] = $ch;
        curl_multi_add_handle($mh, $ch);
    }

    if ($needsShopify && !empty($_ENV['shopifyToken'])) {
        $ch = curl_init('https://curaproxclub.myshopify.com/admin/api/2024-01/customers/search.json?query=' . urlencode('email:' . $email));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'X-Shopify-Access-Token: ' . $_ENV['shopifyToken']],
            CURLOPT_TIMEOUT        => 15,
        ]);
        $handles['shopify'] = $ch;
        curl_multi_add_handle($mh, $ch);
    }

    if (empty($handles)) {
        curl_multi_close($mh);
        return false;
    }

    // Execute all requests in parallel
    $running = null;
    do {
        curl_multi_exec($mh, $running);
        curl_multi_select($mh);
    } while ($running > 0);

    $updates = [];
    $params  = [];

    if (isset($handles['b2b'])) {
        $body = curl_multi_getcontent($handles['b2b']);
        $code = curl_getinfo($handles['b2b'], CURLINFO_HTTP_CODE);
        curl_multi_remove_handle($mh, $handles['b2b']);
        if ($code === 200) {
            $r = json_decode($body, true);
            if (!empty($r['results'][0])) {
                $c = $r['results'][0];
                if (!empty($c['id']))                                { $updates[] = 'id_hubspot_b2b_contact = ?'; $params[] = $c['id']; }
                if (!empty($c['properties']['associatedcompanyid'])) { $updates[] = 'id_hubspot_b2b_company = ?'; $params[] = $c['properties']['associatedcompanyid']; }
            }
        } else {
            error_log("HubSpot B2B API error: HTTP {$code}");
        }
    }

    if (isset($handles['b2c'])) {
        $body = curl_multi_getcontent($handles['b2c']);
        $code = curl_getinfo($handles['b2c'], CURLINFO_HTTP_CODE);
        curl_multi_remove_handle($mh, $handles['b2c']);
        if ($code === 200) {
            $r = json_decode($body, true);
            if (!empty($r['results'][0]['id'])) { $updates[] = 'id_hubspot_b2c_contact = ?'; $params[] = $r['results'][0]['id']; }
        } else {
            error_log("HubSpot B2C API error: HTTP {$code}");
        }
    }

    if (isset($handles['shopify'])) {
        $body = curl_multi_getcontent($handles['shopify']);
        $code = curl_getinfo($handles['shopify'], CURLINFO_HTTP_CODE);
        curl_multi_remove_handle($mh, $handles['shopify']);
        if ($code === 200) {
            $r = json_decode($body, true);
            if (!empty($r['customers'][0]['id'])) { $updates[] = 'id_shopify_b2c = ?'; $params[] = $r['customers'][0]['id']; }
        } else {
            error_log("Shopify API error: HTTP {$code}");
        }
    }

    curl_multi_close($mh);

    if (!empty($updates)) {
        $params[] = $profileId;
        $stmt = $pdo->prepare('UPDATE profile SET ' . implode(', ', $updates) . ' WHERE id = ?');
        $stmt->execute($params);
    }

    return !empty($updates);
}

function getHubSpotB2BData($contactId): ?array {
    $token = $_ENV['hubspotTokenB2B'] ?? '';
    if (empty($token) || empty($contactId)) return null;

    $query = 'query GetContactData($contactId: String!) {
        CRM {
            contact(uniqueIdentifier: "hs_object_id", uniqueIdentifierValue: $contactId) {
                firstname lastname email phone jobtitle
                address address2 city state_us zip country
                jobtitle_additional jobtitle_dental jobtitle_pharmacy
                jobtitle_generalhealth jobtitle_pregnancypediatricmedicine jobtitle_student
                institution_other institution_us
                institution_education_start institution_education_end
                license_number type_multiplier hs_object_id id_erp
                associations {
                    company_collection__primary {
                        items {
                            name address address2 city state_us zip country
                            phone domain website hs_object_id
                        }
                    }
                }
            }
        }
    }';

    $res = httpRequest(
        'https://api.hubapi.com/collector/graphql',
        ['Authorization: Bearer ' . $token, 'Content-Type: application/json'],
        json_encode([
            'operationName' => 'GetContactData',
            'query'         => $query,
            'variables'     => ['contactId' => (string)$contactId],
        ])
    );

    if ($res['status'] !== 200) return null;

    $result = json_decode($res['body'], true);
    if (isset($result['errors'])) return null;

    $contactData = $result['data']['CRM']['contact'] ?? null;
    if (!$contactData) return null;

    // Country can be a string or an array {label, value} depending on the HubSpot field type
    $normaliseCountry = fn($v) => is_array($v) ? ($v['label'] ?? $v['value'] ?? '') : ($v ?? '');

    $data = [
        'contact' => [
            'firstname'                   => $contactData['firstname'] ?? '',
            'lastname'                    => $contactData['lastname'] ?? '',
            'email'                       => $contactData['email'] ?? '',
            'phone'                       => $contactData['phone'] ?? '',
            'jobtitle'                    => $contactData['jobtitle'] ?? '',
            'address'                     => $contactData['address'] ?? '',
            'address2'                    => $contactData['address2'] ?? '',
            'city'                        => $contactData['city'] ?? '',
            'state'                       => $contactData['state_us'] ?? '',
            'zip'                         => $contactData['zip'] ?? '',
            'country'                     => $normaliseCountry($contactData['country'] ?? ''),
            'jobtitle_additional'         => $contactData['jobtitle_additional'] ?? '',
            'institution_other'           => $contactData['institution_other'] ?? '',
            'institution_us'              => $contactData['institution_us'] ?? '',
            'institution_education_start' => $contactData['institution_education_start'] ?? '',
            'institution_education_end'   => $contactData['institution_education_end'] ?? '',
            'license_number'              => $contactData['license_number'] ?? '',
            'type_multiplier'             => $contactData['type_multiplier'] ?? '',
            'id_erp'                      => $contactData['id_erp'] ?? '',
        ],
        'company' => [],
    ];

    $companies = $contactData['associations']['company_collection__primary']['items'] ?? [];
    if (!empty($companies)) {
        $c = $companies[0];
        $data['company'] = [
            'name'         => $c['name'] ?? '',
            'address'      => $c['address'] ?? '',
            'address2'     => $c['address2'] ?? '',
            'city'         => $c['city'] ?? '',
            'state'        => $c['state_us'] ?? '',
            'zip'          => $c['zip'] ?? '',
            'country'      => $normaliseCountry($c['country'] ?? ''),
            'phone'        => $c['phone'] ?? '',
            'domain'       => $c['domain'] ?? '',
            'hs_object_id' => $c['hs_object_id'] ?? '',
            'website'      => $c['website'] ?? '',
        ];
    }

    return $data;
}

/**
 * Fetch page data from the database and parse the Markdown description.
 */
function getPageData($url = null): array {
    global $parsedown;

    if ($url === null) {
        $url = basename($_SERVER['PHP_SELF']);
    }

    $pdo  = getDbConnection();
    $stmt = $pdo->prepare('SELECT header, name, description, description_short, required_role FROM page WHERE url = ? LIMIT 1');
    $stmt->execute([$url]);
    $pageData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pageData) {
        return [
            'header'           => 'Page Not Found',
            'description_html' => '<p>Sorry, this content is not available.</p>',
            'roles'            => [],
        ];
    }

    return [
        'header'            => $pageData['header'],
        'name'              => $pageData['name'],
        'icon'              => $pageData['icon'] ?? '',
        'description_short' => $pageData['description_short'] ?? '',
        'description_raw'   => $pageData['description'] ?? '',
        'description_html'  => $parsedown->text($pageData['description'] ?? ''),
        'roles'             => json_decode($pageData['required_role'] ?? '[]', true),
    ];
}
