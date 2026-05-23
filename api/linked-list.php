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

        case 'INSERT_HEAD':
            $title  = trim($input['title'] ?? '');
            $artist = trim($input['artist'] ?? 'Unknown');
            $duration = trim($input['duration'] ?? '0:00');
            if ($title === '') {
                echo json_encode(['success' => false, 'message' => 'Judul tidak boleh kosong']);
                exit;
            }

            // Geser semua posisi +1
            $db->prepare("UPDATE playlist_nodes SET position = position + 1 WHERE session_id = ?")->execute([$sessId]);

            // Insert node baru di posisi 0
            $stmt = $db->prepare("INSERT INTO playlist_nodes (session_id, title, artist, duration, position, prev_id, next_id) VALUES (?, ?, ?, ?, 0, NULL, NULL)");
            $stmt->execute([$sessId, $title, $artist, $duration]);
            $newId = (int)$db->lastInsertId();

            // Sambungkan ke node lama head
            $oldHead = $db->prepare("SELECT id FROM playlist_nodes WHERE session_id = ? AND position = 1");
            $oldHead->execute([$sessId]);
            $oldHeadId = $oldHead->fetchColumn();

            if ($oldHeadId) {
                $db->prepare("UPDATE playlist_nodes SET next_id = ? WHERE id = ?")->execute([$oldHeadId, $newId]);
                $db->prepare("UPDATE playlist_nodes SET prev_id = ? WHERE id = ?")->execute([$newId, $oldHeadId]);
            }

            $log = $db->prepare("INSERT INTO playlist_log (session_id, action, value) VALUES (?, 'INSERT_HEAD', ?)");
            $log->execute([$sessId, $title]);

            echo json_encode(['success' => true, 'message' => "INSERT HEAD: \"$title\" ditambahkan di awal", 'nodes' => getNodes($db, $sessId)]);
            break;

        case 'INSERT_TAIL':
            $title    = trim($input['title'] ?? '');
            $artist   = trim($input['artist'] ?? 'Unknown');
            $duration = trim($input['duration'] ?? '0:00');
            if ($title === '') {
                echo json_encode(['success' => false, 'message' => 'Judul tidak boleh kosong']);
                exit;
            }

            // Cari posisi terakhir
            $maxPos = $db->prepare("SELECT COALESCE(MAX(position), -1) + 1 FROM playlist_nodes WHERE session_id = ?");
            $maxPos->execute([$sessId]);
            $newPos = (int)$maxPos->fetchColumn();

            // Cari tail saat ini
            $tailStmt = $db->prepare("SELECT id FROM playlist_nodes WHERE session_id = ? ORDER BY position DESC LIMIT 1");
            $tailStmt->execute([$sessId]);
            $tailId = $tailStmt->fetchColumn();

            // Insert node baru
            $stmt = $db->prepare("INSERT INTO playlist_nodes (session_id, title, artist, duration, position, prev_id, next_id) VALUES (?, ?, ?, ?, ?, ?, NULL)");
            $stmt->execute([$sessId, $title, $artist, $duration, $newPos, $tailId ?: null]);
            $newId = (int)$db->lastInsertId();

            // Update tail lama
            if ($tailId) {
                $db->prepare("UPDATE playlist_nodes SET next_id = ? WHERE id = ?")->execute([$newId, $tailId]);
            }

            $log = $db->prepare("INSERT INTO playlist_log (session_id, action, value) VALUES (?, 'INSERT_TAIL', ?)");
            $log->execute([$sessId, $title]);

            echo json_encode(['success' => true, 'message' => "INSERT TAIL: \"$title\" ditambahkan di akhir", 'nodes' => getNodes($db, $sessId)]);
            break;

        case 'DELETE_HEAD':
            $head = $db->prepare("SELECT * FROM playlist_nodes WHERE session_id = ? ORDER BY position ASC LIMIT 1");
            $head->execute([$sessId]);
            $headNode = $head->fetch();

            if (!$headNode) {
                echo json_encode(['success' => false, 'message' => 'Playlist kosong']);
                exit;
            }

            // Update next node — hapus prev_id
            if ($headNode['next_id']) {
                $db->prepare("UPDATE playlist_nodes SET prev_id = NULL WHERE id = ?")->execute([$headNode['next_id']]);
            }

            $db->prepare("DELETE FROM playlist_nodes WHERE id = ?")->execute([$headNode['id']]);
            $db->prepare("UPDATE playlist_nodes SET position = position - 1 WHERE session_id = ?")->execute([$sessId]);

            $log = $db->prepare("INSERT INTO playlist_log (session_id, action, value) VALUES (?, 'DELETE_HEAD', ?)");
            $log->execute([$sessId, $headNode['title']]);

            echo json_encode(['success' => true, 'message' => "DELETE HEAD: \"{$headNode['title']}\" dihapus dari awal", 'nodes' => getNodes($db, $sessId)]);
            break;

        case 'DELETE_TAIL':
            $tail = $db->prepare("SELECT * FROM playlist_nodes WHERE session_id = ? ORDER BY position DESC LIMIT 1");
            $tail->execute([$sessId]);
            $tailNode = $tail->fetch();

            if (!$tailNode) {
                echo json_encode(['success' => false, 'message' => 'Playlist kosong']);
                exit;
            }

            // Update prev node — hapus next_id
            if ($tailNode['prev_id']) {
                $db->prepare("UPDATE playlist_nodes SET next_id = NULL WHERE id = ?")->execute([$tailNode['prev_id']]);
            }

            $db->prepare("DELETE FROM playlist_nodes WHERE id = ?")->execute([$tailNode['id']]);

            $log = $db->prepare("INSERT INTO playlist_log (session_id, action, value) VALUES (?, 'DELETE_TAIL', ?)");
            $log->execute([$sessId, $tailNode['title']]);

            echo json_encode(['success' => true, 'message' => "DELETE TAIL: \"{$tailNode['title']}\" dihapus dari akhir", 'nodes' => getNodes($db, $sessId)]);
            break;

        case 'DELETE_AT':
            $nodeId = (int)($input['node_id'] ?? 0);
            $node = $db->prepare("SELECT * FROM playlist_nodes WHERE id = ? AND session_id = ?");
            $node->execute([$nodeId, $sessId]);
            $targetNode = $node->fetch();

            if (!$targetNode) {
                echo json_encode(['success' => false, 'message' => 'Node tidak ditemukan']);
                exit;
            }

            // Sambungkan prev dan next
            if ($targetNode['prev_id']) {
                $db->prepare("UPDATE playlist_nodes SET next_id = ? WHERE id = ?")->execute([$targetNode['next_id'], $targetNode['prev_id']]);
            }
            if ($targetNode['next_id']) {
                $db->prepare("UPDATE playlist_nodes SET prev_id = ? WHERE id = ?")->execute([$targetNode['prev_id'], $targetNode['next_id']]);
            }

            $db->prepare("DELETE FROM playlist_nodes WHERE id = ?")->execute([$nodeId]);
            $db->prepare("UPDATE playlist_nodes SET position = position - 1 WHERE session_id = ? AND position > ?")->execute([$sessId, $targetNode['position']]);

            $log = $db->prepare("INSERT INTO playlist_log (session_id, action, value) VALUES (?, 'DELETE_AT', ?)");
            $log->execute([$sessId, $targetNode['title']]);

            echo json_encode(['success' => true, 'message' => "DELETE: \"{$targetNode['title']}\" dihapus", 'nodes' => getNodes($db, $sessId)]);
            break;

        case 'CLEAR_LOG':
            $db->prepare("DELETE FROM playlist_log WHERE session_id = ?")->execute([$sessId]);
            echo json_encode(['success' => true, 'message' => 'Log berhasil dihapus']);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Action tidak dikenal']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'DB Error: ' . $e->getMessage()]);
}

function getNodes(PDO $db, int $sessId): array {
    $stmt = $db->prepare("SELECT * FROM playlist_nodes WHERE session_id = ? ORDER BY position ASC");
    $stmt->execute([$sessId]);
    return $stmt->fetchAll();
}