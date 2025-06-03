<?php
// SANDBOX endpoint
$endpoint = "https://apitest.authorize.net/xml/v1/request.api";

// Your sandbox credentials
$api_login_id = '4625ksJLu';
$transaction_key = '6F5S66g2Nsd49w8A';

// Log raw request (optional)
file_put_contents("php://stderr", "RAW INPUT: " . file_get_contents("php://input") . "\n", FILE_APPEND);

// Read the incoming JSON
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
$dataDescriptor = $input['dataDescriptor'] ?? null;
$dataValue = $input['dataValue'] ?? null;

if (!$dataDescriptor || !$dataValue) {
  http_response_code(400);
  echo json_encode(['error' => 'Missing tokenized card data.']);
  exit;
}

// Create customer ID for demo purposes
$customerId = uniqid("cust_");

// Optional fields if added later
$fullName = $input['fullName'] ?? "Test User";
$email = $input['email'] ?? "test@example.com";
$phone = $input['phone'] ?? "0000000000";

// Prepare the request payload
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

// Send request to Authorize.net
$ch = curl_init($endpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
$response = curl_exec($ch);
curl_close($ch);

// Return response to frontend
header('Content-Type: application/json');
echo $response;
