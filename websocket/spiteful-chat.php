#!/usr/bin/env php
<?php

    function spiteErrorHandler($errno, $errstr, $errfile, $errline) {
        echo "\033[31m[!] \033[39mThis command must be run from the spiteful-chat folder.\n";
        die();
    }
    
    set_error_handler("spiteErrorHandler");
    
    require_once "./assets/configuration.php";
    require_once "./assets/languages.php";
    require_once "./websocket/websockets.php";
    require_once "$privateFolder/database.php";
    
    restore_error_handler();
    
    class SpiteServer extends WebSocketServer
    {
        private $mysqlLastUsed;
        
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
                        $message["content"] = str_replace(
                            "<br>",
                            "\n",
                            $message["content"]
                        );
                        $message["content"] = htmlspecialchars($message["content"]);
                    }
                    
                    if (!$message["type"]) {
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
                        
                        $sql = "SELECT chat_id FROM chats WHERE (sender = $user->sessId AND receiver = $receiverId) OR (sender = $receiverId AND receiver = $user->sessId)";
                        $result = $GLOBALS["connection"]->query($sql);
                        
                        if ($result->num_rows != 1) {
                            // If no chat exists yet
                            if (
                                strcasecmp($receiverUsername, $user->username) == 0
                            ) {
                                $response["statusText"] = word("chat-self-error");
                                die(json_encode($response));
                            }
                            
                            $sql = "INSERT INTO `chats` (`sender`, `receiver`) VALUES ($user->sessId, $receiverId);";
                            $result = $GLOBALS["connection"]->query($sql);
                            
                            if (!$result) {
                                $response = [
                                    "statusText" => word("conversation-creation-fail"),
                                ];
                                die(json_encode($response));
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
                    
                    $receiver = false;
                    foreach ($this->users as $currentUser) {
                        if ($currentUser->username === $receiverUsername) {
                            $receiver = $currentUser;
                            break;
                        }
                    }
                    unset($currentUser);
                    
                    $message["date"] = $lm;
                    
                    if ($receiver) {
                        $this->send(
                            $receiver,
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
                    
                    $this->respond($user, word("message-sent-successfully"), true, [
                        "lm" => $lm,
                        "id" => isset($insertedId) ? $insertedId : 0,
                        "sendId" => $parsedMsg["sendId"],
                    ]);
                    return;
                    
                    break;
                case "C":
                    $sql = "
                        SELECT chats.chat_id, chats.last_message, chats.modified, profiles.username, profiles.name, profiles.picture
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
                        foreach ($this->users as $currentUser) {
                            if ($currentUser->username === $chat["username"]) {
                                $chat["status"] = "online";
                                break;
                            }
                        }
                        unset($currentUser);
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
                    
                    foreach ($this->users as $currentUser) {
                        if ($currentUser->username === $parsedMsg["content"]) {
                            $this->send(
                                $user,
                                json_encode([
                                    "ok" => true,
                                    "username" => $currentUser->username,
                                    "status" => "online",
                                ])
                            );
                            break;
                        }
                    }
                    unset($currentUser);
                    break;
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
            $this->stdout("\033[36m[i] \033[39m" . word("client-connecting"));
        }
        
        protected function doingHandShake($user, $headers, &$handshakeResponse)
        {
            // If there's already an issue there's no reason to check for another
            if (!empty($handshakeResponse)) {
                return;
            }
            
            if (
                !isset($headers["get"]) ||
                empty(trim($headers["get"])) ||
                trim($headers["get"]) == "/"
            ) {
                $handshakeResponse = "HTTP/1.1 400 Bad Request";
                return;
            }
            
            list($sessId, $token) = explode(".", substr($headers["get"], 1));
            
            if (empty($sessId) || empty($token)) {
                $handshakeResponse = "HTTP/1.1 400 Bad Request";
                return;
            }
            
            $sql = "SELECT * FROM `profiles` WHERE `user_id`=?";
            $statement = $GLOBALS["connection"]->prepare($sql);
            $statement->bind_param("i", $sessId);
            $this->updateMysqlLastUsed();
            
            // Failed execute MySQL statement
            if (!$statement->execute()) {
                $handshakeResponse = "HTTP/1.1 400 Bad Request";
                return;
            }
            
            $result = $statement->get_result();
            
            // Failed to find the user
            if ($result->num_rows == 0) {
                $handshakeResponse = "HTTP/1.1 400 Bad Request";
                return;
            }
            
            $row = $result->fetch_assoc();
            
            // Failed to validate token
            if ($row["token"] != $token) {
                $handshakeResponse = "HTTP/1.1 400 Bad Request";
                return;
            }
            
            $token_generated = strtotime($row["token_generated"]);
            $timeBetween = time() - $token_generated;
            
            // Token expired
            if ($timeBetween > TIME_HOUR * 8) {
                $handshakeResponse = "HTTP/1.1 400 Bad Request";
                return;
            }
            
            $user->sessId = $row["user_id"];
            $user->sessToken = $row["token"];
            
            $user->username = $row["username"];
            $user->name = $row["name"];
            $user->picture = $row["picture"];
            
            // Will eventually need to define a friends system likely another relational table :)
            // For now I will get the current chats and use that to determine the active friends
            
            // When status is added as a table column "appear offline" would negate this entire logic
            $sql = "
                SELECT profiles.username
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
                foreach ($this->users as $currentUser) {
                    if ($currentUser->username === $chat["username"]) {
                        $this->send(
                            $currentUser,
                            json_encode([
                                "ok" => true,
                                "username" => $user->username,
                                "status" => "online", // This will eventually reflect an option within the profiles table for now "online" is fine
                            ])
                        );
                        break;
                    }
                }
            }
        }
        
        protected function connected($user)
        {
            $this->stdout("\033[36m[i] \033[39m" . word("user-connected") . " - " . word("count") . ": " . count($this->users));
        }
        
        protected function closed($user)
        {
            $this->stdout("\033[36m[i] \033[39m" . word("user-disconnected") . " - " . word("count") . ": " . count($this->users));
            if ($user->username == null) {
                return;
            }
            $sql = "
                SELECT profiles.username
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
                foreach ($this->users as $currentUser) {
                    if ($currentUser->username === $chat["username"]) {
                        $this->send(
                            $currentUser,
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
?>