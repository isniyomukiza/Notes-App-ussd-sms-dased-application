<?php
class util
{
    static $goback = 98;
    static $goToMainMenu = 99;
    static $apiKey = "atsk_c8db5806ce2780f767f0132c9f7c7e12b10ca09b6ddbe1bc23c932ca2cf043075e73ce1d";
    static $username = "sandbox"; // Use your Africa's Talking username
    static $COMPANY_NAME = "Notes App LTD";
    static function getDbConnection()
    {
        $host = 'localhost';
        $db = 'notes_app';
        $user = 'root'; // Change if your DB user is different
        $pass = '';
        $charset = 'utf8mb4';
        $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        try {
            return new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            throw new RuntimeException('Database connection failed: ' . $e->getMessage());
        }
    }
}
?>