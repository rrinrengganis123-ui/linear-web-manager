<?php
require_once 'config/database.php';

try {
    $db = getDB();

    $stackCount = $db->query("SELECT COUNT(*) FROM stack_items")->fetchColumn();
    $stackSession = $db->query("SELECT name FROM stack_sessions ORDER BY id DESC LIMIT 1")->fetchColumn();

    $queueWaiting = $db->query("SELECT COUNT(*) FROM queue_items WHERE status='waiting'")->fetchColumn();
    $queueDone    = $db->query("SELECT COUNT(*) FROM queue_items WHERE status='done'")->fetchColumn();
    $queueTotal   = $db->query("SELECT COUNT(*) FROM queue_items")->fetchColumn();

    $playlistCount   = $db->query("SELECT COUNT(*) FROM playlist_nodes")->fetchColumn();
    $playlistSession = $db->query("SELECT name FROM playlist_sessions ORDER BY id DESC LIMIT 1")->fetchColumn();

    $recentLogs = $db->query("
        SELECT 'Stack' as type, action, value, acted_at as time FROM stack_log
        UNION ALL
        SELECT 'Queue' as type, action, value, acted_at FROM queue_log
        UNION ALL
        SELECT 'Playlist' as type, action, value, acted_at FROM playlist_log
        ORDER BY time DESC LIMIT 6
    ")->fetchAll();

} catch (Exception $e) {
    $stackCount = $queueWaiting = $queueTotal = $playlistCount = 0;
    $stackSession = $playlistSession = '-';
    $recentLogs = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Linear Web Manager — Dashboard</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>

:root {
    --bg:        #0d0f14;
    --bg2:       #13161e;
    --bg3:       #1a1e2a;
    --border:    #252836;
    --border2:   #2e3347;

    --text:      #e8eaf0;
    --text2:     #8b90a0;
    --text3:     #555b70;

    --stack:     #ff6b35;
    --stack-dim: #3d1f0f;
    --queue:     #00c896;
    --queue-dim: #0a2e22;
    --list:      #7c6af7;
    --list-dim:  #1e1a40;

    --mono: 'Space Mono', monospace;
    --sans: 'DM Sans', sans-serif;

    --radius: 12px;
    --radius-lg: 20px;
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

html { scroll-behavior: smooth; }

body {
    font-family: var(--sans);
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
    overflow-x: hidden;
}

.sidebar {
    position: fixed;
    top: 0; left: 0;
    width: 220px;
    height: 100vh;
    background: var(--bg2);
    border-right: 1px solid var(--border);
    display: flex;
    flex-direction: column;
    z-index: 100;
    padding: 0 0 24px;
}

.sidebar-logo {
    padding: 28px 24px 24px;
    border-bottom: 1px solid var(--border);
    margin-bottom: 8px;
}

.sidebar-logo .logo-mark {
    font-family: var(--mono);
    font-size: 11px;
    color: var(--text3);
    letter-spacing: 0.15em;
    text-transform: uppercase;
    margin-bottom: 6px;
}

.sidebar-logo h1 {
    font-size: 15px;
    font-weight: 600;
    color: var(--text);
    line-height: 1.3;
}

.sidebar-logo h1 span {
    display: block;
    color: var(--text2);
    font-weight: 400;
}

.nav-section {
    padding: 0 12px;
    margin-bottom: 4px;
}

.nav-label {
    font-family: var(--mono);
    font-size: 10px;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: var(--text3);
    padding: 12px 12px 6px;
}

.nav-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border-radius: 8px;
    color: var(--text2);
    text-decoration: none;
    font-size: 14px;
    font-weight: 400;
    transition: all 0.15s;
    position: relative;
}

.nav-item:hover {
    background: var(--bg3);
    color: var(--text);
}

.nav-item.active {
    background: var(--bg3);
    color: var(--text);
}

.nav-item .dot {
    width: 8px; height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
}

.dot-stack  { background: var(--stack); box-shadow: 0 0 6px var(--stack); }
.dot-queue  { background: var(--queue); box-shadow: 0 0 6px var(--queue); }
.dot-list   { background: var(--list);  box-shadow: 0 0 6px var(--list); }
.dot-home   { background: var(--text3); }

.nav-item .badge {
    margin-left: auto;
    font-family: var(--mono);
    font-size: 10px;
    background: var(--border2);
    color: var(--text3);
    padding: 2px 7px;
    border-radius: 20px;
}

.sidebar-footer {
    margin-top: auto;
    padding: 16px 24px 0;
    border-top: 1px solid var(--border);
}

.sidebar-footer p {
    font-size: 11px;
    color: var(--text3);
    line-height: 1.5;
}

.main {
    margin-left: 220px;
    min-height: 100vh;
    padding: 40px 40px 60px;
}

.page-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 40px;
}

.page-header-left .eyebrow {
    font-family: var(--mono);
    font-size: 11px;
    letter-spacing: 0.15em;
    text-transform: uppercase;
    color: var(--text3);
    margin-bottom: 8px;
}

.page-header-left h2 {
    font-size: 28px;
    font-weight: 600;
    color: var(--text);
    line-height: 1.2;
}

.page-header-right {
    text-align: right;
}

.datetime {
    font-family: var(--mono);
    font-size: 12px;
    color: var(--text3);
    line-height: 1.8;
}

.status-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: var(--queue-dim);
    border: 1px solid var(--queue);
    color: var(--queue);
    font-family: var(--mono);
    font-size: 11px;
    padding: 4px 12px;
    border-radius: 20px;
    margin-top: 8px;
}

