<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../app/models/Book.php';
require_once __DIR__ . '/../config/Auth.php';

class BookController
{
    private $bookModel;

    public function __construct()
    {
        $this->bookModel = new Book();
    }

    public function index(): void
    {
        $search = trim($_GET['search'] ?? '');
        $categoryId = isset($_GET['category_id']) ? (int) $_GET['category_id'] : null;
        $books = $this->bookModel->getAll($search !== '' ? $search : null, $categoryId ?: null);
        $this->respond(true, 'Daftar buku berhasil diambil', $books);
    }

    public function show(int $id): void
    {
        $book = $this->bookModel->getById($id);
        if (!$book) {
            $this->respond(false, 'Buku tidak ditemukan', null, 404);
            return;
        }

        $this->respond(true, 'Detail buku berhasil diambil', $book);
    }

    public function store(array $data): void
    {
        try {
            $judul = trim($data['judul'] ?? $data['title'] ?? '');
            $isbn = trim($data['isbn'] ?? '');
            $categoryId = (int) ($data['category_id'] ?? $data['categoryId'] ?? 0);
            $stock = (int) ($data['stok'] ?? $data['stock'] ?? 0);

            if ($judul === '' || $isbn === '' || $categoryId <= 0 || $stock < 0) {
                $this->respond(false, 'Judul, ISBN, kategori, dan stok wajib diisi dengan benar', null, 422);
                return;
            }

            if ($this->bookModel->isIsbnExists($isbn)) {
                $this->respond(false, 'ISBN sudah digunakan', null, 409);
                return;
            }

            $id = $this->bookModel->create($judul, $isbn, $categoryId, $stock);
            $this->respond(true, 'Buku berhasil ditambahkan', ['id' => $id], 201);
        } catch (PDOException $e) {
            $this->respond(false, 'Gagal menyimpan buku ke database: ' . $e->getMessage(), null, 500);
        }
    }

    public function update(int $id, array $data): void
    {
        $book = $this->bookModel->getById($id);
        if (!$book) {
            $this->respond(false, 'Buku tidak ditemukan', null, 404);
            return;
        }

        $judul = trim($data['judul'] ?? '');
        $isbn = trim($data['isbn'] ?? '');
        $categoryId = (int) ($data['category_id'] ?? 0);
        $stock = (int) ($data['stok'] ?? 0);

        if ($judul === '' || $isbn === '' || $categoryId <= 0 || $stock < 0) {
            $this->respond(false, 'Judul, ISBN, kategori, dan stok wajib diisi dengan benar', null, 422);
            return;
        }

        $db = Database::getConnection();
        if (!$db) {
            $this->respond(false, 'Database tidak tersedia', null, 500);
            return;
        }

        if ($this->bookModel->isIsbnExists($isbn, $id)) {
            $this->respond(false, 'ISBN sudah digunakan', null, 409);
            return;
        }

        $ok = $this->bookModel->update($id, $judul, $isbn, $categoryId, $stock);
        $this->respond($ok, $ok ? 'Buku berhasil diperbarui' : 'Gagal memperbarui buku', null, $ok ? 200 : 500);
    }

    public function destroy(int $id): void
    {
        $book = $this->bookModel->getById($id);
        if (!$book) {
            $this->respond(false, 'Buku tidak ditemukan', null, 404);
            return;
        }

        $ok = $this->bookModel->delete($id);
        $this->respond($ok, $ok ? 'Buku berhasil dihapus' : 'Gagal menghapus buku', null, $ok ? 200 : 500);
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

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$data = json_decode(file_get_contents('php://input'), true);

if (!is_array($data) || count($data) === 0) {
    $data = $_POST;
    if (!is_array($data) || count($data) === 0) {
        $rawBody = file_get_contents('php://input');
        parse_str($rawBody, $data);
    }
}

if (!is_array($data)) {
    $data = [];
}

$controller = new BookController();

switch ($method) {
    case 'GET':
        Auth::check();
        $id ? $controller->show($id) : $controller->index();
        break;

    case 'POST':
        Auth::checkRole('admin');
        $controller->store($data);
        break;

    case 'PUT':
        Auth::checkRole('admin');
        if (!$id) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'ID buku wajib disertakan']);
            break;
        }
        $controller->update($id, $data);
        break;

    case 'DELETE':
        Auth::checkRole('admin');
        if (!$id) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'ID buku wajib disertakan']);
            break;
        }
        $controller->destroy($id);
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan']);
}