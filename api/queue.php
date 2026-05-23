<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/database.php';

$input  = json_decode(file_get_contents('php://input'), true);
$action = strtoupper($input['action'] ?? '');
$sessId = (int)($input['session_id'] ?? 1);

try {
    $db = getDB();

    switch ($action) {

        case 'ENQUEUE':
            $value    = trim($input['value'] ?? '');
            $priority = (int)($input['priority'] ?? 0);
            if ($value === '') {
                echo json_encode(['success' => false, 'message' => 'Nilai tidak boleh kosong']);
                exit;
            }

            // Cek kapasitas
            $total = $db->prepare("SELECT COUNT(*) FROM queue_items WHERE session_id = ? AND status != 'done'");
            $total->execute([$sessId]);
            $count = (int)$total->fetchColumn();

            $maxSize = $db->prepare("SELECT max_size FROM queue_sessions WHERE id = ?");
            $maxSize->execute([$sessId]);
            $max = (int)$maxSize->fetchColumn();

            if ($count >= $max) {
                echo json_encode(['success' => false, 'message' => "Antrean penuh! Maksimum $max item"]);
                exit;
            }

            $stmt = $db->prepare("INSERT INTO queue_items (session_id, value, priority, status) VALUES (?, ?, ?, 'waiting')");
            $stmt->execute([$sessId, $value, $priority]);

            $log = $db->prepare("INSERT INTO queue_log (session_id, action, value) VALUES (?, 'ENQUEUE', ?)");
            $log->execute([$sessId, $value]);

            echo json_encode([
                'success' => true,
                'message' => "ENQUEUE: \"$value\" masuk antrean",
                'items'   => getQueueItems($db, $sessId)
            ]);
            break;

        case 'DEQUEUE':
            // Ambil item terdepan (FIFO) — status waiting, id terkecil
            $stmt = $db->prepare("SELECT * FROM queue_items WHERE session_id = ? AND status = 'waiting' ORDER BY id ASC LIMIT 1");
            $stmt->execute([$sessId]);
            $front = $stmt->fetch();

            if (!$front) {
                echo json_encode(['success' => false, 'message' => 'Antrean kosong, tidak ada yang bisa di-DEQUEUE']);
                exit;
            }

            // Update status jadi done
            $upd = $db->prepare("UPDATE queue_items SET status = 'done', dequeued_at = NOW() WHERE id = ?");
            $upd->execute([$front['id']]);

            $log = $db->prepare("INSERT INTO queue_log (session_id, action, value) VALUES (?, 'DEQUEUE', ?)");
            $log->execute([$sessId, $front['value']]);

            echo json_encode([
                'success'   => true,
                'message'   => "DEQUEUE: \"{$front['value']}\" keluar dari antrean",
                'dequeued'  => $front['value'],
                'items'     => getQueueItems($db, $sessId)
            ]);
            break;

        case 'PEEK':
            $stmt = $db->prepare("SELECT * FROM queue_items WHERE session_id = ? AND status = 'waiting' ORDER BY id ASC LIMIT 1");
            $stmt->execute([$sessId]);
            $front = $stmt->fetch();

            if (!$front) {
                echo json_encode(['success' => false, 'message' => 'Antrean kosong']);
                exit;
            }

            $log = $db->prepare("INSERT INTO queue_log (session_id, action, value) VALUES (?, 'PEEK', ?)");
            $log->execute([$sessId, $front['value']]);

            echo json_encode([
                'success' => true,
                'message' => "PEEK: \"{$front['value']}\" ada di depan antrean",
                'front'   => $front['value']
            ]);
            break;

        case 'CLEAR':
            $stmt = $db->prepare("DELETE FROM queue_items WHERE session_id = ?");
            $stmt->execute([$sessId]);

            $log = $db->prepare("INSERT INTO queue_log (session_id, action, value) VALUES (?, 'CLEAR', NULL)");
            $log->execute([$sessId]);

            echo json_encode([
                'success' => true,
                'message' => 'Antrean berhasil dikosongkan',
                'items'   => []
            ]);
            break;

            case 'PROCESS':
    $itemId = (int)($input['item_id'] ?? 0);
    $stmt = $db->prepare("UPDATE queue_items SET status = 'processing' WHERE id = ? AND session_id = ?");
    $stmt->execute([$itemId, $sessId]);
    $log = $db->prepare("INSERT INTO queue_log (session_id, action, value) VALUES (?, 'DEQUEUE', ?)");
    $val = $db->query("SELECT value FROM queue_items WHERE id = $itemId")->fetchColumn();
    $log->execute([$sessId, $val]);
    echo json_encode([
        'success' => true,
        'message' => "Item sedang diproses",
        'items'   => getQueueItems($db, $sessId)
    ]);
    break;
    

        case 'CLEAR_LOG':
            $stmt = $db->prepare("DELETE FROM queue_log WHERE session_id = ?");
            $stmt->execute([$sessId]);
            echo json_encode(['success' => true, 'message' => 'Log berhasil dihapus']);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Action tidak dikenal']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'DB Error: ' . $e->getMessage()]);
}

function getQueueItems(PDO $db, int $sessId): array {
    $stmt = $db->prepare("SELECT * FROM queue_items WHERE session_id = ? ORDER BY id ASC");
    $stmt->execute([$sessId]);
    return $stmt->fetchAll();
}