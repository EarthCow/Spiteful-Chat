#!/usr/bin/env php
<?php

require_once('websockets.php');
require_once("/var/www/private/spiteful-chat/database.php");

class SpiteServer extends WebSocketServer {

  private $nextPing;

  // $maxBufferSize is 1MB
  function __construct($addr, $port, $bufferLength=1048576) {
    parent::__construct($addr, $port, $bufferLength);
    $this->userClass = 'SpiteUser';
  }

  function respond($user, $statusText, $ok = false, $obj = false) {
    $response = [
      "ok" => $ok,
      "statusText" => $statusText
    ];
    $finalObj = ($obj === false ? $response : (object) array_merge((array) $response, (array) $obj));
    $this->send($user, json_encode($finalObj));
  }

  // ran when server recieves data
  protected function process ($user, $message) {
    $parsedMsg = null;
    try {
      $parsedMsg = json_decode($message, true);
    } catch (\Throwable $th) {
      $this->respond($user, "Invalid data received!");
      return;
    }

    switch ($parsedMsg["instruction"]) {
      case "L":
        if ($user->sessId != null) {
          $this->respond($user, "Logged in already!");
        } else {
          $sql = "SELECT * FROM `profiles` WHERE `user_id`=?";
          $statement = $GLOBALS['connection']->prepare($sql);
          $statement->bind_param("i", $parsedMsg["content"]["id"]);
  
          if (!$statement->execute()) {
            $this->respond($user, "CSDB10");
            return;
          }
  
          $result = $statement->get_result();
  
          if ($result->num_rows == 0) {
            $this->respond($user, "CNE1");
            return;
          }
  
          $row = $result->fetch_assoc();
          if ($row['token'] != $parsedMsg["content"]["token"]) {
            $this->respond($user, "CNE1");
            return;
          }
  
          $token_generated = strtotime($row['token_generated']);
          $timeBetween = time() - $token_generated;
  
          if ($timeBetween > 28800) { // 28800 is 8 hours
            $this->respond($user, "SESS");
            return;
          }
  
          $user->sessId = $row["user_id"];
          $user->sessToken = $row["token"];

          $user->username = $row["username"];
          $user->name = $row["name"];
          $user->picture = $row["picture"];

          $this->respond($user, "User logged in!", true);
        }
        break;
      case "M":
        if (empty($parsedMsg["content"])) {
          $this->respond($user, "Invalid operation");
          return;
        }

        List($receiverUsername, $message) = $parsedMsg["content"];
        
        if (preg_match('/[-!#@$%^&*()_+|~=`{}\[\]:\";\'<>?,.\\\\\/\s]/', $receiverUsername)) {
          $this->respond($user, "Invalid operation");
          return;
        }

        if (!$message["type"]) {
        
          $sql = "SELECT `user_id`, `name`, `picture` FROM `profiles` WHERE `username`=?";
          $statement = $GLOBALS["connection"] -> prepare($sql);
          $statement -> bind_param("s", $receiverUsername);
          $statement -> execute();
          $result = $statement -> get_result();

          if ($result -> num_rows == 0) {
            $this->respond($user, "Invalid Operation");
            return;
          }

          $receiverRow = $result -> fetch_assoc();
          $receiverId = $receiverRow["user_id"];

          $sql = "SELECT chat_id FROM chats WHERE (sender = $user->sessId AND receiver = $receiverId) OR (sender = $receiverId AND receiver = $user->sessId)";
          $result = $GLOBALS["connection"] -> query($sql);

          if ($result -> num_rows != 1) {
            // If no chat exists yet
            if (strcasecmp($receiverUsername, $user->username) == 0) {
              $response["statusText"] = "Fucking dumbass no you cannot message yourself lonely ass";
              die(json_encode($response));
            }

            $sql = "INSERT INTO `chats` (`sender`, `receiver`) VALUES ($user->sessId, $receiverId);";
            $result = $GLOBALS["connection"] -> query($sql);

            if (!$result) {
              $response = ["statusText" => "Failed to create a new conversation"];
              die(json_encode($response));
            }
            $chatId = $GLOBALS["connection"] -> insert_id;
          } else {
            $row = $result ->fetch_assoc();
            $chatId = $row["chat_id"];
          }

          $sql = "INSERT INTO `messages` (chat_id, sender, content) VALUES ($chatId, $user->sessId, ?)";
          $statement = $GLOBALS["connection"] -> prepare($sql);
          $statement -> bind_param("s", $message["content"]);
          $statement -> execute();

          $insertedId = $statement -> get_result();

          $sql = "UPDATE chats SET last_message = ? WHERE chat_id = $chatId";
          $statement = $GLOBALS["connection"] -> prepare($sql);
          $truncated = substr($message["content"], 0, 255);
          $statement -> bind_param("s", $truncated);
          $statement -> execute();
        }
        
        $lm = date("m/d/Y h:i:s");

        $receiver = false;
        foreach ($this->users as $currentUser) {
            if ($currentUser->username === $receiverUsername) {
                $receiver = $currentUser;
                break;
            }
        }
        unset($currentUser);

        if ($receiver) {
          $this->send($receiver, json_encode([
            "sender" => $user->username,
            "message" => $message,
            "info" => [
              "name" => $user->name,
              "picture" => $user->picture
            ]
          ]));
        }
        
        $this->respond($user, "Message was sent successfully!", true, [
          "lm" => $lm,
          "id" => isset($insertedId) ? $insertedId : 0,
          "sendId" => $parsedMsg["sendId"]
        ]);
        return;

        break;
      case "P":
        $this->respond($user, "Pong!", true);
        break;
      default:
        $response["statusText"] = "Invalid instruction recieved!";
        $this->send($user, json_encode($response));
        break;
    }
  }

  protected function connecting ($user) {
    $this->stdout("Client attempting to connect");
  }
  
  protected function connected ($user) {
    $this->stdout("User Connected - Count: ".count($this->users));
  }
  
  protected function closed ($user) {
    $this->stdout("User Disconnected - Count: ".count($this->users));
    // Do nothing: This is where cleanup would go, in case the user had any sort of
    // open files or other objects associated with them.  This runs after the socket 
    // has been closed, so there is no need to clean up the socket itself here.
  }

  protected function tick() {

  }
}

$spite = new SpiteServer("127.0.0.1","12345");
try {
  $spite->run();
}
catch (Exception $e) {
  $spite->stdout($e->getMessage());
}
