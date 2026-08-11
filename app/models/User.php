<?php
require_once __DIR__ . '/../../config/Database.php';

class User {
    /**
     * @var PDO|null
     */
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function findByUsername($username)
    {
        $username = strtolower(trim((string) $username));

        if ($this->db) {
            try {
                $stmt = $this->db->prepare("SELECT * FROM users WHERE username = :username");
                $stmt->execute(['username' => $username]);
                $user = $stmt->fetch();
                if ($user) {
                    return $user;
                }
            } catch (PDOException $e) {
                // fallback ke akun bawaan jika database tidak tersedia
            }
        }

        $fallbackUsers = self::fallbackUsers();
        return $fallbackUsers[$username] ?? null;
    }

    public function create($nama, $username, $password, $role = 'siswa')
    {
        $username = strtolower(trim((string) $username));

        if ($this->db) {
            try {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $this->db->prepare("INSERT INTO users (nama, username, password, role) VALUES (:nama, :username, :password, :role)");
                $stmt->execute(['nama' => $nama, 'username' => $username, 'password' => $hashed, 'role' => $role]);
                return (int)$this->db->lastInsertId();
            } catch (PDOException $e) {
                // lanjut ke fallback
            }
        }

        $fallbackUsers = self::fallbackUsers();
        $fallbackUsers[$username] = [
            'id' => count($fallbackUsers) + 1,
            'nama' => $nama,
            'username' => $username,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role,
        ];

        return $fallbackUsers[$username]['id'];
    }

    public function ensureDefaultUsers()
    {
        $defaultUsers = [
            ['nama' => 'Admin', 'username' => 'admin', 'password' => 'admin123', 'role' => 'admin'],
            ['nama' => 'Siswa', 'username' => 'siswa', 'password' => 'siswa123', 'role' => 'siswa'],
        ];

        foreach ($defaultUsers as $user) {
            $existing = $this->findByUsername($user['username']);
            if (!$existing) {
                $this->create($user['nama'], $user['username'], $user['password'], $user['role']);
                continue;
            }

            if ($existing['role'] !== $user['role']) {
                if ($this->db) {
                    try {
                        $stmt = $this->db->prepare("UPDATE users SET role = :role WHERE username = :username");
                        $stmt->execute(['role' => $user['role'], 'username' => $user['username']]);
                    } catch (PDOException $e) {
                        // abaikan
                    }
                }
            }
        }
    }

    private static function fallbackUsers(): array
    {
        return [
            'admin' => [
                'id' => 1,
                'nama' => 'Admin',
                'username' => 'admin',
                'password' => password_hash('admin123', PASSWORD_DEFAULT),
                'role' => 'admin',
            ],
            'siswa' => [
                'id' => 2,
                'nama' => 'Siswa',
                'username' => 'siswa',
                'password' => password_hash('siswa123', PASSWORD_DEFAULT),
                'role' => 'siswa',
            ],
        ];
    }
}
?>