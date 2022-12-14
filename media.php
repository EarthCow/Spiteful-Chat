<?php

// initizing session and verification

if (!isset($_GET['name']) || !isset($_GET['type']) || !isset($_GET['c'])) {
  http_response_code(404);
  // include 404 error doc here
  die();
}

// start the session
session_start();

// check if user is logged in
if (!isset($_SESSION['id']) || !isset($_SESSION['token'])) {
  http_response_code(404);
  // include 404 error doc here
  die();
}

$private = "/WAMP/apache2/gtdocs/spiteful-chat";
require_once("$private/database.php");

$id = $_SESSION['id'];
$token = $_SESSION['token'];

$sql = "SELECT * FROM `profiles` WHERE `id`=?";
$statement = $connection->prepare($sql);
$statement->bind_param("i", $id);
$statement->execute() or die(); // Code select database 10 double digits for distinction
$result = $statement->get_result();

if ($result->num_rows == 0) {
  session_destroy();
  http_response_code(404);
  // include 404 error doc here
  die(); // Code no entry 1
}

$row = $result->fetch_assoc();

if ($row['token'] != $token) {
  session_destroy();
  http_response_code(404);
  // include 404 error doc here
  die(); // Code invalid token 1
}

$token_generated = strtotime($row['token_generated']);
$timeBetween = time() - $token_generated;
if ($timeBetween > 28800) { // 28800 is 8 hours
  session_destroy();
  http_response_code(404);
  // include 404 error doc here
  die();
}

// user and token are verified

$mediaFilename = $_GET["name"];
$mediaMimeType = $_GET["type"];
$c = $_GET["c"];

// to verify I have access we can just check the chats column for the chat name
if ($row["chats"] === null || !is_file("$private/chats/$c") || !str_contains($row["chats"], $c)) {
  http_response_code(404);
  // include 404 error doc here
  die();
}

// check that media file exists
if (!is_file("$private/chats/media/$mediaFilename")) {
  http_response_code(404);
  // include 404 error doc here
  die();
}

$mediaContent = file_get_contents("$private/chats/media/$mediaFilename");

header("Cache-Control: no-cache, must-revalidate");
header("Content-Type: $mediaMimeType");

if (isset($_GET["download"])) {
  $downloadName = (isset($_GET["og"])) ? $_GET["og"] : "file";
  header('Content-Description: File Transfer');
  header("Expires: 0");
  // filename has to be in double quotes (")
  header('Content-Disposition: attachment; filename="' . $downloadName . '"');
  header('Content-Length: ' . filesize("$private/chats/media/$mediaFilename"));
  header('Pragma: public');
}

die($mediaContent);
