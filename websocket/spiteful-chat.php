#!/usr/bin/env php
<?php

function spiteErrorHandler($errno, $errstr, $errfile, $errline)
{
  echo "\033[31m[!] \033[39mThis command must be run from the spiteful-chat folder.\n";
  die();
}

set_error_handler("spiteErrorHandler");

require_once "./configuration.php";
require_once "$composerFolder/autoload.php";

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

require_once "./languages.php";
require_once "./websocket/websockets.php";
require_once "$privateFolder/database.php";

restore_error_handler();

class SpiteServer extends WebSocketServer
{
  private $mysqlLastUsed;
  private $userClients = [];

  // $maxBufferSize is 1MB
  function __construct($addr, $port, $bufferLength = 1048576)
  {
    parent::__construct($addr, $port, $bufferLength);
    $this->userClass = "SpiteUser";

    $this->updateMysqlLastUsed();
  }

  function respond($user, $statusText, $ok = false, $obj = false)
  {
    $response = [
      "ok" => $ok,
      "statusText" => $statusText,
    ];
    $finalObj =
      $obj === false
      ? $response
      : (object) array_merge((array) $response, (array) $obj);
    $this->send($user, json_encode($finalObj));
  }

  protected function updateMysqlLastUsed()
  {
    $this->mysqlLastUsed = time();
  }

