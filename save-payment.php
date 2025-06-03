
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$api_login_id = 'YOUR_API_LOGIN_ID';
$transaction_key = 'YOUR_TRANSACTION_KEY';

$input = json_decode(file_get_contents('php://input'), true);
$dataValue = $input['dataValue'];
$dataDescriptor = $input['dataDescriptor'];
$fullName = $input['fullName'] ?? 'Unknown';
$email = $input['email'] ?? 'noemail@example.com';
$phone = $input['phone'] ?? '0000000000';
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

$ch = curl_init("https://apitest.authorize.net/xml/v1/request.api");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

header('Content-Type: application/json');
if ($httpCode === 200) {
  echo $response;
} else {
  echo json_encode(["error" => "Failed to connect to Authorize.net", "statusCode" => $httpCode]);
}
?>
