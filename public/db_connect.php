<?php
    $hn = 'localhost';
    $un = 'hh223ji';
    $pw = 'Dpp4CE2E';
    $db = 'hh223ji';

    $conn = new mysqli($hn, $un, $pw, $db);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    function queryMySQL($query){
        global $conn;
        $result = $conn->query($query);
        return $result;
    }

    function sanitizeString($str){
        global $conn;
        $str = strip_tags($str);
        $str = htmlentities($str);
        $str = stripslashes($str);
        return $conn->real_escape_string($str);
    };
?>
