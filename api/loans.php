<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../app/controller/LoanController.php';
require_once __DIR__ . '/../config/Auth.php';

$method = $_SERVER ['REQUEST_METHOD'];
$id = isset($_GET['id']) ? (int) $_GET ['id'] : null;
$data = json_decode(file_get_contents('php://input'), true) ?? [];

$controller = new LoanController();

switch ($method) {
    case 'GET':
        $payload = Auth::check();
        if ($payload['role'] === 'admin' && !isset($_GET['mine'])) {
            //admin melihat semua peminjaman
            $controller->index();
        }else{
            //siswa hanya melihat peminjaman miliknya sendiri
            $controller->myLoans((int) $payload['id']);
        }
        break;
    case 'POST':
        //siswa mengajukan peminjaman
        $payload = Auth::checkRole('siswa');
        $controller->store((int) $payload['id'], $data);
        break;
    
    case 'PUT':
        Auth::checkRole('admin');
        if (!$id) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'ID peminjaman wajib disertakan']);
            break;
        }

        $controller->markReturned($id);
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan']);
}