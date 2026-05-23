<?php
// api/stack.php
// API endpoint untuk operasi Stack (PUSH, POP, PEEK, CLEAR)

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/database.php';

$input  = json_decode(file_get_contents('php://input'), true);
$action = strtoupper($input['action'] ?? '');
$sessId = (int)($input['session_id'] ?? 1);

try {
    $db = getDB();

    switch ($action) {

        // =========================================
        // PUSH — tambah item ke atas stack
        // =========================================
        case 'PUSH':
            $value = trim($input['value'] ?? '');
            if ($value === '') {
                echo json_encode(['success' => false, 'message' => 'Nilai tidak boleh kosong']);
                exit;
            }

            // Hitung posisi baru (top of stack)
            $maxPos = $db->prepare("SELECT COALESCE(MAX(position), -1) + 1 FROM stack_items WHERE session_id = ?");
            $maxPos->execute([$sessId]);
            $newPos = (int)$maxPos->fetchColumn();

            // Insert item baru
            $stmt = $db->prepare("INSERT INTO stack_items (session_id, value, position) VALUES (?, ?, ?)");
            $stmt->execute([$sessId, $value, $newPos]);

            // Catat ke log
            $log = $db->prepare("INSERT INTO stack_log (session_id, action, value) VALUES (?, 'PUSH', ?)");
            $log->execute([$sessId, $value]);

            // Ambil semua item terbaru
            $items = getStackItems($db, $sessId);

            echo json_encode([
                'success' => true,
                'message' => "PUSH: \"$value\" masuk ke stack",
                'items'   => $items,
                'top'     => end($items) ?: null
            ]);
            break;

        // =========================================
        // POP — hapus item teratas stack
        // =========================================
        case 'POP':
            // Ambil item teratas (posisi MAX)
            $stmt = $db->prepare("SELECT * FROM stack_items WHERE session_id = ? ORDER BY position DESC LIMIT 1");
            $stmt->execute([$sessId]);
            $top = $stmt->fetch();

            if (!$top) {
                echo json_encode(['success' => false, 'message' => 'Stack kosong, tidak ada yang bisa di-POP']);
                exit;
            }

            // Hapus item teratas
            $del = $db->prepare("DELETE FROM stack_items WHERE id = ?");
            $del->execute([$top['id']]);

            // Catat ke log
            $log = $db->prepare("INSERT INTO stack_log (session_id, action, value) VALUES (?, 'POP', ?)");
            $log->execute([$sessId, $top['value']]);

            $items = getStackItems($db, $sessId);

            echo json_encode([
                'success' => true,
                'message' => "POP: \"{$top['value']}\" keluar dari stack",
                'popped'  => $top['value'],
                'items'   => $items,
                'top'     => end($items) ?: null
            ]);
            break;

        // =========================================
        // PEEK — lihat item teratas tanpa hapus
        // =========================================
        case 'PEEK':
            $stmt = $db->prepare("SELECT * FROM stack_items WHERE session_id = ? ORDER BY position DESC LIMIT 1");
            $stmt->execute([$sessId]);
            $top = $stmt->fetch();

            if (!$top) {
                echo json_encode(['success' => false, 'message' => 'Stack kosong']);
                exit;
            }

            // Catat ke log
            $log = $db->prepare("INSERT INTO stack_log (session_id, action, value) VALUES (?, 'PEEK', ?)");
            $log->execute([$sessId, $top['value']]);

            echo json_encode([
                'success' => true,
                'message' => "PEEK: \"{$top['value']}\" ada di puncak stack",
                'top'     => $top['value']
            ]);
            break;

        // =========================================
        // CLEAR — hapus semua item dalam stack
        // =========================================
        case 'CLEAR':
            $stmt = $db->prepare("DELETE FROM stack_items WHERE session_id = ?");
            $stmt->execute([$sessId]);

            $log = $db->prepare("INSERT INTO stack_log (session_id, action, value) VALUES (?, 'CLEAR', NULL)");
            $log->execute([$sessId]);

            echo json_encode([
                'success' => true,
                'message' => 'Stack berhasil dikosongkan',
                'items'   => []
            ]);
            break;

        case 'CLEAR_LOG':
    $stmt = $db->prepare("DELETE FROM stack_log WHERE session_id = ?");
    $stmt->execute([$sessId]);
    echo json_encode(['success' => true, 'message' => 'Log berhasil dihapus']);
    break;

case 'CLEAR_LOG':
    $stmt = $db->prepare("DELETE FROM stack_log WHERE session_id = ?");
    $stmt->execute([$sessId]);
    echo json_encode(['success' => true, 'message' => 'Log berhasil dihapus']);
    break;

default:
    echo json_encode(['success' => false, 'message' => 'Action tidak dikenal']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'DB Error: ' . $e->getMessage()]);
}

// =========================================
// HELPER — ambil semua item stack terurut
// =========================================
function getStackItems(PDO $db, int $sessId): array {
    $stmt = $db->prepare("SELECT * FROM stack_items WHERE session_id = ? ORDER BY position ASC");
    $stmt->execute([$sessId]);
    return $stmt->fetchAll();
}