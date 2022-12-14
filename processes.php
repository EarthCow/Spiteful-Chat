<?php

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

    $id = $_SESSION['id'];
    $token = $_SESSION['token'];

    $sql = "SELECT * FROM `profiles` WHERE `id`=?";
    $statement = $connection->prepare($sql);
    $statement->bind_param("i", $id);
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
              if ($row["chats"] !== null) {
                $chats = json_decode($row["chats"], true);
              } else {
                $chats = [];
              }
            
              $response = ["ok" => true, "chats" => $chats];
              die(json_encode($response));

              break;

            case 'getMessages':
              // verify the $data variable is not empty
              if (empty($data)) {
                $response["statusText"] = "Invalid Operation";
                die(json_encode($response));
              }
              $recipient = $data;

              // confirm the $recipient is in a valid format
              if (preg_match('/[-!#@$%^&*()_+|~=`{}\[\]:\";\'<>?,.\\\\\/\s]/', $recipient)) {
                $response["statusText"] = "Invalid Operation";
                die(json_encode($response));
              }
              
              // check the db for a user with that username
              $sql = "SELECT * FROM `profiles` WHERE `username`=?";
              $statement = $connection -> prepare($sql);
              $statement -> bind_param("s", $recipient);
              $statement -> execute() or die();
              $result = $statement -> get_result();

              if ($result -> num_rows == 0) {
                $response["statusText"] = "Invalid Operation";
                die(json_encode($response));
              }

              // get the db row for the recipient
              $recipientRow = $result -> fetch_assoc();

              $theChat = json_decode($row["chats"], true)[$recipientRow["id"]];

              $chatFileSize = filesize("$private/chats/".$theChat["filename"]);

              $messagesJson = [];

              // false is failure 0 is also false but not a failure must use "==="
              if ($chatFileSize === false) {
                $response["statusText"] = "Failed to retrieve information";
                die(json_encode($response));
              } elseif ($chatFileSize) {
                $theChatFile = fopen("$private/chats/".$theChat["filename"], "r");
                $messages = fread($theChatFile, $chatFileSize);
  
                // this is an array of messages "4:231323:content:..."
                $messagesArr = explode(";", $messages);
  
                // removes the first ""
                array_shift($messagesArr);
  
                // an array of the messages further broken down into arrays
                $messagesJson = [];
  
                foreach ($messagesArr as $message) {
                  if (count($messageArr = explode(":", $message)) < 4) {
                    list($senderId, $timestamp, $content) = $messageArr;
                    $date = date("m/d/Y h:i:s", $timestamp);
                    $mine = ($senderId == $id);
                    $message = [
                      "mine"    => $mine,
                      "date"    => $date,
                      "content" => htmlentities(base64_decode($content))
                    ];
                    array_push($messagesJson, $message);
                  } else {
                    list($senderId, $timestamp, $mediaFilename, $mediaMimeType, $originalFilename) = $messageArr;
                    $date = date("m/d/Y h:i:s", $timestamp);
                    $mine = ($senderId == $id);
                    $message = [
                      "mine"     => $mine,
                      "date"     => $date,
                      "content"  => "https://earthcow.xyz/spiteful-chat/media?name=$mediaFilename&type=".urlencode($mediaMimeType)."&og=".urlencode(base64_decode($originalFilename))."&c=".$theChat["filename"],
                      "type"     => $mediaMimeType,
                      "og"       => base64_decode($originalFilename)
                    ];
                    array_push($messagesJson, $message);
                  }
                }
              }

              $response = ["ok" => true, "messages" => $messagesJson];
              die(json_encode($response));


              break;
            
            case 'sendMessage':
              if (empty($data)) {
                $response["statusText"] = "Invalid Operation";
                die(json_encode($response));
              }
              
              list($recipient, $message) = json_decode($data, true);

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

              $chats = json_decode($row["chats"], true);

              if (!isset($chats[$recipientRow["id"]])) {
                $response["statusText"] = "Unknown";
                die(json_encode($response));
              }
              
              $theChat = $chats[$recipientRow["id"]];
              $recipientChats = json_decode($recipientRow["chats"], true);

              $recipientChats[$row["id"]]["lastMessage"] = $message;
              $chats[$recipientRow["id"]]["lastMessage"] = $message;
              $message = ";$id:".time().":".base64_encode($message);

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

              $response = ["ok" => true, "lm" => $lm];
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
              $sql = "UPDATE `profiles` SET `username`=? WHERE `id`=$id";
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
              $sql = "SELECT * FROM `profiles` WHERE `username`=?";
              $statement = $connection -> prepare($sql);
              $statement -> bind_param("s", $data);
              $statement -> execute() or die();
              $result = $statement -> get_result();

              if ($result -> num_rows == 0) {
                $response["statusText"] = "Couldn't find who you're looking for";
                die(json_encode($response));
              }

              $recipientRow = $result -> fetch_assoc();
              $recipientId = $recipientRow["id"];

                // this is representing the chat between the recipient and the sender
                $theChat = false;
                // see if the sender's column chat is not null
                if ($row["chats"] !== null) {
                  $chats = json_decode($row["chats"], true);
                  if (isset($chats[$recipientId])) {
                    $theChat = $chats[$recipientId];
                  }
                } else {
                  $chats = [];
                }

                $alreadyExists = false;
                if ($theChat === false) {
                  $chatFilename = uniqid(rand(), true);
                  // this is the key that will be used to decrypt the chat file it must be stored in base64 or otherwise the json cannot encode
                  $chatFileKey = base64_encode(openssl_random_pseudo_bytes(128));
                  $lm = date("m/d/Y h:i:s", $timestamp = time());
                  $chats[$recipientId] = [
                      "filename"         => $chatFilename,
                      // "key"            => $chatFileKey,
                      "recipient"        => $recipientRow["username"],
                      "recipientName"    => $recipientRow["name"],
                      "recipientImage"   => $recipientRow["picture"],
                      "lastMessage"      => null,
                      "lastModified"     => $lm,
                      "lastModifiedTime" => $timestamp
                  ];

                  if (!$chatFile = fopen("$private/chats/$chatFilename", "w")) {
                    $response["statusText"] = "Failed to dedicate new chat file";
                    die(json_encode($response));
                  }
                
                  fclose($chatFile);

                  $recipientChats = [];
                  if ($recipientRow["chats"] !== null) {
                    $recipientChats = json_decode($recipientRow["chats"], true);
                  }
                  $recipientChats[$id] = [
                      "filename"         => $chatFilename,
                      // "key"            => $chatFileKey,
                      "recipient"        => $row["username"],
                      "recipientName"    => $row["name"],
                      "recipientImage"   => $row["picture"],
                      "lastMessage"      => null,
                      "lastModified"     => $lm,
                      "lastModifiedTime" => $timestamp
                  ];

                  $sql = "UPDATE `profiles` SET `chats`=? WHERE id=?";
                  $statement = $connection -> prepare($sql);
                  
                  $strChats = json_encode($chats);
                  // update my chat db
                  $statement -> bind_param("si", $strChats, $id);
                  $statement -> execute() or die();

                  $strRecChats = json_encode($recipientChats);
                  // update recipients chat db
                  $statement -> bind_param("si", $strRecChats, $recipientId);
                  $statement -> execute() or die();

                } else {
                  $chatFilename = $theChat;
                  $alreadyExists = true;
                }



              

              $response = ["ok" => true, "chat" => $chats[$recipientId], "alreadyExists" => $alreadyExists];
              die(json_encode($response));


              break;
            
            case "administrator":
              // restrict access only to administrators
              $administrators = [1];
              if (!in_array($id, $administrators)) {
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
