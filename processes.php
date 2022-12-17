<?php

use LDAP\Result;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_POST['process']) || !isset($_POST['data'])) {
  header("Location: ."); // Redirects to /spiteful-chat/
  die();
} else {
  // start the session
  session_start();
  // check if user is logged in
  if (!isset($_SESSION['id']) || !isset($_SESSION['token'])) {
    die("SESS");
  } else {
    $private = "/WAMP/apache2/gtdocs/spiteful-chat";
    require_once("$private/database.php");
    $connection = $GLOBALS['connection'];

    $userId = $_SESSION['id'];
    $token = $_SESSION['token'];

    $sql = "SELECT * FROM `profiles` WHERE `user_id`=?";
    $statement = $connection->prepare($sql);
    $statement->bind_param("i", $userId);
    $statement->execute() or die("An error occurred CSDB10"); // Code select database 10 double digits for distinction
    $result = $statement->get_result();

    if ($result->num_rows == 0) {
      session_destroy();
      die("CNE1");// Code no entry 1
    } else {
      $row = $result->fetch_assoc();
      if ($row['token'] != $token) {
        session_destroy();
        die("CIT1");// Code invalid token 1
      } else {
        $token_generated = strtotime($row['token_generated']);
        $timeBetween = time() - $token_generated;
        if ($timeBetween > 28800) { // 28800 is 8 hours
          session_destroy();
          die("SESS");
        } else {
          $process = $_POST["process"];
          $data = $_POST["data"];

          $response = ["ok" => false, "statusText" => "Invalid username"];
          switch ($process) {
            case 'getActiveDms':
              $sql = "
              SELECT chats.chat_id, chats.last_message, chats.modified, profiles.username, profiles.name, profiles.picture
              FROM `chats`
              JOIN `profiles`
              ON IF(chats.sender = $userId, chats.receiver = profiles.user_id, chats.sender = profiles.user_id)
              WHERE chats.sender = $userId OR chats.receiver = $userId
              ORDER BY `modified` DESC;
              ";
              $result = $connection -> query($sql) or die("An error occurred CULA1");// Code update last active 1

              $row = $result->fetch_all(MYSQLI_ASSOC);

              // if there are no rows (chats) then send an empty array
              $response = ["ok" => true, "chats" => (($row === null) ? [] : $row)];
              die(json_encode($response));

              break;

            case 'getMessages':
              // verify the $data variable is not empty
              if (empty($data)) {
                $response["statusText"] = "Invalid Operation";
                die(json_encode($response));
              }
              $receiver = $data;

              // confirm the $receiver is in a valid format
              if (preg_match('/[-!#@$%^&*()_+|~=`{}\[\]:\";\'<>?,.\\\\\/\s]/', $receiver)) {
                $response["statusText"] = "Invalid Operation";
                die(json_encode($response));
              }
              
              // check the db for a user with that username
              $sql = "SELECT * FROM `profiles` WHERE `username`=?";
              $statement = $connection -> prepare($sql);
              $statement -> bind_param("s", $receiver);
              $statement -> execute() or die();
              $result = $statement -> get_result();

              if ($result -> num_rows == 0) {
                $response["statusText"] = "Invalid Operation";
                die(json_encode($response));
              }

              // get the db row for the receiver
              $receiverRow = $result -> fetch_assoc();
              $receiverId = $receiverRow["user_id"];

              // $sql = "SELECT `chat_id` FROM `chats` WHERE (`sender` = $userId AND `receiver` = $receiverId) OR (`sender` = $receiverId AND `receiver` = $userId)";

              // basically instead of finding the chat id in a different query
              // we can use a join statement and a carefully dictated where statement
              // to eliminate the need for an additional query
              $sql = "
              SELECT messages.msg_id, messages.sender, messages.media, messages.content, messages.timestamp
              FROM chats
              JOIN messages
              ON chats.chat_id = messages.chat_id
              WHERE (chats.sender = $userId AND chats.receiver = $receiverId) OR (chats.sender = $receiverId AND chats.receiver = $userId)
              ";

              $result = $connection -> query($sql);

              if (!$result) {
                $response["statusText"] = "Failed to retreive messages";
                die(json_encode($response));
              }

              $messages = $result->fetch_all(MYSQLI_ASSOC);

              foreach ($messages as $messageKey => $messageArr) {
                $messageArr["date"] = date("m/d/Y h:i:s", strtotime($messageArr["timestamp"]));
                unset($messageArr["timestamp"]);
                $messageArr["mine"] = ($messageArr["sender"] == $userId);
                unset($messageArr["sender"]);
                if ($messageArr["media"] !== NULL) {
                  $messageArr["content"] = "earthcow.xyz/spiteful-chat/media?id=" . $messageArr["msg_id"];
                } else unset($messageArr["media"]);
                $messages[$messageKey] = $messageArr;
              }

              $response = ["ok" => true, "messages" => $messages];
              die(json_encode($response));

              break;
            
            case 'sendMessage':
              if (empty($data)) {
                $response["statusText"] = "Invalid Operation";
                die(json_encode($response));
              }
              
              list($receiver, $message) = json_decode($data, true);

              if (preg_match('/[-!#@$%^&*()_+|~=`{}\[\]:\";\'<>?,.\\\\\/\s]/', $receiver)) {
                $response["statusText"] = "Invalid Operation";
                die(json_encode($response));
              }
              
              $sql = "SELECT * FROM `profiles` WHERE `username`=?";
              $statement = $connection -> prepare($sql);
              $statement -> bind_param("s", $receiver);
              $statement -> execute();
              $result = $statement -> get_result();

              if ($result -> num_rows == 0) {
                $response["statusText"] = "Invalid Operation";
                die(json_encode($response));
              }

              $receiverRow = $result -> fetch_assoc();
              $receiverId = $receiverRow["user_id"];

              $sql = "SELECT chat_id FROM chats WHERE (sender = $userId AND receiver = $receiverId) OR (sender = $receiverId AND receiver = $userId)";
              $result = $connection -> query($sql);

              if ($result -> num_rows != 1) {
                $response["statusText"] = "Invalid Operation";
                die(json_encode($response));
              }

              $row = $result ->fetch_assoc();
              $chatId = $row["chat_id"];

              $sql = "INSERT INTO `messages` (chat_id, sender, content) VALUES ($chatId, $userId, ?)";
              $statement = $connection -> prepare($sql);
              $statement -> bind_param("s", $message);
              $statement -> execute();

              $sql = "UPDATE chats SET last_message = ? WHERE chat_id = $chatId";
              $statement = $connection -> prepare($sql);
              $statement -> bind_param("s", $message);
              $statement -> execute();

              $response = ["ok" => true, "lm" => date("m/d/Y h:i:s")];
              die(json_encode($response));

              break;

            case 'sendFile';
              $recipient = $data;
              if (preg_match('/[-!#@$%^&*()_+|~=`{}\[\]:\";\'<>?,.\\\\\/\s]/', $recipient)) {
                $response["statusText"] = "Invalid Operation";
                die(json_encode($response));
              }
              
              $sql = "SELECT * FROM `profiles` WHERE `username`=?";
              $statement = $connection -> prepare($sql);
              $statement -> bind_param("s", $recipient);
              $statement -> execute() or die();
              $result = $statement -> get_result();

              if ($result -> num_rows == 0) {
                $response["statusText"] = "Invalid Operation";
                die(json_encode($response));
              }

              $recipientRow = $result -> fetch_assoc();

              if(!isset($_FILES['file'])){
                $response["statusText"] = "No file was recieved";
                die(json_encode($response));
              }
              if($_FILES['file']['error'] !== 0){
                $response["statusText"] = "A transfer error occurred with error code: " . $_FILES['file']['error'];
                die(json_encode($response));
              }
              $mediaFilename = uniqid(rand(), true);
              if(!rename($_FILES['file']['tmp_name'], "$private/chats/media/$mediaFilename")){
                $response["statusText"] = "Failed to retrieve file" . $_FILES['file']['type'];
                die(json_encode($response));
              }

              $chats = json_decode($row["chats"], true);

              if (!isset($chats[$recipientRow["id"]])) {
                $response["statusText"] = "Unknown";
                die(json_encode($response));
              }
              
              $theChat = $chats[$recipientRow["id"]];
              $recipientChats = json_decode($recipientRow["chats"], true);

              $recipientChats[$row["id"]]["lastMessage"] = $_FILES['file']['name'];
              $chats[$recipientRow["id"]]["lastMessage"] = $_FILES['file']['name'];
              $message = ";$id:".time().":$mediaFilename:" . (($_FILES['file']['type'] == "video/quicktime") ? "video/mp4" : $_FILES['file']['type']) . ":" . base64_encode($_FILES['file']['name']);

              $myfile = file_put_contents("$private/chats/".$theChat["filename"], $message.PHP_EOL , FILE_APPEND | LOCK_EX);

              $lm = date("m/d/Y h:i:s", $timestamp = time());
              $recipientChats[$row["id"]]["lastModified"] = $lm;
              $chats[$recipientRow["id"]]["lastModified"] = $lm;

              $recipientChats[$row["id"]]["lastModifiedTime"] = $timestamp;
              $chats[$recipientRow["id"]]["lastModifiedTime"] = $timestamp;

              $chatsStr = json_encode($chats);
              $recipientChatsStr = json_encode($recipientChats);

              $sql = "UPDATE `profiles` SET `chats`=? WHERE id=?";
              $statement = $connection -> prepare($sql);

              // update recievers chat db
              $statement -> bind_param("si", $chatsStr, $id);
              $statement -> execute() or die();

              // update recipients chat db
              $statement -> bind_param("si", $recipientChatsStr, $recipientRow["id"]);
              $statement -> execute() or die();

              $src = "https://earthcow.xyz/spiteful-chat/media?name=$mediaFilename&type=".urlencode($_FILES['file']['type'])."&og=".urlencode($_FILES['file']['name'])."&c=".$theChat["filename"];

              $response = ["ok" => true, "lm" => $lm, "src" => $src, "type" => $_FILES['file']['type'], "og" => $_FILES['file']['name']];
              die(json_encode($response));

              break;
            
            case 'updateUsername':
              if (empty($data) || preg_match('/[-!#@$%^&*()_+|~=`{}\[\]:\";\'<>?,.\\\\\/\s]/', $data)) {
                $response["statusText"] = "Invalid username";
                die(json_encode($response));
              }
              $sql = "SELECT * FROM `profiles` WHERE `username`=?";
              $statement = $connection -> prepare($sql);
              $statement -> bind_param("s", $data);
              $statement -> execute() or die();
              $result = $statement -> get_result();

              if ($result -> num_rows > 0) {
                $response["statusText"] = "Be more original dude c'mon";
                die(json_encode($response));
              }
              $sql = "UPDATE `profiles` SET `username`=? WHERE `user_id`=$userId";
              $statement = $connection -> prepare($sql);
              $statement -> bind_param("s", $data);
              $statement -> execute();

              $result = $statement -> get_result();

              $response = ["ok" => true, "username" => $data, "subtleText" => "nuts"];
              die(json_encode($response));

              break;

            case "newRecipient":
              if (empty($data) || preg_match('/[-!#@$%^&*()_+|~=`{}\[\]:\";\'<>?,.\\\\\/\s]/', $data)) {
                $response["statusText"] = "Invalid username";
                die(json_encode($response));
              }
              
              if (strcasecmp($data, $row["username"]) == 0) {
                $response["statusText"] = "Fucking dumbass no you cannot message yourself lonely ass";
                die(json_encode($response));
              }
              $sql = "SELECT `user_id`, `username`, `name`, `picture` FROM `profiles` WHERE `username`=?";
              $statement = $connection -> prepare($sql);
              $statement -> bind_param("s", $data);
              $statement -> execute() or die();
              $result = $statement -> get_result();

              if ($result -> num_rows == 0) {
                $response["statusText"] = "Couldn't find who you're looking for";
                die(json_encode($response));
              }

              $receiverRow = $result -> fetch_assoc();
              $receiverId = $receiverRow["user_id"];

              $sql = "SELECT `chat_id` FROM `chats` WHERE `sender` = $receiverId OR `receiver` = $receiverId";
              $result = $connection -> query($sql);

              if ($result -> num_rows != 0) {
                $response = ["ok" => true, "receiver" => ["username" => $receiverRow["username"]],"alreadyExists" => true];
                die(json_encode($response));
              }

              $sql = "INSERT INTO `chats` (`sender`, `receiver`) VALUES ($userId, $receiverId);";
              $result = $connection -> query($sql);

              if (!$result) {
                $response = ["statusText" => "Failed to created a new conversation"];
                die(json_encode($response));
              } else {
                $response = ["ok" => true, "receiver" => $receiverRow];
                die(json_encode($response));
              }

              break;
            
            case "administrator":
              // restrict access only to administrators
              $administrators = [1];
              if (!in_array($userId, $administrators)) {
                $response["statusText"] = "Invalid request :(";
                die(json_encode($response));
              }

              $request = json_decode($data, true);

              if ($request["request"] == "clear") {
                $connection -> query("UPDATE `profiles` SET chats = NULL");

                $deletedFileList = [];
                $files = glob("$private/chats/*");
                array_push($deletedFileList, $files);
                foreach($files as $file){
                  if(is_file($file)) {
                    unlink($file);
                  }
                }
                $files = glob("$private/chats/media/*");
                array_push($deletedFileList, $files);
                foreach($files as $file){
                  if(is_file($file)) {
                    unlink($file);
                  }
                }

                $response = ["ok" => true, "statusText" => "Data cleared", "files" => $deletedFileList];
                die(json_encode($response));
              }

              break;
            
            default:
              $response["statusText"] = "Invalid request :(";
              die(json_encode($response));

              break;
          }
        }
      }
    }
  }
}
