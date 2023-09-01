<?php

// Continue with Google

// Verify requested information is received
if (!isset($_POST['credential'])) {
  header("HTTP/1.0 404 Not Found");
  // need to include a 404 document here but for now I will use redirect
  header("Location: /404");
  die();
} else {
  
  require_once 'vendor/autoload.php';

  // Get $id_token via HTTPS POST.

  $id_token = $_POST["credential"];

  require_once($_SERVER['DOCUMENT_ROOT'] . "/../private/spiteful-chat/google-client-id.php");

  $client = new Google_Client(['client_id' => $google_client_id]);  // Specify the CLIENT_ID of the app that accesses the backend
  $payload = $client->verifyIdToken($id_token);
  if (!$payload) {

    // Invalid ID token
    die("An error as occurred CGIDT");// Code google id token

    // this is where the session would get invalidated and the user needs to login again

  } else {

    require_once($_SERVER['DOCUMENT_ROOT'] . "/../private/spiteful-chat/database.php");
    $connection = $GLOBALS["connection"];

    $google_id = $payload['sub'];
    $sql = "SELECT * FROM `profiles` WHERE `google_id`='$google_id'";
    $result = $connection -> query($sql) or die("An error occurred CSDB1");// Code select database 1
    
    $token = base64_encode(openssl_random_pseudo_bytes(128));
    if($result -> num_rows == 0){
      // if there is no entry in the database with the google id then create one
      $name = $payload['name'];
      $email = $payload['email'];
      $email_verified = $payload['email_verified'];
      $picture = $payload['picture'];
      
      $sql = "INSERT INTO `profiles` (`google_id`, `name`, `email`, `email_verified`, `picture`, `token`) VALUES ('$google_id', '$name', '$email', $email_verified, '$picture', '$token')";
      $connection -> query($sql) or die("An error occurred CIDB1");// Code insert database 1

    } else {
      // if there is an entry then log the user in
      $sql = "UPDATE `profiles` SET `token`='$token', `token_generated`=CURRENT_TIMESTAMP WHERE `google_id`='$google_id'";
      $connection -> query($sql) or die("An error occurred CUDB1");// Code update database 1
    }

    $sql = "SELECT * FROM `profiles` WHERE `google_id`='$google_id'";
    $result = $connection -> query($sql) or die("An error occurred CIDB1");// Code insert database 1
    if($result -> num_rows == 0){

      die("An error occurred CDBSK");// Code database select known

    } else {
      $row = $result -> fetch_assoc();

      session_start([
        'cookie_lifetime' => 28800, // 8 hours
      ]);
      $_SESSION["id"] = $row["user_id"];
      $_SESSION["token"] = $token;
      header("Location: dashboard");
    }

    
  }
}