.status-pill::before {
    content: '';
    width: 6px; height: 6px;
    border-radius: 50%;
    background: var(--queue);
    animation: blink 1.4s ease-in-out infinite;
}

@keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.3; }
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 32px;
}

.stat-card {
    background: var(--bg2);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 28px;
    position: relative;
    overflow: hidden;
    transition: border-color 0.2s, transform 0.2s;
    cursor: default;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 2px;
}

.stat-card.stack-card::before  { background: var(--stack); }
.stat-card.queue-card::before  { background: var(--queue); }
.stat-card.list-card::before   { background: var(--list); }

.stat-card:hover {
    border-color: var(--border2);
    transform: translateY(-2px);
}

.stat-card-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
}

.stat-icon {
    width: 40px; height: 40px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px;
}

.stack-card .stat-icon { background: var(--stack-dim); }
.queue-card .stat-icon { background: var(--queue-dim); }
.list-card  .stat-icon { background: var(--list-dim); }

.stat-type {
    font-family: var(--mono);
    font-size: 10px;
    letter-spacing: 0.12em;
    text-transform: uppercase;
}

.stack-card .stat-type { color: var(--stack); }
.queue-card .stat-type { color: var(--queue); }
.list-card  .stat-type { color: var(--list); }

.stat-number {
    font-family: var(--mono);
    font-size: 42px;
    font-weight: 700;
    line-height: 1;
    margin-bottom: 6px;
}

.stack-card .stat-number { color: var(--stack); }
.queue-card .stat-number { color: var(--queue); }
.list-card  .stat-number { color: var(--list); }

.stat-label {
    font-size: 13px;
    color: var(--text2);
    margin-bottom: 16px;
}

