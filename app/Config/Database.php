<?php
namespace App\Config;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $connection = null;

    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            $host = 'localhost';
            $port = '3306';
            $db   = 'acc1';

            // عدّل هذه القيم لتطابق مستخدم قاعدة البيانات الجديد في shneler.com
            $user = 'acc1';
            $pass = '123@123_*8';

            $charset = 'utf8mb4';
            $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$connection = new PDO($dsn, $user, $pass, $options);
            } catch (PDOException $e) {
                http_response_code(500);
                header('Content-Type: text/plain; charset=utf-8');
                echo "Database connection failed:\n" . $e->getMessage();
                exit;
            }
        }

        return self::$connection;
    }
}