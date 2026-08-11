<?php
require_once __DIR__ . '/../../config/Database.php';

class Book
{
    
    private $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function baseQuery(): string
    {
        return 'SELECT books.*, categories.nama_kategori
                FROM books
                JOIN categories ON books.category_id = categories.id';
    }

    public function getAll(?string $search = null, ?int $categoryId = null): array
    {
        $sql = $this->baseQuery();
        $params = [];
        $conditions = [];

        if ($search) {
            $conditions[] = '(books.judul LIKE :search OR books.isbn LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        if ($categoryId) {
            $conditions[] = 'books.category_id = :category_id';
            $params['category_id'] = $categoryId;
        }

        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql.=' ORDER BY books.judul ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

   
    public function getById(int $id)
    {
        $stmt = $this->db->prepare($this->baseQuery() . ' WHERE books.id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Ambil data buku dengan row lock (FOR UPDATE).
     * Dipakai di dalam transaksi peminjaman agar stock tidak berkurang ganda
     * kalau ada yang mengajukan pinjam buku yang sama secara bersamaan.
     */

    public function getByIdForUpdate(int $id)
    {
        $stmt = $this->db->prepare('SELECT * FROM books WHERE id = :id FOR UPDATE');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function isIsbnExists(string $isbn, ?int $excludeId = null): bool
    {
        $sql = 'SELECT id FROM books WHERE isbn = :isbn';
        $params = ['isbn' => $isbn];

        if ($excludeId) {
            $sql .= ' AND id != :id';
            $params['id'] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (bool) $stmt->fetch();
    }
    public function create(string $judul, string $isbn, int $categoryId, int $stock): int
    {
        $stmt = $this->db->prepare("INSERT INTO books (judul, isbn, category_id, stok) VALUES (:judul, :isbn, :category_id, :stok)");
        $stmt->execute([
            'judul' => $judul,
            'isbn' => $isbn,
            'category_id' => $categoryId,
            'stok' => $stock
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, string $judul, string $isbn, int $categoryId, int $stock): bool
    {
        $stmt = $this->db->prepare("UPDATE books SET judul = :judul, isbn = :isbn, category_id = :category_id, stok = :stok WHERE id = :id");
        return $stmt->execute([
            'id' => $id,
            'judul' => $judul,
            'isbn' => $isbn,
            'category_id' => $categoryId,
            'stok' => $stock
        ]);
    }
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM books WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
    public function decreaseStock(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE books SET stok = stok - 1 WHERE id = :id AND stok > 0");
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function increaseStock(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE books SET stok = stok + 1 WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }

}