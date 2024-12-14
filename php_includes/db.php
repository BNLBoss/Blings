<?php

$host = 'localhost';
$dbname = 'citizen_participation_db';
$username = 'raphaelchinjamb';
$password = '@icuzambia';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    header("Location: ../index.html");
}

?>