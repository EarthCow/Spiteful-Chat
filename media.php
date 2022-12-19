<?php

// initizing session and verification

if (!isset($_GET["id"])) {
  http_response_code(404);
  // include 404 error doc here
  die();
}

// start the session
session_start();

// check if user is logged in
if (!isset($_SESSION["id"]) || !isset($_SESSION["token"])) {
  http_response_code(404);
  // include 404 error doc here
  die();
}

$private = "/WAMP/apache2/gtdocs/spiteful-chat";
require_once("$private/database.php");

$userId = $_SESSION["id"];
$token = $_SESSION["token"];

$sql = "SELECT * FROM `profiles` WHERE `user_id`=?";
$statement = $connection->prepare($sql);
$statement->bind_param("i", $userId);
$statement->execute() or die(); // Code select database 10 double digits for distinction
$result = $statement->get_result();

if ($result->num_rows == 0) {
  session_destroy();
  http_response_code(404);
  // include 404 error doc here
  die(); // Code no entry 1
}

$row = $result->fetch_assoc();

if ($row["token"] != $token) {
  session_destroy();
  http_response_code(404);
  // include 404 error doc here
  die(); // Code invalid token 1
}

$token_generated = strtotime($row["token_generated"]);
$timeBetween = time() - $token_generated;
if ($timeBetween > 28800) { // 28800 is 8 hours
  session_destroy();
  http_response_code(404);
  // include 404 error doc here
  die();
}

// user and token are verified

$msgId = $_GET["id"];

$sql = "
SELECT media.*, chats.sender, chats.receiver FROM `media`
  JOIN messages
    ON media.msg_id = messages.msg_id
  JOIN chats
    ON messages.chat_id = chats.chat_id
WHERE media.msg_id = ?
";
$statement = $connection -> prepare($sql);
$statement -> bind_param("i", $msgId);
$statement -> execute();

$result = $statement -> get_result();

if ($result->num_rows == 0) {
  http_response_code(404);
  // include 404 error doc here
  die();
}

$row = $result -> fetch_assoc();

if ($userId != $row["sender"] && $userId != $row["receiver"]) {
  http_response_code(404);
  // include 404 error doc here
  die();
}

// check that media file exists
if (!is_file("$private/chats/media/" . $row["filename"])) {
  http_response_code(404);
  // include 404 error doc here
  die();
}

$mediaContent = file_get_contents("$private/chats/media/" . $row["filename"]);

header("Cache-Control: no-cache, must-revalidate");
header("Content-Type: " . $row["type"]);

if (isset($_GET["download"])) {
  header('Content-Description: File Transfer');
  header("Expires: 0");
  // filename has to be in double quotes (")
  header('Content-Disposition: attachment; filename="' . $row["original"] . '"');
  header('Content-Length: ' . filesize("$private/chats/media/" . $row["filename"]));
  header('Pragma: public');
}

die($mediaContent);
