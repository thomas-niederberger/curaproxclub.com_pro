<?php
header('Content-Type: application/json');
define('API_REQUEST', true);
require_once __DIR__ . '/../config/config.php';
requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$formId    = $_GET['form_id'] ?? '';
$contactId = $currentProfile['id_hubspot_b2b_contact'] ?? null;

if (empty($formId) || empty($contactId)) {
    echo json_encode(['success' => false, 'error' => 'Missing form_id or no HubSpot contact linked']);
    exit;
}

$token = $_ENV['hubspotTokenB2B'] ?? '';
if (empty($token)) {
    http_response_code(503);
    echo json_encode(['success' => false, 'error' => 'HubSpot not configured']);
    exit;
}

// Step 1: Fetch the HubSpot form definition to discover which fields to prefill
$formRes = httpRequest(
    "https://api.hubapi.com/marketing/v3/forms/{$formId}",
    ['Authorization: Bearer ' . $token]
);

if ($formRes['status'] !== 200) {
    http_response_code(502);
    echo json_encode(['success' => false, 'error' => 'Failed to fetch HubSpot form definition']);
    exit;
}

$formDef = json_decode($formRes['body'], true);

if (!isset($formDef['fieldGroups'])) {
    echo json_encode(['success' => false, 'error' => 'Form not found or has no fields']);
    exit;
}

// Sort fields into contact vs company buckets (skip HubSpot-internal and legal-consent fields)
$contactFields = [];
$companyFields = [];

foreach ($formDef['fieldGroups'] as $group) {
    foreach ($group['fields'] as $field) {
        $name = $field['name'];
        if (str_starts_with($name, 'hs_') || str_starts_with($name, 'LEGAL_CONSENT')) {
            continue;
        }
        if (($field['objectTypeId'] ?? '') === '0-2') {
            $companyFields[] = $name;
        } else {
            $contactFields[] = $name;
        }
    }
}

// Step 2: Build a GraphQL query for exactly the fields this form needs
$contactFieldsStr = implode("\n", array_unique($contactFields));
$companyFieldsStr = !empty($companyFields)
    ? 'associations { company_collection__primary { items { ' . implode("\n", array_unique($companyFields)) . ' } } }'
    : '';

$query = "query GetPrefillData(\$id: String!) {
    CRM {
        contact(uniqueIdentifier: \"hs_object_id\", uniqueIdentifierValue: \$id) {
            {$contactFieldsStr}
            {$companyFieldsStr}
        }
    }
}";

$graphqlRes = httpRequest(
    'https://api.hubapi.com/collector/graphql',
    ['Authorization: Bearer ' . $token, 'Content-Type: application/json'],
    json_encode(['query' => $query, 'variables' => ['id' => (string)$contactId]])
);

if ($graphqlRes['status'] !== 200) {
    http_response_code(502);
    echo json_encode(['success' => false, 'error' => 'Failed to fetch HubSpot contact data']);
    exit;
}

$result = json_decode($graphqlRes['body'], true);

if (isset($result['errors'])) {
    error_log('hubspot_prefill_get GraphQL error: ' . json_encode($result['errors']));
    echo json_encode(['success' => false, 'error' => 'GraphQL query error — check server logs']);
    exit;
}

$contact = $result['data']['CRM']['contact'] ?? null;
if (!$contact) {
    echo json_encode(['success' => false, 'error' => 'No contact data found']);
    exit;
}

// Flatten contact and company values into a params array
$getVal  = fn($v) => is_array($v) ? ($v['label'] ?? '') : ($v ?? '');
$params  = [];

foreach ($contactFields as $field) {
    $params[$field] = $getVal($contact[$field] ?? '');
}

$company = ($contact['associations']['company_collection__primary']['items'] ?? [])[0] ?? null;
if ($company) {
    foreach ($companyFields as $field) {
        $params[$field] = $getVal($company[$field] ?? '');
    }
}

echo json_encode([
    'success'      => true,
    'query_string' => http_build_query(array_filter($params)),
]);
