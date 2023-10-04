<?php

require_once "./configuration.php";
require_once "./languages.php";

// Continue with Google

// Verify requested information is received
if (!isset($_POST["credential"])) {
  header("HTTP/1.0 404 Not Found");
  // need to include a 404 document here but for now I will use redirect
  header("Location: ./404");
  die();
}

require_once "$composerFolder/autoload.php";

// Get $id_token via HTTPS POST.

$id_token = $_POST["credential"];

$client = new Google_Client(["client_id" => $googleClientId]);  // Specify the CLIENT_ID of the app that accesses the backend
$payload = $client->verifyIdToken($id_token);

if (!$payload) {

  // Invalid ID token
  die("An error as occurred CGIDT"); // Code google id token

  // This is where the session would get invalidated and the user needs to login again

}

require_once("$privateFolder/database.php");
$connection = $GLOBALS["connection"];

$google_id = $payload["sub"];
$sql = "SELECT * FROM `profiles` WHERE `google_id`='$google_id'";
$result = $connection->query($sql) or die(word("error-occurred") . " CSDB1"); // Code select database 1

$token = bin2hex(random_bytes(32));
if ($result->num_rows == 0) {
  // if there is no entry in the database with the google id then create one
  $name = $payload["name"];
  $email = $payload["email"];
  $email_verified = $payload["email_verified"];
  $picture = $payload["picture"];

  $sql = "INSERT INTO `profiles` (`google_id`, `name`, `email`, `email_verified`, `picture`, `token`) VALUES ('$google_id', '$name', '$email', $email_verified, '$picture', '$token')";
  $connection->query($sql) or die(word("error-occurred") . " CIDB1"); // Code insert database 1
} else {
  // if there is an entry then log the user in
  $sql = "UPDATE `profiles` SET `token`='$token', `token_generated`=CURRENT_TIMESTAMP WHERE `google_id`='$google_id'";
  $connection->query($sql) or die(word("error-occurred") . " CUDB1"); // Code update database 1
}

$sql = "SELECT * FROM `profiles` WHERE `google_id`='$google_id'";
$result = $connection->query($sql) or die(word("error-occurred") . " CIDB1"); // Code insert database 1

if ($result->num_rows == 0) {
  die(word("error-occurred") . " CDBSK"); // Code database select known
}

$row = $result->fetch_assoc();

createSession($row["user_id"], $token);

header("Location: ./dashboard");