  // ran when server recieves data
  protected function process($user, $message)
  {
    global $vapidPublic, $vapidPrivate;
    $parsedMsg = null;
    try {
      $parsedMsg = json_decode($message, true);
    } catch (\Throwable $th) {
      $this->respond($user, word("invalid-data"));
      return;
    }

    $sql = "SELECT * FROM `profiles` WHERE `user_id`=?";
    $statement = $GLOBALS["connection"]->prepare($sql);
    $statement->bind_param("i", $user->sessId);
    $this->updateMysqlLastUsed();

    if (!$statement->execute()) {
      $this->respond($user, "SESS:DBF");
      return;
    }

    $result = $statement->get_result();

    if ($result->num_rows == 0) {
      $this->respond($user, "SESS:NEX");
      return;
    }

    $row = $result->fetch_assoc();
    if ($row["token"] != $user->sessToken) {
      $this->respond($user, "SESS:TOK");
      return;
    }

    $token_generated = strtotime($row["token_generated"]);
    $timeBetween = time() - $token_generated;

    if ($timeBetween > TIME_HOUR * 8) {
      $this->respond($user, "SESS:EXP");
      return;
    }

    switch ($parsedMsg["instruction"]) {
      case "M":
        if (empty($parsedMsg["content"])) {
          $this->respond($user, word("invalid-operation"));
          return;
        }

        list($receiverUsername, $message) = $parsedMsg["content"];

        if (
          preg_match(
            '/[-!#@$%^&*()_+|~=`{}\[\]:\";\'<>?,.\\\\\/\s]/',
            $receiverUsername
          )
        ) {
          $this->respond($user, word("invalid-operation"));
          return;
        }

        // Media may not contain key content
        if (isset($message["content"])) {
          $message["content"] = trim($message["content"]);
          if (empty($message["content"])) {
            $this->respond($user, word("invalid-operation"));
            return;
          }
          $message["content"] = htmlspecialchars($message["content"]);
        }

        $sql =
          "SELECT `user_id`, `name`, `picture` FROM `profiles` WHERE `username`=?";
        $statement = $GLOBALS["connection"]->prepare($sql);
        $statement->bind_param("s", $receiverUsername);
        $statement->execute();
        $result = $statement->get_result();

        if ($result->num_rows == 0) {
          $this->respond($user, word("invalid-operation"));
          return;
        }

        $receiverRow = $result->fetch_assoc();
        $receiverId = $receiverRow["user_id"];

        if (!$message["type"]) {
          $sql = "SELECT chat_id FROM chats WHERE (sender = $user->sessId AND receiver = $receiverId) OR (sender = $receiverId AND receiver = $user->sessId)";
          $result = $GLOBALS["connection"]->query($sql);

          if ($result->num_rows != 1) {
            // If no chat exists yet
            if (
              strcasecmp($receiverUsername, $user->username) == 0
            ) {
              $this->respond($user, word("chat-self-error"));
              return;
            }

            $sql = "INSERT INTO `chats` (`sender`, `receiver`) VALUES ($user->sessId, $receiverId);";
            $result = $GLOBALS["connection"]->query($sql);

            if (!$result) {
              $this->respond($user, word("conversation-creation-fail"));
              return;
            }
            $chatId = $GLOBALS["connection"]->insert_id;
          } else {
            $row = $result->fetch_assoc();
            $chatId = $row["chat_id"];
          }

          $sql = "INSERT INTO `messages` (chat_id, sender, content) VALUES ($chatId, $user->sessId, ?)";
          $statement = $GLOBALS["connection"]->prepare($sql);
          $statement->bind_param("s", $message["content"]);
          $statement->execute();

          $insertedId = $statement->get_result();

          $sql = "UPDATE chats SET last_message = ? WHERE chat_id = $chatId";
          $statement = $GLOBALS["connection"]->prepare($sql);
          $truncated = substr($message["content"], 0, 255);
          $statement->bind_param("s", $truncated);
          $statement->execute();
        }

        $this->updateMysqlLastUsed();

        $lm = date("m/d/Y h:i:s");
        $message["date"] = $lm;

        // Send the message to the user's other clients if there are any
        if (count($this->userClients[$user->sessId]) > 1) {
          foreach ($this->userClients[$user->sessId] as $client) {
            if ($client->id == $user->id) continue;
            $this->send(
              $client,
              json_encode([
                "ok" => true,
                "sender" => $user->username,
                "sentTo" => $receiverUsername,
                "message" => $message,
              ])
            );
          }
        }

        // Send the message to all the receiver's clients if there are any
        if (isset($this->userClients[$receiverId])) {
          foreach ($this->userClients[$receiverId] as $client) {
            $this->send(
              $client,
              json_encode([
                "ok" => true,
                "sender" => $user->username,
                "message" => $message,
                "info" => [
                  "name" => $user->name,
                  "picture" => $user->picture,
                ],
              ])
            );
          }
        }

        // If the receiver is subscribed send them a push notification
        $sql = "SELECT `notify_sub` FROM `profiles` WHERE `username` = ?";
        $statement = $GLOBALS["connection"]->prepare($sql);
        $statement->bind_param("s", $receiverUsername);
        $statement->execute();
        $result = $statement->get_result();
        if ($result->num_rows == 1) {
          $receiverSub = $result->fetch_assoc()["notify_sub"];
          if ($receiverSub != null) {
            $sub = Subscription::create(json_decode($receiverSub, true));

            $push = new WebPush(["VAPID" => [
              "subject" => "Message",
              "publicKey" => $vapidPublic,
              "privateKey" => $vapidPrivate
            ]]);

            $result = $push->sendOneNotification($sub, json_encode([
              "title" => $user->name,
              "body" => $message["content"] ?? $message["original"],
              "icon" => $user->picture,
              // "image" => $user->picture
            ]));

            if (!$result->isSuccess()) {
              $this->stdout(word("service-worker-failed-expired") . $result->isSubscriptionExpired());
              if ($result->isSubscriptionExpired()) {
                // If the sub is expired remove it from the db
                $sql =
                  "UPDATE `profiles` SET `notify_sub`=NULL WHERE `username`=?";
                $statement = $GLOBALS["connection"]->prepare($sql);
                $statement->bind_param("s", $receiverUsername);
                $statement->execute();

                $this->updateMysqlLastUsed();
              }
            }
          }
        }

        $this->respond($user, word("message-sent-successfully"), true, [
          "lm" => $lm,
          "id" => isset($insertedId) ? $insertedId : 0,
          "sendId" => $parsedMsg["sendId"],
        ]);
        return;

        break;
      case "C":
        $sql = "
          SELECT chats.chat_id, chats.last_message, chats.modified, profiles.user_id, profiles.username, profiles.name, profiles.picture
          FROM `chats`
          JOIN `profiles`
          ON IF(chats.sender = $user->sessId, chats.receiver = profiles.user_id, chats.sender = profiles.user_id)
          WHERE chats.sender = $user->sessId OR chats.receiver = $user->sessId
          ORDER BY `modified` DESC;
        ";
        $result = $GLOBALS["connection"]->query($sql);
        $this->updateMysqlLastUsed();

        $row = $result->fetch_all(MYSQLI_ASSOC);

        // Pass $chat as a reference to add the status key
        foreach ($row as &$chat) {
          if (isset($this->userClients[$chat["user_id"]]) && !empty($this->userClients[$chat["user_id"]])) {
            $chat["status"] = "online";
          }
        }
        unset($chat);

        // if there are no rows (chats) then send an empty array
        $this->respond($user, "", true, [
          "chats" => $row ?? [],
          "sendId" => $parsedMsg["sendId"],
        ]);

        break;
      case "S":
        if (empty($parsedMsg["content"])) {
          return;
        }

        $sql =
          "SELECT `user_id`, `username` FROM `profiles` WHERE `username`=?";
        $statement = $GLOBALS["connection"]->prepare($sql);
        $statement->bind_param("s", $parsedMsg["content"]);
        $statement->execute();
        $result = $statement->get_result();

        $this->updateMysqlLastUsed();

        if ($result->num_rows == 0) {
          $this->respond($user, word("invalid-operation"));
          return;
        }

        $requestedRow = $result->fetch_assoc();
        $requestedId = $requestedRow["user_id"];


        if (isset($this->userClients[$requestedId]) && !empty($userClients[$requestedId])) {
          $this->send(
            $user,
            json_encode([
              "ok" => true,
              "username" => $requestedRow["username"],
              "status" => "online",
            ])
          );
        }

        break;
      case "SUB":
        $sql =
          "UPDATE `profiles` SET `notify_sub` = ? WHERE `user_id`=?";
        $statement = $GLOBALS["connection"]->prepare($sql);
        $notifySub = $parsedMsg["content"] === NULL ? NULL : json_encode($parsedMsg["content"]);
        $statement->bind_param("si", $notifySub, $user->sessId);
        $statement->execute();

        $user->notifySub = $parsedMsg["content"] ?? NULL;

        $this->updateMysqlLastUsed();

        $this->respond($user, word("successfully-updated-records"), true, [
          "sendId" => $parsedMsg["sendId"],
        ]);
        return;
      case "P":
        $this->respond($user, word("pong"), true);
        break;
      default:
        $response["statusText"] = word("invalid-instruction");
        $this->send($user, json_encode($response));
        break;
    }
  }

