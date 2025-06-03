<?php
// Log raw input for debugging
file_put_contents("php://stderr", "RAW INPUT: " . file_get_contents("php://input") . "\n", FILE_APPEND);

// SANDBOX endpoint
$endpoint = "https://apitest.authorize.net/xml/v1/request.api";

// Your sandbox credentials
$api_login_id = '4625ksJLu';
$transaction_key = '6F5S66g2Nsd49w8A';

// Decode incoming JSON
$input = json_decode(file_get_contents('php://input'), true);

// Safely extract fields
$dataValue = $input['dataValue'] ?? null;
$dataDescriptor = $input['dataDescriptor'] ?? null;
$fullName = $input['fullName'] ?? null;
$email = $input['email'] ?? null;
$phone = $input['phone'] ?? null;

// Validate required fields
if (!$dataValue || !$dataDescriptor) {
  http_response_code(400);
  echo json_encode(['error' => 'Missing tokenized card data.']);
  exit;
}

// Create a unique customer ID
$customerId = uniqid('cust_');

// Prepare payload for Authorize.net
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

// Make the API call to Authorize.net
$ch = curl_init($endpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
$response = curl_exec($ch);
curl_close($ch);

// Send back the API response
header('Content-Type: application/json');
echo $response;
