<?php 
require_once __DIR__ . '/../../config/Database.php';

class Loan{
    /** @var \PDO */
    private $db;
    private const MAX_LOAN_PER_USER = 3;
    private const LOAN_DURATION_DAYS = 14;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }
    private function baseQuery(): string
    {
        return 'SELECT loans.*, users.nama AS nama_siswa, books.judul, books.isbn
                FROM loans
                JOIN users ON loans.user_id = users.id
                JOIN books ON loans.book_id = books.id';
    }
    /**
     * @param string|null $status
     * @return array
     */
    public function getAll(?string $status = null): array
    {
        $sql = $this->baseQuery();
        $params = [];

        if ($status) {
            $sql .= ' WHERE loans.status = :status';
            $params['status'] = $status;
        }
        $sql .= ' ORDER BY loans.tanggal_pinjam DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getByUserId(int $userId, ?string $status = null): array
    {
        $sql = $this->baseQuery() . ' WHERE loans.user_id = :user_id';
        $params = ['user_id' => $userId];

        if ($status) {
            $sql .= ' AND loans.status = :status';
            $params['status'] = $status;
        }

        $sql .= ' ORDER BY loans.tanggal_pinjam DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function countActiveByUser(int $userId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) AS total FROM loans WHERE user_id = :user_id AND status IN ("dipinjam", "terlambat")');
        $stmt->execute(['user_id' => $userId]);
        $count = $stmt->fetchColumn();
        return (int)$count;
    }
    public function getMaxLoanPerUser(): int
    {
        return self::MAX_LOAN_PER_USER;
    }
    public function create(int $userId, int $bookId): int
    {
       $tanggalPinjam = date('Y-m-d');
       $batasKembali = date('Y-m-d', strtotime("+".self::LOAN_DURATION_DAYS." days"));

       $stmt = $this->db->prepare('INSERT INTO loans (user_id, book_id, tanggal_pinjam, batas_kembali, status) VALUES (:user_id, :book_id, :tanggal_pinjam, :batas_kembali, "dipinjam")');
       $stmt->execute([
           'user_id' => $userId,
           'book_id' => $bookId,
           'tanggal_pinjam' => $tanggalPinjam,
           'batas_kembali' => $batasKembali
        ]);
        return (int)$this->db->lastInsertId();
    }
    /**
     * @param int $id
     * @return array|false
     */
    public function getById(int $id)
    {
        $stmt = $this->db->prepare($this->baseQuery() . ' WHERE loans.id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function markReturned(int $id): bool
    {
        $stmt = $this->db->prepare('UPDATE loans SET status = "dikembalikan", tanggal_kembali = :tanggal_kembali WHERE id = :id');
        return $stmt->execute([
            'tanggal_kembali' => date('Y-m-d'),
            'id' => $id
        ]);
    }
    /**
     * Menandai peminjaman yang lewat batas_kembali sebagai "terlambat".
     * Sebaiknya di panggil sebelum menampilkan data peminjaman.
     */
    /**
     * Update overdue statuses.
     *
     * @return void
     */
    public function updateOverdueStatuses()
    {
        $stmt = $this->db->prepare('UPDATE loans SET status = "terlambat" WHERE status = "dipinjam" AND batas_kembali < :today');
        $stmt->execute(['today' => date('Y-m-d')]);
    }
}