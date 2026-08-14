<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../../config/Jwt.php';

class AuthController
{
    private $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    public function login(array $data): void
    {
        $username = trim($data['username'] ?? '');
        $password = trim($data['password'] ?? '');

        if ($username === '' || $password === '') {
            $this->respond(false, 'Username dan kata sandi wajib diisi', null, 422);
            return;
        }

        $user = $this->userModel->findByUsername($username);

        if (!$user || !password_verify($password, $user['password'])) {
            $this->respond(false, 'Username atau kata sandi salah', null, 401);
            return;
        }

        $token = Jwt::encode([
            'id'       => $user['id'],
            'username' => $user['username'],
            'role'     => $user['role'],
        ]);

        $this->respond(true, 'Login berhasil', [
            'token' => $token,
            'user'  => [
                'id'       => $user['id'],
                'nama'     => $user['nama'],
                'username' => $user['username'],
                'role'     => $user['role'],
            ],
        ]);
    }

    public function register(array $data): void
    {
        $nama     = trim($data['nama'] ?? '');
        $username = trim($data['username'] ?? '');
        $password = trim($data['password'] ?? '');
        $role     = $data['role'] ?? 'siswa';

        if ($nama === '' || $username === '' || $password === '') {
            $this->respond(false, 'Semua field wajib diisi', null, 422);
            return;
        }

        if ($this->userModel->findByUsername($username)) {
            $this->respond(false, 'Username sudah digunakan', null, 409);
            return;
        }

        $id = $this->userModel->create($nama, $username, $password, $role);
        $this->respond(true, 'Akun berhasil dibuat', ['id' => $id], 201);
    }

    private function respond(bool $success, string $message, $data = null, int $code = 200): void
    {
        http_response_code($code);
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'data'    => $data,
        ]);
    }
}
