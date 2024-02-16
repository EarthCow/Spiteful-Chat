<?php

// Verify file is not being accessed from the browser
if (basename(__FILE__) == basename($_SERVER["SCRIPT_FILENAME"])) {
  http_response_code(404);
  die();
}

/* CONFIGURATION */

if (!isset($debug)) {
  $debug = true; // Enable PHP error_reporting
}

/* TIME DEFINITIONS */

define("TIME_SECOND", 1);
define("TIME_MINUTE", 60);
define("TIME_HOUR", 60 * 60);
define("TIME_DAY", 24 * 60 * 60);
define("TIME_WEEK", 7 * 24 * 60 * 60);

//$language = "en_US"; // Explicitly set language locale. Remove or comment out to auto detect.

/*

  This requires PHP Locale class.
  sudo apt-get install php-intl
  
  https://stackoverflow.com/questions/18346531/how-to-enable-php-locale-class
  
  Other languages may have rogue locales.
  https://superuser.com/questions/1519501/what-exactly-do-the-three-special-locales-called-en-us-posix-en-001-and
  
*/

$maintenance = false;

$chatRoot = "/spiteful-chat/"; // Public directory root of the project
$privateFolder = "/private/spiteful-chat"; // Private directory location from directory behind DOCUMENT_ROOT
$composerFolder = "./vendor"; // PHP Composer Directory
$nodeModulesFolder = "./node_modules"; // NPM Modules Directory
$spiteSocketServerHost = "127.0.0.1"; // Spiteful Server Websocket Host
$spiteSocketServerPort = "12345"; // Spiteful Server Websocket Port

$loginSessionLength = TIME_HOUR * 8; // How long the session should last

if (php_sapi_name() === "cli") {
  $privateFolder = "../..$privateFolder"; // Assumes command is being run from /html/spiteful-chat/
} else {
  $tfolder = explode("/", $_SERVER["DOCUMENT_ROOT"]);
  $tfolder = array_filter($tfolder);
  array_pop($tfolder);
  $tfolder = implode("/", $tfolder);
  $privateFolder = "/$tfolder$privateFolder";
  unset($tfolder);
}

/* CUSTOM FUNCTIONS */

if ($debug === true) {
  error_reporting(E_ALL);
  ini_set("display_errors", 1);
}

require_once "$composerFolder/autoload.php";
require_once "$privateFolder/private-variables.php";

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function createSession($userId, $token)
{
  global $loginSessionLength, $sessionKey;

  $iat = time();
  $exp = $iat + $loginSessionLength;
  $payload = [
    'iss' => $_SERVER["SERVER_NAME"],
    'aud' => "Spiteful-Chat",
    'iat' => $iat,
    'exp' => $exp,
    'sub' => $userId,
    'token' => $token
  ];

  $jwt = JWT::encode($payload, $sessionKey, 'HS256');
  setcookie("SPITESESS", $jwt, $exp, "/", null, true);
}

function verifySession($database = false, $jwt = null)
{
  global $sessionKey, $loginSessionLength, $privateFolder;

  if (!isset($jwt) && !isset($_COOKIE["SPITESESS"])) {
    return false;
  }

  $jwt = isset($jwt) ? $jwt : $_COOKIE["SPITESESS"];
  try {
    $payload = JWT::decode($jwt, new Key($sessionKey, 'HS256'));
  } catch (Exception $e) {
    return false;
  }

  if (!$database)
    return (array) $payload;
  
  require_once("$privateFolder/database.php");
  $connection = $GLOBALS["connection"];

  $sql = "SELECT * FROM `profiles` WHERE `user_id`=?";
  $statement = $connection->prepare($sql);
  $statement->bind_param("i", $payload->sub);
  $statement->execute() or die(word("error-occurred") . " CSDB10"); // Code select database 10 double digits for distinction
  $result = $statement->get_result();

  if ($result->num_rows == 0) {
    return false;
  }

  $row = $result->fetch_assoc();

  if ($row["token"] != $payload->token) {
    return false;
  }

  $token_generated = strtotime($row["token_generated"]);
  $timeBetween = time() - $token_generated;

  if ($timeBetween > $loginSessionLength) {
    return false;
  }

  return array_merge((array) $payload, (array) $row);
}

function logout($redirect = true)
{
  global $chatRoot;
  if (isset($_COOKIE["SPITESESS"])) {
    setcookie("SPITESESS", ".", 1, "/"); // Destroys the cookie
  }
  if ($redirect)
    header("Location: $chatRoot");
}

function fileLink($file)
{
  // Supply a file URL along with a timestamp. Must be a local file. Assumes no existing ?s or will error as ? is not in file path.
  $tfile = explode("?", $file);
  return $tfile[0] . "?" . filemtime($tfile[0]);
  unset($tfile);
}

function metaTags($tabs = 0)
{
  // Alternative to doing head/foot.php
  $first = true;
  $metaTags = [
    "<!--[if lt IE 9]>",
    "\t<script src=\"//cdnjs.cloudflare.com/ajax/libs/html5shiv/r29/html5.min.js\"></script>",
    "<![endif]-->",
    "<meta name=\"viewport\" content=\"width=device-width,initial-scale=1,shrink-to-fit=no\" />",
    "<meta name=\"apple-mobile-web-app-capable\" content=\"yes\" />",
    "<meta charset=\"utf-8\" />"
  ];
  foreach ($metaTags as $tag) {
    for ($i = 0; $i < $tabs; $i++) {
      if ($first === false) {
        echo "\t";
      }
    }
    $first = false;
    echo "$tag\n";
  }
}
