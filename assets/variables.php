<?php
    if (basename(__FILE__) != basename($_SERVER["SCRIPT_FILENAME"])) {
        
        /* CONFIGURATION */
        
        if (!isset($debug)) {
            $debug = false; // Enable PHP error_reporting
        }
        
        $privateFolder = "/private/spiteful-chat"; // Private directory location from directory behind DOCUMENT_ROOT
        $composerFolder = "./vendor"; // PHP Composer Directory
        $nodeModulesFolder = "./node_modules"; // NPM Modules Directory
        $spiteSocketServerHost = "127.0.0.1"; // Spiteful Server Websocket Host
        $spiteSocketServerPort = "12345"; // Spiteful Server Websocket Port
        
        $loginSessionHours = 8; // How long the session should last in hours
        
        $chatRoot = "./"; // TMP: Not utilized in folders within folders, since paths are relative
        
        $googleDataClientId = "13964511690-v18rnirom2pk4ct8rc5careg7geda2mg.apps.googleusercontent.com"; // x.apps.googleusercontent.com
        $googleDataLoginUri = "https://dleovl.me/spiteful-chat/cwg";
        $googleSigninClientId = "905753697514-bat1tv7nld4ltb4fhfo9op5gmf93jggj.apps.googleusercontent.com"; // x.apps.googleusercontent.com
        
        /* TIME CONFIGURATIONS */
        
        define("TIME_SECOND", 1);
        define("TIME_MINUTE", TIME_SECOND * 60);
        define("TIME_HOUR", TIME_MINUTE * 60);
        define("TIME_DAY", TIME_HOUR * 24);
        define("TIME_WEEK", TIME_DAY * 7);
        
        /* CUSTOM FUNCTIONS */
        
        if ($debug === true) {
            error_reporting(E_ALL);
            ini_set("display_errors", 1);
        }
        
        if ((!isset($noSession) || ($noSession === false)) && (session_status() === PHP_SESSION_NONE)) {
            // Starts session if $noSession does not exist (or set to false) and if session is not already started
            session_start();
        }
        
        if (php_sapi_name() === "cli") {
            $privateFolder = "../..$privateFolder";
        } else {
            $tfolder = explode("/", $_SERVER["DOCUMENT_ROOT"]);
            $tfolder = array_filter($tfolder);
            array_pop($tfolder);
            $tfolder = implode("/", $tfolder);
            $privateFolder = "/$tfolder$privateFolder";
            unset($tfolder);
        }
        
        function logout() {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            unset($_SESSION["id"]);
            unset($_SESSION["token"]);
            header("Location: $chatRoot");
        }
        
        function fileLink($file) {
            // Supply a file URL along with a timestamp. Must be a local file. Assumes no existing ?s or will error as ? is not in file path.
            $tfile = explode("?", $file);
            return $tfile[0] . "?" . filemtime($tfile[0]);
            unset($tfile);
        }
        
        function metaTags($tabs = 0) {
            // Alternative to doing head/foot.php
            $first = true;
            $metaTags = [
                "<!--[if lt IE 9]>",
                "\t<script src=\"//cdnjs.cloudflare.com/ajax/libs/html5shiv/r29/html5.min.js\"></script>",
                "<![endif]-->",
                "<meta name=\"viewport\" content=\"width=device-width,initial-scale=1,shrink-to-fit=no\" />",
                "<meta name=\"apple-mobile-web-app-capable\" content=\"yes\" />"
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
        
    } else {
        http_response_code(404);
        die();
    }
?>