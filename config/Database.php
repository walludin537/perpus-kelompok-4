<?php
class Database
{
    private static $instance = null;
    private static $host = 'localhost';
    private static $username = 'root';
    private static $password = 'rpl12345';
    private static $dbName = 'perpus_kelompok4';
    private static $charset = 'utf8mb4';

    public function __construct()
    {
        // mencegah instansi langsung
    }

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $dsn = "mysql:host=" . self::$host . ";dbname=" . self::$dbName . ";charset=" . self::$charset;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            try {
                self::$instance = new PDO($dsn, self::$username, self::$password, $options);
            } catch (PDOException $e) {
                http_response_code(500);
                die(json_encode([
                    'success' => false,
                    'message' => 'koneksi database gagal: ' . $e->getMessage(),
                ]));
            }
        }

        return self::$instance;
    }
}