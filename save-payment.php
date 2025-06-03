<?php
// -------------------------------
// ✅ CORS HEADERS (Adjust domain as needed)
// -------------------------------
header("Access-Control-Allow-Origin: https://theshowcenter.com");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");

// -------------------------------
// ✅ Handle OPTIONS Preflight Request
// -------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// -------------------------------
// ✅ SANDBOX AUTH.NET CONFIG
// -------------------------------
$endpoint = "https://apitest.authorize.net/xml/v1/request.api";
$api_login_id = '4625ksJLu';
$transaction_key = '6F5S66g2Nsd49w8A';

// -------------------------------
// ✅ PARSE JSON INPUT
// -------------------------------
$input = json_decode(file_get_contents('php://input'), true);
$dataDescriptor = $input['dataDescriptor'] ?? null;
$dataValue = $input['dataValue'] ?? null;
$fullName = $input['fullName'] ?? "Test User";
$email = $input['email'] ?? "test@example.com";
$phone = $input['phone'] ?? "0000000000";

// -------------------------------
// ❌ RETURN 400 IF TOKEN MISSING
// -------------------------------
if (!$dataDescriptor || !$dataValue) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing payment token.']);
    exit;
}

// -------------------------------
// ✅ BUILD REQUEST PAYLOAD
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
// ✅ SEND TO AUTH.NET API
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
// ✅ RETURN RESPONSE TO FRONTEND
// -------------------------------
http_response_code($httpCode);
header('Content-Type: application/json');
echo $response;