<?php
// TMP FOR TESTING
    session_start();
    unset($_SESSION["id"]);
    unset($_SESSION["token"]);
    header("Location: ./");
?>