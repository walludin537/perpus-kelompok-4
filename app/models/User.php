<?php
require_once __DIR__ . '/../../config/Database.php';

class User {
    /**
     * @var PDO
     */
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

 
    public function findByUsername($username)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->execute(['username' => $username]);
        return $stmt->fetch();
    }

    
    public function create($nama, $username, $password, $role = 'siswa')
    {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("INSERT INTO users (nama, username, password, role) VALUES (:nama, :username, :password, :role)");
        $stmt->execute(['nama' => $nama, 'username' => $username, 'password' => $hashed, 'role' => $role]);
        return (int)$this->db->lastInsertId();
    }

    public function ensureDefaultUsers()
    {
        $defaultUsers = [
            ['nama' => 'Admin', 'username' => 'admin', 'password' => 'admin123', 'role' => 'admin'],
            ['nama' => 'Siswa', 'username' => 'siswa', 'password' => 'siswa123', 'role' => 'siswa'],
        ];

        foreach ($defaultUsers as $user) {
            if (!$this->findByUsername($user['username'])) {
                $this->create($user['nama'], $user['username'], $user['password'], $user['role']);
            }
        }
    }
}
?>