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

    public static function getConnection(): ?PDO
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        $configs = [
            ['host' => self::$host, 'username' => self::$username, 'password' => self::$password],
            ['host' => self::$host, 'username' => 'root', 'password' => ''],
            ['host' => '127.0.0.1', 'username' => self::$username, 'password' => self::$password],
            ['host' => '127.0.0.1', 'username' => 'root', 'password' => ''],
        ];

        foreach ($configs as $config) {
            try {
                $serverDsn = 'mysql:host=' . $config['host'] . ';charset=' . self::$charset;
                $serverPdo = new PDO($serverDsn, $config['username'], $config['password'], $options);
                $serverPdo->exec('CREATE DATABASE IF NOT EXISTS ' . self::$dbName);

                $dsn = 'mysql:host=' . $config['host'] . ';dbname=' . self::$dbName . ';charset=' . self::$charset;
                self::$instance = new PDO($dsn, $config['username'], $config['password'], $options);
                self::$host = $config['host'];
                self::$username = $config['username'];
                self::$password = $config['password'];

                self::ensureSchema(self::$instance);
                return self::$instance;
            } catch (PDOException $e) {
                // lanjut ke konfigurasi berikutnya
            }
        }

        return null;
    }

    private static function ensureSchema(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nama VARCHAR(100) NOT NULL,
                username VARCHAR(50) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                role ENUM('admin','siswa') NOT NULL DEFAULT 'siswa',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS categories (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nama_kategori VARCHAR(50) NOT NULL UNIQUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS books (
                id INT AUTO_INCREMENT PRIMARY KEY,
                judul VARCHAR(150) NOT NULL,
                isbn VARCHAR(30) NOT NULL UNIQUE,
                category_id INT NOT NULL,
                stok INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_books_category FOREIGN KEY (category_id) REFERENCES categories(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS loans (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                book_id INT NOT NULL,
                tanggal_pinjam DATE NOT NULL,
                batas_kembali DATE NOT NULL,
                tanggal_kembali DATE DEFAULT NULL,
                status ENUM('dipinjam','dikembalikan','terlambat') NOT NULL DEFAULT 'dipinjam',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_loans_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                CONSTRAINT fk_loans_book FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // PENTING: hash bcrypt (mengandung karakter '$') TIDAK BOLEH ditulis langsung
        // di dalam string double-quote PHP ("...") karena '$' akan dianggap awal
        // variabel dan memicu "Undefined variable" warning yang merusak output JSON.
        // Solusi: pakai prepared statement dengan parameter terikat (bind),
        // supaya nilai hash dikirim sebagai DATA, bukan diproses sebagai kode PHP.
        $insertUser = $pdo->prepare(
            "INSERT IGNORE INTO users (id, nama, username, password, role)
             VALUES (:id, :nama, :username, :password, :role)"
        );

        $insertUser->execute([
            'id' => 1,
            'nama' => 'Admin',
            'username' => 'admin',
            'password' => password_hash('admin123', PASSWORD_DEFAULT),
            'role' => 'admin',
        ]);

        $insertUser->execute([
            'id' => 2,
            'nama' => 'Siswa',
            'username' => 'siswa',
            'password' => password_hash('siswa123', PASSWORD_DEFAULT),
            'role' => 'siswa',
        ]);
    }
}