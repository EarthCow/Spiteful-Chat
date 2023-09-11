<?php

/*

  Language Localization
    .js files are not translated,
    meaning whatever is inside of them is by default English
    It may be possible to serve the file using PHP, but
    entirely up to you.

    Websocket PHP files are not translated because
    they must first access this file.
    
*/

$words = array(

  // Language List

  "supported-languages-list" => array( // Show these to the user. These are all supported languages. Will default to English if not found.
    "en_US",
    "es_ES",
    "de_DE",
  ),

  // End Language List

  "spiteful-chat" => array(
    // Capitalized Title
    "en_US" => "Spiteful Chat",
    "es_ES" => "Chat Malicioso",
    "de_DE" => "Boshafter Chat",
  ),

  "welcome-to-spiteful-chat" => array(
    // Capitalized Title
    "en_US" => "Welcome to Spiteful Chat",
    "es_ES" => "Bienvenido a Chat Malicioso",
    "de_DE" => "Willkommen beim Boshaften Chat",
  ),

  "hello" => array(
    // Capitalized Title
    "en_US" => "Hello",
    "es_ES" => "Hola",
    "de_DE" => "Hallo",
  ),

  "please-login" => array(
    "en_US" => "Please login or sign up to continue",
    "es_ES" => "Por favor, inicia sesión o regístrate para continuar",
    "de_DE" => "Bitte melde dich an oder registriere dich, um fortzufahren",
  ),

  "maintenance" => array(
    "en_US" => "Maintenance",
    "es_ES" => "Mantenimiento",
    "de_DE" => "Wartung",
  ),

  "spiteful-maintenance" => array(
    // Capitalized Title
    "en_US" => "Spiteful Chat Maintenance",
    "es_ES" => "Mantenimiento de Chat Malicioso",
    "de_DE" => "Wartung des Boshaften Chats",
  ),

  "chat-being-worked-on" => array(
    "en_US" => "The chat is currently being worked on and you are denied access.",
    "es_ES" => "Actualmente se está trabajando en el chat y se te ha denegado el acceso.",
    "de_DE" => "Der Chat wird derzeit bearbeitet und der Zugriff wurde verweigert.",
  ),

  "error-occurred" => array(
    "en_US" => "An error occurred",
    "es_ES" => "Se ha producido un error",
    "de_DE" => "Ein Fehler ist aufgetreten",
  ),

  "invalid-username" => array(
    "en_US" => "Invalid username",
    "es_ES" => "Nombre de usuario no válido",
    "de_DE" => "Ungültiger Benutzername",
  ),

  "invalid-operation" => array(
    // Capitalized Title
    "en_US" => "Invalid Operation",
    "es_ES" => "Operación no válida",
    "de_DE" => "Ungültige Operation",
  ),

  "chat-self-error" => array(
    "en_US" => "You cannot message yourself",
    "es_ES" => "No puedes enviarte mensajes a ti mismo",
    "de_DE" => "Sie können sich keine Nachrichten senden",
  ),

  "conversation-creation-fail" => array(
    "en_US" => "Failed to create a new conversation",
    "es_ES" => "Error al crear una nueva conversación",
    "de_DE" => "Fehler beim Erstellen einer neuen Konversation",
  ),

  "transfer-error-code" => array(
    "en_US" => "A transfer error occurred with error code:",
    "es_ES" => "Se produjo un error de transferencia con el código de error:",
    "de_DE" => "Ein Übertragungsfehler ist aufgetreten, Fehlercode:",
  ),

  "invalid-request" => array(
    "en_US" => "Invalid request",
    "es_ES" => "Solicitud no válida",
    "de_DE" => "Ungültige Anfrage",
  ),

  "cannot-find-user" => array(
    "en_US" => "Couldn't find who you're looking for",
    "es_ES" => "No se pudo encontrar a quien estás buscando",
    "de_DE" => "Konnte nicht finden, wen du suchst",
  ),

  "update-username-subtle" => array(
    // May be temporary
    "en_US" => "nuts",
    "es_ES" => "nueces",
    "de_DE" => "nüsse",
  ),

  "rename-taken" => array(
    "en_US" => "Be more original dude c'mon",
    "es_ES" => "Sé más original, amigo, vamos",
    "de_DE" => "Sei origineller, Kumpel, komm schon",
  ),

  "sent-video" => array(
    "en_US" => "Sent a video",
    "es_ES" => "Envió un vídeo",
    "de_DE" => "Ein Video gesendet",
  ),

  "sent-image" => array(
    "en_US" => "Sent an image",
    "es_ES" => "Envió una imagen",
    "de_DE" => "Ein Bild gesendet",
  ),

  "sent-file" => array(
    "en_US" => "Sent a file",
    "es_ES" => "Envió un archivo",
    "de_DE" => "Eine Datei gesendet",
  ),

  "file-retrieve-fail" => array(
    "en_US" => "Failed to retrieve file",
    "es_ES" => "No se pudo recuperar el archivo",
    "de_DE" => "Dateiabruf fehlgeschlagen",
  ),

  "messages-retrieve-fail" => array(
    "en_US" => "Failed to retrieve messages",
    "es_ES" => "No se pudieron recuperar los mensajes",
    "de_DE" => "Nachrichtenabruf fehlgeschlagen",
  ),

  "loading-messages" => array(
    "en_US" => "Loading messages",
    "es_ES" => "Cargando mensajes",
    "de_DE" => "Nachrichten werden geladen",
  ),

  "nothing-to-see" => array(
    "en_US" => "Nothing to see here...",
    "es_ES" => "No hay nada que ver aquí...",
    "de_DE" => "Hier gibt es nichts zu sehen...",
  ),

  "pick-username" => array(
    "en_US" => "Choose a username!",
    "es_ES" => "¡Elige un nombre de usuario!",
    "de_DE" => "Wähle einen Benutzernamen!",
  ),

  "blank-username" => array(
    "en_US" => "Type something bro",
    "es_ES" => "Escribe algo, amigo",
    "de_DE" => "Gib etwas ein, Kumpel",
  ),

  "welcome," => array(
    // Capitalized Title
    // Contains trimmable whitespace
    "en_US" => "Welcome ",
    "es_ES" => "Bienvenido ",
    "de_DE" => "Willkommen ",
  ),

  "invalid-username-characters" => array(
    "en_US" => "Your username cannot contain special characters or spaces",
    "es_ES" => "Tu nombre de usuario no puede contener caracteres especiales ni espacios",
    "de_DE" => "Dein Benutzername darf keine Sonderzeichen oder Leerzeichen enthalten",
  ),

  "invalid-username-characters-to" => array(
    "en_US" => "Usernames cannot contain special characters or spaces",
    "es_ES" => "Los nombres de usuario no pueden contener caracteres especiales ni espacios",
    "de_DE" => "Benutzernamen dürfen keine Sonderzeichen oder Leerzeichen enthalten",
  ),


  "dashboard" => array(
    "en_US" => "Dashboard",
    "es_ES" => "Tablero",
    "de_DE" => "Armaturenbrett",
  ),

  "new-message" => array(
    // Capitalized Title
    "en_US" => "New Message",
    "es_ES" => "Nuevo Mensaje",
    "de_DE" => "Neue Nachricht",
  ),

  "message-sent-successfully" => array(
    "en_US" => "Message was sent successfully!",
    "es_ES" => "¡El mensaje se envió exitosamente!",
    "de_DE" => "Die Nachricht wurde erfolgreich gesendet!",
  ),

  "client-connecting" => array(
    "en_US" => "Client attempting to connect...",
    "es_ES" => "Cliente intentando conectarse...",
    "de_DE" => "Client versucht eine Verbindung herzustellen...",
  ),

  "user-connected" => array(
    "en_US" => "User Connected",
    "es_ES" => "Usuario Conectado",
    "de_DE" => "Benutzer verbunden",
  ),

  "user-disconnected" => array(
    "en_US" => "User Disconnected",
    "es_ES" => "Usuario Desconectado",
    "de_DE" => "Benutzer getrennt",
  ),

  "count" => array(
    "en_US" => "Count",
    "es_ES" => "Contador",
    "de_DE" => "Zähler",
  ),

  "mysql-false-ping" => array(
    "en_US" => "MYSQL: Pinged false - Current ping:",
    "es_ES" => "MYSQL: Ping falso - Ping actual:",
    "de_DE" => "MYSQL: Falsches Ping - Aktuelles Ping:",
  ),

  "invalid-instruction" => array(
    "en_US" => "Invalid instruction received!",
    "es_ES" => "Instrucción no válida recibida!",
    "de_DE" => "Ungültige Anweisung empfangen!",
  ),

  // Not sure if a different translation will cause issues.
  // Other languages are the same since it is an onomatopoeia.
  "pong" => array(
    "en_US" => "Pong!",
    "es_ES" => "¡Pong!",
    "de_DE" => "Pong!",
  ),

  "server-listening-on" => array(
    "en_US" => "Server listening on",
    "es_ES" => "Servidor escuchando en",
    "de_DE" => "Server hört auf",
  ),

  "with-master-socket" => array(
    "en_US" => "with master socket",
    "es_ES" => "con el socket maestro",
    "de_DE" => "mit dem Master-Socket",
  ),

  "who-recipient" => array(
    "en_US" => "Who is the recipient?",
    "es_ES" => "¿Quién es el destinatario?",
    "de_DE" => "Wer ist der Empfänger?",
  ),

  "username" => array(
    "en_US" => "Username",
    "es_ES" => "Nombre de usuario",
    "de_DE" => "Benutzername",
  ),

  "cancel" => array(
    "en_US" => "Cancel",
    "es_ES" => "Cancelar",
    "de_DE" => "Abbrechen",
  ),

  "continue" => array(
    "en_US" => "Continue",
    "es_ES" => "Continuar",
    "de_DE" => "Weiter",
  ),

  "conversation-exists" => array(
    "en_US" => "You already have a conversation with @",
    "es_ES" => "Ya tienes una conversación con @",
    "de_DE" => "Du hast bereits ein Gespräch mit @",
  ),

  "already-exists" => array(
    "en_US" => "Already exists",
    "es_ES" => "Ya existe",
    "de_DE" => "Bereits vorhanden",
  ),


  "not-found" => array(
    "en_US" => "not found",
    "es_ES" => "no encontrado",
    "de_DE" => "nicht gefunden",
  ),

  "not-found-title" => array(
    "en_US" => "Not Found",
    "es_ES" => "No Encontrado",
    "de_DE" => "Nicht Gefunden",
  ),

  "this-is-you" => array(
    "en_US" => "This is you!",
    "es_ES" => "¡Este eres tú!",
    "de_DE" => "Das bist du!",
  ),

  "cannot-message-yourself" => array(
    "en_US" => "You cannot message yourself.",
    "es_ES" => "No puedes enviar mensajes a ti mismo.",
    "de_DE" => "Du kannst dir selbst keine Nachrichten senden.",
  ),

  "ok" => array(
    "en_US" => "OK",
    "es_ES" => "Aceptar",
    "de_DE" => "OK",
  ),

  "edit-profile" => array(
    "en_US" => "Edit Profile",
    "es_ES" => "Editar Perfil",
    "de_DE" => "Profil bearbeiten",
  ),

  "error" => array(
    "en_US" => "Error",
    "es_ES" => "Error",
    "de_DE" => "Fehler",
  ),

  "not-confirmed" => array(
    "en_US" => "Not confirmed",
    "es_ES" => "No confirmado",
    "de_DE" => "Nicht bestätigt",
  ),

  "not-an-object" => array(
    "en_US" => "Not an object",
    "es_ES" => "No es un objeto",
    "de_DE" => "Kein Objekt",
  ),

  "conversation-exists" => array(
    "en_US" => "You already have a conversation with @",
    "es_ES" => "Ya tienes una conversación con @",
    "de_DE" => "Du hast bereits ein Gespräch mit @",
  ),

  "logout" => array(
    "en_US" => "Logout",
    "es_ES" => "Cerrar sesión",
    "de_DE" => "Abmelden",
  ),

  "loading-messages" => array(
    "en_US" => "Loading Messages",
    "es_ES" => "Cargando mensajes",
    "de_DE" => "Nachrichten werden geladen",
  ),

  "now-loading-section" => array(
    "en_US" => "Now loading section",
    "es_ES" => "Cargando sección",
    "de_DE" => "Bereich wird geladen",
  ),

  "wait-message-sending" => array(
    "en_US" => "Wait until your previous message sends",
    "es_ES" => "Espera hasta que se envíe tu mensaje anterior",
    "de_DE" => "Warte, bis deine vorherige Nachricht gesendet wird",
  ),

  "empty-message" => array(
    "en_US" => "You can't send an empty message",
    "es_ES" => "No puedes enviar un mensaje vacío",
    "de_DE" => "Du kannst keine leere Nachricht senden",
  ),

  "click-here-or-drag-drop" => array(
    "en_US" => "Click here or drag and drop",
    "es_ES" => "Haz clic aquí o arrastra y suelta",
    "de_DE" => "Klicke hier oder ziehe und lasse los",
  ),

  "upload-and-send" => array(
    "en_US" => "Upload and Send",
    "es_ES" => "Subir y Enviar",
    "de_DE" => "Hochladen und senden",
  ),

  "select-file" => array(
    "en_US" => "Please select a file",
    "es_ES" => "Por favor, selecciona un archivo",
    "de_DE" => "Bitte wähle eine Datei aus",
  ),

  "waiting" => array(
    "en_US" => "Waiting...",
    "es_ES" => "Esperando...",
    "de_DE" => "Warten...",
  ),

  "network-error" => array(
    "en_US" => "A network error has occurred. Some services may be unavailable.",
    "es_ES" => "Se ha producido un error de red. Algunos servicios pueden no estar disponibles.",
    "de_DE" => "Ein Netzwerkfehler ist aufgetreten. Einige Dienste sind möglicherweise nicht verfügbar.",
  ),

  "finalizing" => array(
    "en_US" => "Finalizing, please wait just a little longer...",
    "es_ES" => "Finalizando, por favor espera un poco más...",
    "de_DE" => "Abschließend, bitte warte noch einen Moment...",
  ),

  "uploaded-progress" => array(
    "en_US" => "% uploaded, please wait...",
    "es_ES" => "% subido, por favor espera...",
    "de_DE" => "% hochgeladen, bitte warten...",
  ),

  "failed-to-send-file" => array(
    "en_US" => "Failed to send file",
    "es_ES" => "No se pudo enviar el archivo",
    "de_DE" => "Senden der Datei fehlgeschlagen",
  ),

  "successfully-sent-file" => array(
    "en_US" => "Successfully sent file",
    "es_ES" => "Archivo enviado con éxito",
    "de_DE" => "Datei erfolgreich gesendet",
  ),

  "session-expired" => array(
    "en_US" => "Your session has expired. This page will reload momentarily.",
    "es_ES" => "Tu sesión ha caducado. Esta página se recargará en breve.",
    "de_DE" => "Ihre Sitzung ist abgelaufen. Diese Seite wird in Kürze neu geladen.",
  ),

  "error-code" => array(
    "en_US" => "Error code:",
    "es_ES" => "Código de error:",
    "de_DE" => "Fehlercode:",
  ),

  "websocket-connected" => array(
    "en_US" => "WEBSOCKET CONNECTED",
    "es_ES" => "WEBSOCKET CONECTADO",
    "de_DE" => "WEBSOCKET VERBUNDEN",
  ),

  "websocket-disconnected" => array(
    "en_US" => "WEBSOCKET DISCONNECTED",
    "es_ES" => "WEBSOCKET DESCONECTADO",
    "de_DE" => "WEBSOCKET GETRENNT",
  ),

  "ping" => array(
    "en_US" => "Ping!",
    "es_ES" => "¡Ping!",
    "de_DE" => "Ping!",
  ),

  "disconnected-reconnect" => array(
    "en_US" => "Disconnected! Attempting to reconnect...",
    "es_ES" => "¡Desconectado! Intentando reconectar...",
    "de_DE" => "Getrennt! Wiederherstellungsversuch...",
  ),

  "message" => array(
    "en_US" => "Message",
    "es_ES" => "Mensaje",
    "de_DE" => "Nachricht",
  ),

  "sending" => array(
    "en_US" => "Sending",
    "es_ES" => "Enviando",
    "de_DE" => "Senden",
  ),

  "settings" => array(
    "en_US" => "Settings",
    "es_ES" => "Configuración",
    "de_DE" => "Einstellungen",
  ),

  "invalid-data" => array(
    "en_US" => "Invalid data received!",
    "es_ES" => "¡Datos no válidos recibidos!",
    "de_DE" => "Ungültige Daten erhalten!",
  ),

  "client-disconnected" => array(
    "en_US" => "Client disconnected. TCP connection lost:",
    "es_ES" => "Cliente desconectado. Conexión TCP perdida:",
    "de_DE" => "Client getrennt. TCP-Verbindung verloren:",
  ),

  "socket-error" => array(
    "en_US" => "Socket error:",
    "es_ES" => "Error de socket:",
    "de_DE" => "Socket-Fehler:",
  ),

  "unusual-disconnect" => array(
    "en_US" => "Unusual disconnect on socket",
    "es_ES" => "Desconexión inusual en el socket",
    "de_DE" => "Ungewöhnliche Trennung am Socket",
  ),

  "failed" => array(
    "en_US" => "Failed",
    "es_ES" => "Fallido",
    "de_DE" => "Fehlgeschlagen",
  ),

);