  protected function connecting($user)
  {
    $this->stdout("\033[36m[i] \033[35m" . $user->id . " \033[39m" . word("client-connecting"));
  }

  protected function doingHandShake($user, $headers, &$handshakeResponse)
  {
    // If there's already an issue there's no reason to check for another
    if (!empty($handshakeResponse)) {
      return;
    }

    if (!isset($headers["cookie"]) || !str_contains($headers["cookie"], "SPITESESS")) {
      $handshakeResponse = "HTTP/1.1 400 Bad Request";
      return;
    }

    $cookies = explode(";", $headers["cookie"]);
    $parsedCookies = array();
    foreach ($cookies as $cookie) {
      list($key, $value) = explode("=", trim($cookie));
      $parsedCookies[$key] = $value;
    }

    $row = verifySession(true, $parsedCookies["SPITESESS"]);
    if (!$row) {
      $handshakeResponse = "HTTP/1.1 400 Bad Request";
      return;
    }
    $this->updateMysqlLastUsed();

    $sessId = $row["user_id"];
    $user->sessId = $sessId;
    $user->sessToken = $row["token"];

    $user->username = $row["username"];
    $user->name = $row["name"];
    $user->picture = $row["picture"];

    // Will eventually need to define a friends system likely another relational table :)
    // For now I will get the current chats and use that to determine the active friends

    // When status is added as a table column "appear offline" would negate this entire logic
    $sql = "
      SELECT profiles.user_id
      FROM `chats`
      JOIN `profiles`
      ON IF(chats.sender = $sessId, chats.receiver = profiles.user_id, chats.sender = profiles.user_id)
      WHERE chats.sender = $sessId OR chats.receiver = $sessId
      ORDER BY `modified` DESC;
    ";
    $result = $GLOBALS["connection"]->query($sql);
    $this->updateMysqlLastUsed();

    $row = $result->fetch_all(MYSQLI_ASSOC);

    foreach ($row as $chat) {
      if (isset($this->userClients[$chat["user_id"]])) {
        foreach ($this->userClients[$chat["user_id"]] as $client) {
          $this->send(
            $client,
            json_encode([
              "ok" => true,
              "username" => $user->username,
              "status" => "online", // This will eventually reflect an option within the profiles table for now "online" is fine
            ])
          );
        }
      }
    }
  }

