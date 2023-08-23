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
    $private = "/var/www/private/spiteful-chat";
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
            case 'getChats':
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
              SELECT messages.msg_id, messages.sender, messages.content, messages.timestamp, media.original, media.type
              FROM messages
              	LEFT JOIN media
                  ON messages.msg_id = media.msg_id
                JOIN chats
                  ON chats.chat_id = messages.chat_id
              WHERE (chats.sender = $userId AND chats.receiver = $receiverId) OR (chats.sender = $receiverId AND chats.receiver = $userId)
              ORDER BY messages.msg_id
              ";

              $result = $connection -> query($sql);

              if (!$result) {
                $response["statusText"] = "Failed to retrieve messages";
                die(json_encode($response));
              }

              $messages = $result->fetch_all(MYSQLI_ASSOC);

              foreach ($messages as $messageKey => $messageArr) {
                $messageArr["date"] = date("m/d/Y h:i:s", strtotime($messageArr["timestamp"]));
                unset($messageArr["timestamp"]);
                $messageArr["mine"] = ($messageArr["sender"] == $userId);
                unset($messageArr["sender"]);
                if ($messageArr["content"] === NULL) {
                  $messageArr["content"] = "media?id=" . $messageArr["msg_id"];
                }

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

              $insertedId = $statement -> get_result();

              $sql = "UPDATE chats SET last_message = ? WHERE chat_id = $chatId";
              $statement = $connection -> prepare($sql);
              $statement -> bind_param("s", $message);
              $statement -> execute();

              $response = ["ok" => true, "lm" => date("m/d/Y h:i:s"), "id" => $insertedId];
              die(json_encode($response));

              break;

            case 'sendFile';
              $receiver = $data;
              if (preg_match('/[-!#@$%^&*()_+|~=`{}\[\]:\";\'<>?,.\\\\\/\s]/', $receiver)) {
                $response["statusText"] = "Invalid Operation";
                die(json_encode($response));
              }
              
              $sql = "SELECT * FROM `profiles` WHERE `username`=?";
              $statement = $connection -> prepare($sql);
              $statement -> bind_param("s", $receiver);
              $statement -> execute() or die();
              $result = $statement -> get_result();

              if ($result -> num_rows == 0) {
                $response["statusText"] = "Invalid Operation";
                die(json_encode($response));
              }

              $receiverRow = $result -> fetch_assoc();
              $receiverId = $receiverRow["user_id"];

              if(!isset($_FILES['file'])){
                $response["statusText"] = "No file was received";
                die(json_encode($response));
              }
              if($_FILES['file']['error'] !== 0){
                $response["statusText"] = "A transfer error occurred with error code: " . $_FILES['file']['error'];
                die(json_encode($response));
              }
              $filename = uniqid(rand(), true);
              if(!rename($_FILES['file']['tmp_name'], "$private/chats/media/$filename")){
                $response["statusText"] = "Failed to retrieve file";
                die(json_encode($response));
              }

              $sql = "SELECT chat_id FROM chats WHERE (sender = $userId AND receiver = $receiverId) OR (sender = $receiverId AND receiver = $userId)";
              $result = $connection -> query($sql);

              if ($result -> num_rows != 1) {
                $response["statusText"] = "Invalid Operation";
                die(json_encode($response));
              }

              $row = $result -> fetch_assoc();
              $chatId = $row["chat_id"];

              $typeName = explode("/", $_FILES['file']['type'])[0];

              if ($typeName == "video" || $typeName == "quicktime") {
                $msgTxt = "Sent a video";
              } elseif ($typeName == "image") {
                $msgTxt = "Sent an image";
              } else {
                $msgTxt = "Sent a file";
              }

              $sql = "INSERT INTO `messages` (chat_id, sender) VALUES ($chatId, $userId)";
              $result = $connection -> query($sql);

              // this gets the auto_increment id which apparently has always been there
              // another thing to note is this id is specific to this connection
              // and therefore if another message is sent this will still retreive *this* id
              $msgId = $connection -> insert_id;

              $sql = "INSERT INTO `media` (`msg_id`, `filename`, `original`, `type`) VALUES ($msgId, ?, ?, ?)";
              $statement = $connection -> prepare($sql);
              $statement -> bind_param("sss", $filename, $_FILES['file']['name'], $_FILES['file']['type']);
              $statement -> execute();

              $sql = "UPDATE chats SET last_message = '$msgTxt' WHERE chat_id = $chatId";
              $result = $connection -> query($sql);

              $response = ["ok" => true, "date" => date("m/d/Y h:i:s"), "src" => "media?id=" . $msgId, "type" => $_FILES['file']['type'], "original" => $_FILES['file']['name'], "lastMsg" => $msgTxt];
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

            case "newChat":
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

              $sql = "SELECT `chat_id` FROM `chats` WHERE (sender = $userId AND receiver = $receiverId) OR (sender = $receiverId AND receiver = $userId)";
              $result = $connection -> query($sql);

              if ($result -> num_rows != 0) {
                $response = ["ok" => true, "receiver" => ["username" => $receiverRow["username"]], "alreadyExists" => true];
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
                $sql = "TRUNCATE TABLE media;TRUNCATE TABLE messages;TRUNCATE TABLE chats;";
                $result = $connection -> multi_query($sql);

                $files = glob("$private/chats/media/*");
                foreach($files as $file){
                  if(is_file($file)) {
                    unlink($file);
                  }
                }

                die(var_dump($result, $files));
              }

              break;

            case "getLogin":
              // get user login info
              
              $response = ["ok" => true, "id" => $row['user_id'], "token" => $row['token']];
              die(json_encode($response));
            
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
