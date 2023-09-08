<?php

    class WebSocketUser {
        public $socket;
        public $id;
        public $headers = array();
        public $handshake = false;
        
        public $handlingPartialPacket = false;
        public $partialBuffer = "";
        
        public $sendingContinuous = false;
        public $partialMessage = "";
        
        public $hasSentClose = false;
        
        function __construct($id, $socket) {
            $this->id = $id;
            $this->socket = $socket;
        }
    }
    
    class SpiteUser extends WebSocketUser {
        public $sessId;
        public $sessToken;
        
        public $username;
        public $name;
        public $picture;
        
        function __construct($id, $socket) {
            parent::__construct($id, $socket);
        }
    }

?>