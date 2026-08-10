<?php
require_once __DIR__ . '/../../config/Database.php';

class Category
{

    private $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

       public function getAll(): array
     {
         $stmt = $this->db->query("SELECT * FROM categories ORDER BY nama_kategori ASC");
         return $stmt->fetchAll(PDO::FETCH_ASSOC);
     }

   
    public function getById(int $id)
    {
        $stmt = $this->db->prepare("SELECT * FROM categories WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create(string $namaKategori): int
    {
        $stmt = $this->db->prepare("INSERT INTO categories (nama_kategori) VALUES (:nama_kategori)");
        $stmt->execute(['nama_kategori' => $namaKategori]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, string $namaKategori): bool
    {
        $stmt = $this->db->prepare("UPDATE categories SET nama_kategori = :nama_kategori WHERE id = :id");
        return $stmt->execute(['nama_kategori' => $namaKategori, 'id' => $id]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM categories WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
