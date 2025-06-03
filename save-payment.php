<?php
// SANDBOX endpoint
$endpoint = "https://apitest.authorize.net/xml/v1/request.api";

// Your sandbox credentials
$api_login_id = '4625ksJLu';
$transaction_key = '5PdvByq676376M5b';

$input = json_decode(file_get_contents('php://input'), true);

$dataValue = $input['dataValue'];
$dataDescriptor = $input['dataDescriptor'];
$fullName = $input['fullName'];
$email = $input['email'];
$phone = $input['phone'];
$customerId = uniqid('cust_');

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

$ch = curl_init($endpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

$response = curl_exec($ch);
curl_close($ch);

header('Content-Type: application/json');
echo $response;