  protected function connected($user)
  {
    if (!isset($this->userClients[$user->sessId])) $this->userClients[$user->sessId] = [];
    $this->userClients[$user->sessId][$user->id] = $user;
    $this->stdout("\033[36m[i] \033[35m" . $user->id . " \033[39m" . word("client-connected") . " - " . word("count") . ": " . count($this->users));
  }

  protected function closed($user)
  {
    unset($this->userClients[$user->sessId][$user->id]);
    if (empty($this->userClients[$user->sessId])) unset($this->userClients[$user->sessId]);
    $this->stdout("\033[31m[!] \033[35m" . $user->id . " \033[39m" . word("client-disconnected") . " - " . word("count") . ": " . count($this->users));
    if ($user->username == null) {
      return;
    }
    if (!empty($this->userClients[$user->sessId])) return;
    $sql = "
      SELECT profiles.user_id
      FROM `chats`
      JOIN `profiles`
      ON IF(chats.sender = $user->sessId, chats.receiver = profiles.user_id, chats.sender = profiles.user_id)
      WHERE chats.sender = $user->sessId OR chats.receiver = $user->sessId
      ORDER BY `modified` DESC;
    ";
    $result = $GLOBALS["connection"]->query($sql);
    $this->updateMysqlLastUsed();

    $row = $result->fetch_all(MYSQLI_ASSOC);

    foreach ($row as $chat) {
      if (isset($this->userClients[$chat["user_id"]])) {
        foreach ($this->userClients[$chat["user_id"]] as $client) {
          $this->send(
            $client,
            json_encode([
              "ok" => true,
              "username" => $user->username,
              "status" => "", // Blank will remove status and be "offline"
            ])
          );
          break;
        }
      }
    }
    // Do nothing: This is where cleanup would go, in case the user had any sort of
    // open files or other objects associated with them.  This runs after the socket
    // has been closed, so there is no need to clean up the socket itself here.
  }

  protected function tick()
  {
    // Close the mysql server for inactivity
    if (time() - $this->mysqlLastUsed > 600) {
      // 10 minutes
      if (!$GLOBALS["connection"]->ping()) {
        $this->stdout(
          "\033[31m[!] \033[39m" . word("mysql-false-ping") . " " .
            $GLOBALS["connection"]->ping()
        );
      }
    }
  }
}

$spite = new SpiteServer($spiteSocketServerHost, $spiteSocketServerPort);
try {
  $spite->run();
} catch (Exception $e) {
  $spite->stdout("\033[31m[!] \033[39m" . $e->getMessage());
}
