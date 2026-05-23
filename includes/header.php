<?php
if (!isset($pageTitle))  $pageTitle  = 'Linear Web Manager';
if (!isset($pageActive)) $pageActive = 'dashboard';

try {
    if (!isset($db)) { require_once __DIR__ . '/../config/database.php'; $db = getDB(); }
    $badgeStack = $db->query("SELECT COUNT(*) FROM stack_items")->fetchColumn();
    $badgeQueue = $db->query("SELECT COUNT(*) FROM queue_items")->fetchColumn();
    $badgeList  = $db->query("SELECT COUNT(*) FROM playlist_nodes")->fetchColumn();
} catch (Exception $e) {
    $badgeStack = $badgeQueue = $badgeList = 0;
}

$depth = substr_count($_SERVER['SCRIPT_NAME'], '/') - 2;
$base  = str_repeat('../', max($depth, 0));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle) ?> — Linear Web Manager</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= $base ?>assets/css/style.css">
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-logo">
        
        <h1>Linear Web<span>Manager</span></h1>
    </div>

    <nav class="nav-section">
        <div class="nav-label">Menu</div>
        <a href="<?= $base ?>index.php" class="nav-item <?= $pageActive==='dashboard' ? 'active' : '' ?>">
            <span class="dot dot-home"></span>
            Dashboard
        </a>

        

        <a href="<?= $base ?>pages/stack/index.php" class="nav-item <?= $pageActive==='stack' ? 'active' : '' ?>">
            <span class="dot dot-stack"></span>
            Stack
            <span class="badge"><?= $badgeStack ?></span>
        </a>

        <a href="<?= $base ?>pages/queue/index.php" class="nav-item <?= $pageActive==='queue' ? 'active' : '' ?>">
            <span class="dot dot-queue"></span>
            Queue
            <span class="badge"><?= $badgeQueue ?></span>
        </a>

        <a href="<?= $base ?>pages/linked-list/index.php" class="nav-item <?= $pageActive==='linked-list' ? 'active' : '' ?>">
            <span class="dot dot-list"></span>
            Linked List
            <span class="badge"><?= $badgeList ?></span>
        </a>
    </nav>
</aside>

<main class="main">