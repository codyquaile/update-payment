<?php
// -------------------------------
// ✅ CORS HEADERS
// -------------------------------
header("Access-Control-Allow-Origin: https://theshowcenter.com");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// -------------------------------
// ✅ SANDBOX CONFIG
// -------------------------------
$endpoint = "https://apitest.authorize.net/xml/v1/request.api";
$api_login_id = '4625ksJLu';
$transaction_key = '6F5S66g2Nsd49w8A';

// -------------------------------
// ✅ INPUT
// -------------------------------
$input = json_decode(file_get_contents('php://input'), true);
$dataDescriptor = $input['dataDescriptor'] ?? null;
$dataValue = $input['dataValue'] ?? null;
$fullName = $input['fullName'] ?? "Test User";
$email = $input['email'] ?? "test@example.com";
$phone = $input['phone'] ?? "0000000000";

if (!$dataDescriptor || !$dataValue) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing payment token.']);
    exit;
}

// -------------------------------
// ✅ PAYLOAD
// -------------------------------
$customerId = uniqid("cust_");

$payload = [
    "createCustomerProfileRequest" => [
        "merchantAuthentication" => [
            "name" => $api_login_id,
            "transactionKey" => $transaction_key
        ],
        "profile" => [
            "merchantCustomerId" => $customerId,
            "description" => $fullName,
            "email" => $email,
            "paymentProfiles" => [[
                "payment" => [
                    "opaqueData" => [
                        "dataDescriptor" => $dataDescriptor,
                        "dataValue" => $dataValue
                    ]
                ]
            ]]
        ]
    ]
];

// -------------------------------
// ✅ SEND REQUEST
// -------------------------------
$ch = curl_init($endpoint);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode($payload)
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// -------------------------------
// ✅ HANDLE RESPONSE
// -------------------------------
$responseData = json_decode($response, true);

$profileId = $responseData['customerProfileId'] ?? null;
$paymentProfileId = $responseData['customerPaymentProfileIdList'][0] ?? null;

// If successful, add to response and trigger webhook
if (
    isset($responseData['messages']['resultCode']) &&
    $responseData['messages']['resultCode'] === "Ok"
) {
    $responseData['stored'] = [
        'profileId' => $profileId,
        'paymentProfileId' => $paymentProfileId
    ];

    // ✅ Send to n8n webhook
    $webhook_url = 'https://codyquaile.app.n8n.cloud/webhook-test/c61a1541-57cc-47ae-956d-95ef684408ea';

    $webhook_payload = json_encode([
        'fullName' => $fullName,
        'email' => $email,
        'phone' => $phone,
        'profileId' => $profileId,
        'paymentProfileId' => $paymentProfileId
    ]);

    $wh = curl_init($webhook_url);
    curl_setopt_array($wh, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $webhook_payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json']
    ]);
    curl_exec($wh);
    curl_close($wh);
}

// ✅ Return response to frontend
http_response_code($httpCode);
header('Content-Type: application/json');
echo json_encode($responseData);

// Fallback if API call failed or no response
if (!isset($responseData) || !is_array($responseData)) {
    $responseData = ['error' => 'No valid response from payment gateway'];
}

// Final output
http_response_code($httpCode ?? 500);
header('Content-Type: application/json');
echo json_encode($responseData);