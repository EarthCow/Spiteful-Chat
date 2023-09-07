<?php

    if (basename(__FILE__) != basename($_SERVER["SCRIPT_FILENAME"])) {
        
        /*
            
            Language Localization
                .js files are not translated,
                meaning whatever is inside of them is by default English
                It may be possible to serve the file using PHP, but
                entirely up to you.
                
                Websocket PHP files are not translated because
                they must first access this file.
            
        */
        
        $words = array (
            
            "spiteful-chat" => array(
                // Capitalized Title
                "en_US" => "Spiteful Chat",
                "es_ES" => "Chat Malicioso",
            ),
            
            "welcome-to-spiteful-chat" => array(
                // Capitalized Title
                "en_US" => "Welcome to Spiteful Chat",
                "es_ES" => "Bienvenido a Chat Malicioso",
            ),
            
            "hello" => array (
                // Capitalized Title
                "en_US" => "Hello",
                "es_ES" => "Hola",
            ),
            
            "please-login" => array (
                "en_US" => "Please login or sign up to continue",
                "es_ES" => "Por favor, inicia sesión o regístrate para continuar",
            ),
            
            "maintenance" => array (
                "en_US" => "Maintenance",
                "es_ES" => "Mantenimiento",
            ),
            
            "spiteful-maintenance" => array (
                // Capitalized Title
                "en_US" => "Spiteful Chat Maintenance",
                "es_ES" => "Mantenimiento de Chat Malicioso",
            ),
            
            "chat-being-worked-on" => array (
                "en_US" => "The chat is currently being worked on and you are denied access.",
                "es_ES" => "Actualmente se está trabajando en el chat y se te ha denegado el acceso.",
            ),
            
            "error-occurred" => array (
                "en_US" => "An error occurred",
                "es_ES" => "Se ha producido un error",
            ),
            
            "invalid-username" => array (
                "en_US" => "Invalid username",
                "es_ES" => "Nombre de usuario no válido",
            ),
            
            "invalid-operation" => array (
                // Capitalized Title
                "en_US" => "Invalid Operation",
                "es_ES" => "Operación no válida",
            ),
            
            "chat-self-error" => array (
                "en_US" => "Fucking dumbass no you cannot message yourself lonely ass",
                "es_ES" => "Maldito tonto, no puedes enviarte un mensaje, pobre desgraciado",
            ),
            
            "conversation-creation-fail" => array (
                "en_US" => "Failed to create a new conversation",
                "es_ES" => "Error al crear una nueva conversación",
            ),
            
            "transfer-error-code" => array (
                "en_US" => "A transfer error occurred with error code:",
                "es_ES" => "Se produjo un error de transferencia con el código de error:",
            ),
            
            "invalid-request" => array (
                "en_US" => "Invalid request :(",
                "es_ES" => "Solicitud no válida :(",
            ),
            
            "cannot-find-user" => array (
                "en_US" => "Couldn't find who you're looking for",
                "es_ES" => "No se pudo encontrar a quien estás buscando",
            ),
            
            "update-username-subtle" => array (
                // May be temporary
                "en_US" => "nuts",
                "es_ES" => "nueces",
            ),
            
            "rename-taken" => array (
                "en_US" => "Be more original dude c'mon",
                "es_ES" => "Sé más original, amigo, vamos",
            ),
            
            "sent-video" => array (
                "en_US" => "Sent a video",
                "es_ES" => "Envió un vídeo",
            ),
            
            "sent-image" => array (
                "en_US" => "Sent an image",
                "es_ES" => "Envió una imagen",
            ),
            
            "sent-file" => array (
                "en_US" => "Sent a file",
                "es_ES" => "Envió un archivo",
            ),
            
            "file-retrieve-fail" => array (
                "en_US" => "Failed to retrieve file",
                "es_ES" => "No se pudo recuperar el archivo",
            ),
            
            "messages-retrieve-fail" => array (
                "en_US" => "Failed to retrieve messages",
                "es_ES" => "No se pudieron recuperar los mensajes",
            ),
            
            "loading-messages" => array (
                "en_US" => "Loading messages",
                "es_ES" => "Cargando mensajes",
            ),
            
            "nothing-to-see" => array (
                "en_US" => "Nothing to see here...",
                "es_ES" => "No hay nada que ver aquí...",
            ),
            
            "pick-username" => array (
                "en_US" => "Choose a username!",
                "es_ES" => "¡Elige un nombre de usuario!",
            ),
            
            "blank-username" => array (
                "en_US" => "Type something bro",
                "es_ES" => "Escribe algo, amigo",
            ),
            
            "welcome," => array (
                // Capitalized Title
                // Contains trimmable whitespace
                "en_US" => "Welcome ",
                "es_ES" => "Bienvenido ",
            ),
            
            "invalid-username-characters" => array (
                "en_US" => "Your username cannot contain special characters or spaces",
                "es_ES" => "Tu nombre de usuario no puede contener caracteres especiales ni espacios",
            ),
            
            "dashboard" => array (
                "en_US" => "Dashboard",
                "es_ES" => "Tablero",
            ),
            
            "new-message" => array (
                // Capitalized Title
                "en_US" => "New Message",
                "es_ES" => "Nuevo Mensaje",
            ),
            
            "message-sent-successfully" => array (
                "en_US" => "Message was sent successfully!",
                "es_ES" => "¡El mensaje se envió exitosamente!",
            ),
            
            "client-connecting" => array (
                "en_US" => "Client attempting to connect...",
                "es_ES" => "Cliente intentando conectarse...",
            ),
            
            "user-connected" => array (
                "en_US" => "User Connected",
                "es_ES" => "Usuario Conectado",
            ),
            
            "user-disconnected" => array (
                "en_US" => "User Disconnected",
                "es_ES" => "Usuario Desconectado",
            ),
            
            "count" => array (
                "en_US" => "Count",
                "es_ES" => "Contador",
            ),
            
            "mysql-false-ping" => array (
                "en_US" => "MYSQL: Pinged false - Current ping:",
                "es_ES" => "MYSQL: Ping falso - Ping actual:",
            ),
            
            "invalid-instruction" => array (
                "en_US" => "Invalid instruction received!",
                "es_ES" => "Instrucción no válida recibida!",
            ),
            
            // Not sure if a different translation will cause issues. Spanish is same since it is an onomatopoeia.
            "pong" => array (
                "en_US" => "Pong!",
                "es_ES" => "Pong!",
            ),
            
            "server-listening-on" => array (
                "en_US" => "Server listening on",
                "es_ES" => "Servidor escuchando en",
            ),
            
            "with-master-socket" => array (
                "en_US" => "with master socket",
                "es_ES" => "con el socket maestro",
            ),
            
        );
        
        function word($string) {
            global $words;
            global $language;
            try {
                return $words[$string][$language];
            } catch (Exception $e) { // Default to English
                return $words[$string]["en_US"];
            }
        }
        
    } else {
        http_response_code(404);
        die();
    }

?>