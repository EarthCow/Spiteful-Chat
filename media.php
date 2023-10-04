<?php

require_once "./configuration.php";

// Verification

if (!isset($_GET["id"])) {
  http_response_code(404);
  // Include 404 error document here
  die();
}

// Check if user is logged in

$payload = verifySession(true);
if (!$payload) {
  logout(false);
  http_response_code(404);
  // Include 404 error document here
  die();
}
$userId = $payload["sub"];

// User and token are verified

$msgId = $_GET["id"];

$sql = "
  SELECT media.*, chats.sender, chats.receiver FROM `media`
    JOIN messages
      ON media.msg_id = messages.msg_id
    JOIN chats
      ON messages.chat_id = chats.chat_id
  WHERE media.msg_id = ?
";
$statement = $connection->prepare($sql);
$statement->bind_param("i", $msgId);
$statement->execute();

$result = $statement->get_result();

if ($result->num_rows == 0) {
  http_response_code(404);
  // Include 404 error document here
  die();
}

$row = $result->fetch_assoc();

if ($userId != $row["sender"] && $userId != $row["receiver"]) {
  http_response_code(404);
  // Include 404 error document here
  die();
}

$file = "$privateFolder/chats/media/" . $row["filename"];

// Check that media file exists
if (!is_file($file)) {
  http_response_code(404);
  // Include 404 error document here
  die();
}

// Must revalidate (remove for production)
header("Cache-Control: no-cache, must-revalidate");

// Ranges for resumable downloads and video seeking https://www.sitepoint.com/community/t/loading-html5-video-with-php-chunks-or-not/350957/3
$fileStream = fopen($file, 'rb');
$size   = filesize($file); // File size
$length = $size;           // Content length
$start  = 0;               // Start byte
$end    = $size - 1;       // End byte
header("Content-Type: " . $row["type"]);
header("Accept-Ranges: bytes");

// Handles partial content requests
if (isset($_SERVER['HTTP_RANGE'])) {
  $c_start = $start;
  $c_end   = $end;
  list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
  if (strpos($range, ',') !== false) {
    header('HTTP/1.1 416 Requested Range Not Satisfiable');
    header("Content-Range: bytes $start-$end/$size");
    exit;
  }
  if ($range == '-') {
    $c_start = $size - substr($range, 1);
  } else {
    $range  = explode('-', $range);
    $c_start = $range[0];
    $c_end   = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $size;
  }
  $c_end = ($c_end > $end) ? $end : $c_end;
  if ($c_start > $c_end || $c_start > $size - 1 || $c_end >= $size) {
    header('HTTP/1.1 416 Requested Range Not Satisfiable');
    header("Content-Range: bytes $start-$end/$size");
    exit;
  }
  $start  = $c_start;
  $end    = $c_end;
  $length = $end - $start + 1;
  fseek($fileStream, $start);
  header('HTTP/1.1 206 Partial Content');
}

if (isset($_GET["download"])) {
  header("Content-Description: File Transfer");
  header("Expires: 0");
  // Filename has to be in double quotes (")
  header(
    "Content-Disposition: attachment; filename=\"" . $row["original"] . "\""
  );
  header(
    "Content-Length: $size"
  );
  header("Pragma: public");
}

header("Content-Range: bytes $start-$end/$size");
header("Content-Length: " . $length);
$buffer = 1024 * 8;
$s = 0;
while (!feof($fileStream) && ($p = ftell($fileStream)) <= $end) {
  if ($p + $buffer > $end) {
    $buffer = $end - $p + 1;
  }
  $s++;
  
  echo fread($fileStream, $buffer);
  if ($s >= 250) {
    ob_clean();
    ob_flush();
    flush();
    break;
    
  } else {
    flush();
  }
}
fclose($fileStream);
exit();
