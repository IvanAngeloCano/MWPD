<?php
<<<<<<< HEAD
// connection.php: Secure PDO connection
$host = 'localhost';
$db   = 'MWPD';
$user = 'root'; // Change as needed
$pass = '';     // Change as needed
=======

$host = 'localhost';
$db   = 'MWPD';
$user = 'root'; 
$pass = '';     
>>>>>>> e676bef (Initial commit on updated_BM)
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    throw new PDOException($e->getMessage(), (int)$e->getCode());
}
?>
