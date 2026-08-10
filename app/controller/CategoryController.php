<?php
// Controller untuk mengelola data kategori buku.
require_once __DIR__ . '/../models/Category.php';

class CategoryController
{
    private $categoryModel;

    public function __construct()
    {
        if (class_exists('Category')) {
            $this->categoryModel = new Category();
        } else {
            $this->categoryModel = null;
        }
    }

    public function index(): void
    {
        if (!$this->categoryModel) {
            $this->respond(false, 'Model kategori belum tersedia', null, 500);
            return;
        }

        $categories = $this->categoryModel->getAll();
        $this->respond(true, 'Daftar kategori berhasil diambil', $categories);
    }

    public function store(array $data): void
    {
        if (!$this->categoryModel) {
            $this->respond(false, 'Model kategori belum tersedia', null, 500);
            return;
        }

        $nama = trim($data['nama_kategori'] ?? '');
        if ($nama === '') {
            $this->respond(false, 'Nama kategori wajib diisi', null, 422);
            return;
        }

        $id = $this->categoryModel->create($nama);
        $this->respond(true, 'Kategori berhasil ditambahkan', ['id' => $id], 201);
    }

    public function update(int $id, array $data): void
    {
        if (!$this->categoryModel) {
            $this->respond(false, 'Model kategori belum tersedia', null, 500);
            return;
        }

        if (!$this->categoryModel->getById($id)) {
            $this->respond(false, 'Kategori tidak ditemukan', null, 404);
            return;
        }

        $nama = trim($data['nama_kategori'] ?? '');
        if ($nama === '') {
            $this->respond(false, 'Nama kategori wajib diisi', null, 422);
            return;
        }

        $this->categoryModel->update($id, $nama);
        $this->respond(true, 'Kategori berhasil diperbarui');
    }

    public function destroy(int $id): void
    {
        if (!$this->categoryModel) {
            $this->respond(false, 'Model kategori belum tersedia', null, 500);
            return;
        }

        if (!$this->categoryModel->getById($id)) {
            $this->respond(false, 'Kategori tidak ditemukan', null, 404);
            return;
        }

        try {
            $this->categoryModel->delete($id);
            $this->respond(true, 'Kategori berhasil dihapus');
        } catch (PDOException $e) {
            $this->respond(false, 'Kategori masih dipakai oleh buku lain, tidak bisa dihapus', null, 409);
        }
    }

    private function respond(bool $success, string $message, $data = null, int $code = 200): void
    {
        http_response_code($code);
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'data' => $data,
        ]);
    }
}