$publicWords = [
  "dashboard",
  "who-recipient",
  "username",
  "cancel",
  "continue",
  "blank-username",
  "conversation-exists",
  "invalid-username-characters-to",
  "not-found",
  "not-found-title",
  "this-is-you",
  "cannot-message-yourself",
  "ok",
  "edit-profile",
  "error",
  "not-confirmed",
  "not-an-object",
  "conversation-exists",
  "logout",
  "loading-messages",
  "now-loading-section",
  "wait-message-sending",
  "empty-message",
  "click-here-or-drag-drop",
  "upload-and-send",
  "select-file",
  "waiting",
  "network-error",
  "finalizing",
  "uploaded-progress",
  "failed-to-send-file",
  "successfully-sent-file",
  "session-expired",
  "error-code",
  "websocket-connected",
  "websocket-disconnected",
  "ping",
  "disconnected-reconnect",
  "already-exists",
  "message",
  "sending",
  "settings",
];

if (!isset($language)) {
  $language = locale_get_default();
  if (($language == "en_001") || ($language == "en_150") || ($language == "en_US_POSIX")) {
    $language = "en_US";
  }
}

if (!in_array($language, $words["supported-languages-list"])) {
  $language = "en_US";
}

if (basename(__FILE__) != basename($_SERVER["SCRIPT_FILENAME"])) {

  function word($string)
  {
    global $words;
    global $language;
    try {
      return $words[$string][$language];
    } catch (Exception $e) { // Default to English
      return $words[$string]["en_US"];
    }
  }

  $htmlLang = explode("_", $language);
  $htmlLang = $htmlLang[0];
} else {
  require("../assets/configuration.php");
  if (!in_array($language, $words["supported-languages-list"])) {
    $language = "en_US";
  }
  $public = array();
  foreach ($publicWords as $word) {
    $public[$word] = $words[$word][$language];
  }
  $public = json_encode($public);
  die($public);
}
