<?php

    /*
    require_once "../vendor/autoload.php";
    require_once "../assets/configuration.php";
    use Minishlink\WebPush\Subscription;
    use Minishlink\WebPush\WebPush;
    
    $sub = Subscription::create(json_decode($_POST["sub"], true));
    
    $push = new WebPush(["VAPID" => [
        "subject" => $vapidsubject,
        "publicKey" => $vapidpublickey,
        "privateKey" => $vapidprivatekey
    ]]);
    
    $result = $push->sendOneNotification($sub, json_encode([
        "title" => "Welcome!",
        "body" => "Let's chat!",
        "icon" => "a.png",
        "image" => "b.png"
    ]));
    $endpoint = $result->getRequest()->getUri()->__toString();
    
    if ($result->isSuccess()) {
        // echo "Successfully sent {$endpoint}.";
    } else {
        // echo "Send failed {$endpoint}: {$result->getReason()}";
        // $result->getRequest();
        // $result->getResponse();
        // $result->isSubscriptionExpired();
    }
    */

?>