.stat-sub {
    font-family: var(--mono);
    font-size: 11px;
    color: var(--text3);
    border-top: 1px solid var(--border);
    padding-top: 14px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.stat-action {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-top: 14px;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    text-decoration: none;
    transition: opacity 0.15s;
    border: 1px solid;
}

.stack-card .stat-action {
    background: var(--stack-dim);
    border-color: var(--stack);
    color: var(--stack);
}

.queue-card .stat-action {
    background: var(--queue-dim);
    border-color: var(--queue);
    color: var(--queue);
}

.list-card .stat-action {
    background: var(--list-dim);
    border-color: var(--list);
    color: var(--list);
}

.stat-action:hover { opacity: 0.75; }

.bottom-grid {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 16px;
}

.feature-card {
    background: var(--bg2);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 28px;
}

.feature-card h3 {
    font-size: 14px;
    font-weight: 600;
    color: var(--text);
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.feature-card h3 .tag {
    font-family: var(--mono);
    font-size: 10px;
    font-weight: 400;
    letter-spacing: 0.1em;
    color: var(--text3);
    background: var(--border);
    padding: 2px 8px;
    border-radius: 4px;
}

.feature-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.feature-item {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    padding: 18px 20px;
    background: var(--bg3);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    text-decoration: none;
    transition: all 0.15s;
}

.feature-item:hover {
    border-color: var(--border2);
    transform: translateX(3px);
}

.feature-item:hover .fi-arrow { opacity: 1; transform: translateX(2px); }

.fi-icon {
    width: 44px; height: 44px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}

.fi-stack { background: var(--stack-dim); }
.fi-queue { background: var(--queue-dim); }
.fi-list  { background: var(--list-dim); }

.fi-body { flex: 1; min-width: 0; }

.fi-name {
    font-size: 14px;
    font-weight: 600;
    color: var(--text);
    margin-bottom: 3px;
}

.fi-desc {
    font-size: 12px;
    color: var(--text2);
    line-height: 1.5;
    margin-bottom: 8px;
}

.fi-ops {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}

.fi-op {
    font-family: var(--mono);
    font-size: 10px;
    padding: 2px 8px;
    border-radius: 4px;
    border: 1px solid;
}

.fi-stack .fi-op { color: var(--stack); border-color: var(--stack-dim); background: var(--stack-dim); }
.fi-queue .fi-op { color: var(--queue); border-color: var(--queue-dim); background: var(--queue-dim); }
.fi-list  .fi-op { color: var(--list);  border-color: var(--list-dim);  background: var(--list-dim); }

.fi-arrow {
    font-size: 18px;
    color: var(--text3);
    opacity: 0;
    transition: all 0.15s;
    align-self: center;
}

.log-list {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.log-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px 14px;
    border-radius: 8px;
    transition: background 0.15s;
}

.log-item:hover { background: var(--bg3); }

.log-dot {
    width: 7px; height: 7px;
    border-radius: 50%;
    margin-top: 5px;
    flex-shrink: 0;
}

.log-Stack   .log-dot { background: var(--stack); }
.log-Queue   .log-dot { background: var(--queue); }
.log-Playlist .log-dot { background: var(--list); }

.log-body { flex: 1; min-width: 0; }

.log-action {
    font-family: var(--mono);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.05em;
}

.log-Stack   .log-action { color: var(--stack); }
.log-Queue   .log-action { color: var(--queue); }
.log-Playlist .log-action { color: var(--list); }

.log-value {
    font-size: 12px;
    color: var(--text2);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-top: 1px;
}

.log-time {
    font-family: var(--mono);
    font-size: 10px;
    color: var(--text3);
    white-space: nowrap;
    flex-shrink: 0;
}

.log-empty {
    text-align: center;
    padding: 32px;
    color: var(--text3);
    font-size: 13px;
}

.queue-bar {
    margin-top: 10px;
    height: 4px;
    background: var(--border);
    border-radius: 2px;
    overflow: hidden;
}

.queue-bar-fill {
    height: 100%;
    background: var(--queue);
    border-radius: 2px;
    transition: width 0.6s ease;
}

@media (max-width: 1100px) {
    .bottom-grid { grid-template-columns: 1fr; }
}

@media (max-width: 900px) {
    .stats-grid { grid-template-columns: 1fr; }
    .sidebar { width: 180px; }
    .main { margin-left: 180px; padding: 24px; }
}
</style>
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-logo">
        
        <h1>Linear Web<span>Manager</span></h1>
    </div>

    <nav class="nav-section">
        <div class="nav-label">Menu</div>

        <a href="index.php" class="nav-item active">
            <span class="dot dot-home"></span>
            Dashboard
        </a>

        <a href="pages/stack/index.php" class="nav-item">
            <span class="dot dot-stack"></span>
            Stack
            <span class="badge"><?= $stackCount ?></span>
        </a>

        <a href="pages/queue/index.php" class="nav-item">
            <span class="dot dot-queue"></span>
            Queue
            <span class="badge"><?= $queueTotal ?></span>
        </a>

        <a href="pages/linked-list/index.php" class="nav-item">
            <span class="dot dot-list"></span>
            Linked List
            <span class="badge"><?= $playlistCount ?></span>
        </a>
    </nav>
</aside>

<main class="main">

    <div class="page-header">
        <div class="page-header-left">
            <div class="eyebrow">Overview</div>
            <h2>Dashboard</h2>
        </div>
        <div class="page-header-right">
            <div class="datetime" id="clock">--:--:--</div>
            <div class="status-pill">System Online</div>
        </div>
    </div>

    <div class="stats-grid">

        <div class="stat-card stack-card">
            <div class="stat-card-top">
                <div class="stat-icon">📚</div>
                <div class="stat-type">Stack</div>
            </div>
            <div class="stat-number"><?= $stackCount ?></div>
            <div class="stat-label">Item dalam stack</div>
            <div class="stat-sub">LIFO — Last In First Out</div>
            <a href="pages/stack/index.php" class="stat-action">Buka Stack →</a>
        </div>

        <div class="stat-card queue-card">
            <div class="stat-card-top">
                <div class="stat-icon">🎫</div>
                <div class="stat-type">Queue</div>
            </div>
            <div class="stat-number"><?= $queueWaiting ?></div>
            <div class="stat-label">Menunggu dalam antrean</div>
            <div class="queue-bar">
                <div class="queue-bar-fill" style="width:<?= $queueTotal > 0 ? round(($queueWaiting/$queueTotal)*100) : 0 ?>%"></div>
            </div>
            <div class="stat-sub" style="margin-top:10px"><?= $queueTotal ?> total · <?= $queueDone ?> selesai</div>
            <a href="pages/queue/index.php" class="stat-action">Buka Queue →</a>
        </div>

        <div class="stat-card list-card">
            <div class="stat-card-top">
                <div class="stat-icon">🎵</div>
                <div class="stat-type">Linked List</div>
            </div>
            <div class="stat-number"><?= $playlistCount ?></div>
            <div class="stat-label">Node dalam playlist</div>
            <div class="stat-sub">Doubly Linked List</div>
            <a href="pages/linked-list/index.php" class="stat-action">Buka Playlist →</a>
        </div>

    </div>

    <div class="bottom-grid">

        <div class="feature-card">
            <h3>Struktur Data <span class="tag">3 modul</span></h3>
            <div class="feature-list">

                <a href="pages/stack/index.php" class="feature-item">
                    <div class="fi-icon fi-stack">📚</div>
                    <div class="fi-body fi-stack">
                        <div class="fi-name">Stack — Undo / Redo</div>
                        <div class="fi-desc">Visualisasi mekanisme LIFO. Simulasi riwayat aksi seperti tombol Undo/Redo pada editor teks.</div>
                        <div class="fi-ops">
                            <span class="fi-op">PUSH</span>
                            <span class="fi-op">POP</span>
                            <span class="fi-op">PEEK</span>
                            <span class="fi-op">CLEAR</span>
                        </div>
                    </div>
                    <div class="fi-arrow">›</div>
                </a>

                <a href="pages/queue/index.php" class="feature-item">
                    <div class="fi-icon fi-queue">🎫</div>
                    <div class="fi-body fi-queue">
                        <div class="fi-name">Queue — Antrean Tiket</div>
                        <div class="fi-desc">Visualisasi mekanisme FIFO. Simulasi sistem antrean tiket digital dengan status real-time.</div>
                        <div class="fi-ops">
                            <span class="fi-op">ENQUEUE</span>
                            <span class="fi-op">DEQUEUE</span>
                            <span class="fi-op">PEEK</span>
                            <span class="fi-op">CLEAR</span>
                        </div>
                    </div>
                    <div class="fi-arrow">›</div>
                </a>

                <a href="pages/linked-list/index.php" class="feature-item">
                    <div class="fi-icon fi-list">🎵</div>
                    <div class="fi-body fi-list">
                        <div class="fi-name">Linked List — Playlist Lagu</div>
                        <div class="fi-desc">Visualisasi Doubly Linked List. Setiap node lagu memiliki pointer ke lagu sebelum dan sesudahnya.</div>
                        <div class="fi-ops">
                            <span class="fi-op">INSERT</span>
                            <span class="fi-op">DELETE</span>
                            <span class="fi-op">TRAVERSE</span>
                        </div>
                    </div>
                    <div class="fi-arrow">›</div>
                </a>

            </div>
        </div>

        <div class="feature-card">
            <h3>Log Aktivitas <span class="tag">terbaru</span></h3>
            <div class="log-list">
                <?php if (empty($recentLogs)): ?>
                    <div class="log-empty">Belum ada aktivitas</div>
                <?php else: ?>
                    <?php foreach ($recentLogs as $log): ?>
                    <div class="log-item log-<?= htmlspecialchars($log['type']) ?>">
                        <div class="log-dot"></div>
                        <div class="log-body">
                            <div class="log-action"><?= htmlspecialchars($log['type']) ?> · <?= htmlspecialchars($log['action']) ?></div>
                            <div class="log-value"><?= htmlspecialchars($log['value'] ?? '—') ?></div>
                        </div>
                        <div class="log-time"><?= date('H:i', strtotime($log['time'])) ?></div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>
</main>

<script>
function updateClock() {
  const now = new Date();
  const h = String(now.getHours()).padStart(2, '0');
  const m = String(now.getMinutes()).padStart(2, '0');
  const s = String(now.getSeconds()).padStart(2, '0');

  document.getElementById("clock").innerHTML = h + ':' + m + ':' + s;
}

updateClock();
setInterval(updateClock, 1000);
</script>

</body>
</html>