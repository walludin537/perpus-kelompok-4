<?php
require_once __DIR__ . '/../../config/Database.php';

class User {
   
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

    public function verifyPassword($password, $storedPassword): bool
    {
        $password = (string) $password;
        $storedPassword = (string) $storedPassword;

        if ($password === '' || $storedPassword === '') {
            return false;
        }

        if (password_verify($password, $storedPassword)) {
            return true;
        }

        return $storedPassword === $password;
    }

    public function updatePassword($username, $password): bool
    {
        $username = strtolower(trim((string) $username));
        $hashedPassword = password_hash((string) $password, PASSWORD_DEFAULT);

        if ($this->db) {
            try {
                $stmt = $this->db->prepare("UPDATE users SET password = :password WHERE username = :username");
                $stmt->execute(['password' => $hashedPassword, 'username' => $username]);
                return true;
            } catch (PDOException $e) {
                return false;
            }
        }

        return false;
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

            $needsPasswordUpdate = !$this->verifyPassword($user['password'], $existing['password']);
            if ($existing['role'] !== $user['role'] || $needsPasswordUpdate) {
                if ($this->db) {
                    try {
                        $stmt = $this->db->prepare("UPDATE users SET role = :role, password = :password WHERE username = :username");
                        $stmt->execute([
                            'role' => $user['role'],
                            'password' => password_hash($user['password'], PASSWORD_DEFAULT),
                            'username' => $user['username'],
                        ]);
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