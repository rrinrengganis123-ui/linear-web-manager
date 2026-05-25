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

$base = 'https://linearweb.infinityfreeapp.com/';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle) ?> — Linear Web Manager</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
:root{
    --bg:#0d0f14;
    --bg2:#13161e;
    --bg3:#1a1e2a;
    --border:#252836;
    --border2:#2e3347;

    --text:#e8eaf0;
    --text2:#8b90a0;
    --text3:#555b70;

    --stack:#ff6b35;
    --stack-dim:#3d1f0f;

    --queue:#00c896;
    --queue-dim:#0a2e22;

    --list:#7c6af7;
    --list-dim:#1e1a40;

    --success:#00c896;
    --danger:#ff4d4d;
    --warning:#ffb830;

    --mono:'Space Mono', monospace;
    --sans:'DM Sans', sans-serif;

    --radius:12px;
    --radius-lg:20px;

    --shadow:0 4px 24px rgba(0,0,0,.3);
}

*,
*::before,
*::after{
    box-sizing:border-box;
    margin:0;
    padding:0;
}

html{
    scroll-behavior:smooth;
}

body{
    font-family:var(--sans);
    background:var(--bg);
    color:var(--text);
    min-height:100vh;
    overflow-x:hidden;
}

a{
    text-decoration:none;
    color:inherit;
}

button{
    cursor:pointer;
    font-family:var(--sans);
}

.sidebar{
    position:fixed;
    top:0;
    left:0;
    width:220px;
    height:100vh;
    background:var(--bg2);
    border-right:1px solid var(--border);
    display:flex;
    flex-direction:column;
    z-index:100;
    padding-bottom:24px;
}

.sidebar-logo{
    padding:28px 24px 24px;
    border-bottom:1px solid var(--border);
    margin-bottom:8px;
}

.sidebar-logo h1{
    font-size:15px;
    font-weight:600;
    color:var(--text);
    line-height:1.3;
}

.sidebar-logo h1 span{
    display:block;
    color:var(--text2);
    font-weight:400;
}

.nav-section{
    padding:0 12px;
}

.nav-label{
    font-family:var(--mono);
    font-size:10px;
    letter-spacing:.12em;
    text-transform:uppercase;
    color:var(--text3);
    padding:12px 12px 6px;
}

.nav-item{
    display:flex;
    align-items:center;
    gap:10px;
    padding:10px 12px;
    border-radius:8px;
    color:var(--text2);
    font-size:14px;
    transition:all .15s;
}

.nav-item:hover,
.nav-item.active{
    background:var(--bg3);
    color:var(--text);
}

.dot{
    width:8px;
    height:8px;
    border-radius:50%;
    flex-shrink:0;
}

.dot-home{
    background:var(--text3);
}

.dot-stack{
    background:var(--stack);
    box-shadow:0 0 6px var(--stack);
}

.dot-queue{
    background:var(--queue);
    box-shadow:0 0 6px var(--queue);
}

.dot-list{
    background:var(--list);
    box-shadow:0 0 6px var(--list);
}

.nav-item .badge{
    margin-left:auto;
    font-family:var(--mono);
    font-size:10px;
    background:var(--border2);
    color:var(--text3);
    padding:2px 7px;
    border-radius:20px;
}

.main{
    margin-left:220px;
    min-height:100vh;
    padding:40px 40px 60px;
}

.page-header{
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    margin-bottom:36px;
}

.page-header-left .eyebrow{
    font-family:var(--mono);
    font-size:11px;
    letter-spacing:.15em;
    text-transform:uppercase;
    color:var(--text3);
    margin-bottom:8px;
}

.page-header-left h2{
    font-size:28px;
    font-weight:600;
}

.card{
    background:var(--bg2);
    border:1px solid var(--border);
    border-radius:var(--radius-lg);
    padding:28px;
}

.card-title{
    font-size:14px;
    font-weight:600;
    margin-bottom:20px;
    display:flex;
    align-items:center;
    gap:8px;
}

.card-title .tag{
    font-family:var(--mono);
    font-size:10px;
    color:var(--text3);
    background:var(--border);
    padding:2px 8px;
    border-radius:4px;
}

.btn{
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:10px 20px;
    border-radius:8px;
    font-size:13px;
    border:1px solid transparent;
    transition:.15s;
}

.btn:hover{
    opacity:.85;
}

.btn-stack{
    background:var(--stack-dim);
    border-color:var(--stack);
    color:var(--stack);
}

.btn-queue{
    background:var(--queue-dim);
    border-color:var(--queue);
    color:var(--queue);
}

.btn-list{
    background:var(--list-dim);
    border-color:var(--list);
    color:var(--list);
}

.btn-danger{
    background:rgba(255,77,77,.1);
    border-color:var(--danger);
    color:var(--danger);
}

.btn-ghost{
    background:var(--bg3);
    border-color:var(--border2);
    color:var(--text2);
}

.input-group{
    display:flex;
    gap:10px;
    margin-bottom:16px;
}

.input-field{
    flex:1;
    background:var(--bg3);
    border:1px solid var(--border2);
    border-radius:8px;
    padding:10px 16px;
    color:var(--text);
    font-size:14px;
    outline:none;
}

.input-field::placeholder{
    color:var(--text3);
}

.viz-empty{
    color:var(--text3);
    font-family:var(--mono);
    font-size:12px;
    margin:auto;
}

.node-box{
    display:flex;
    flex-direction:column;
    align-items:center;
    gap:4px;
}

.node-val{
    padding:10px 16px;
    border-radius:8px;
    font-family:var(--mono);
    font-size:13px;
    font-weight:700;
    border:1px solid;
    min-width:60px;
    text-align:center;
}

.node-stack{
    background:var(--stack-dim);
    border-color:var(--stack);
    color:var(--stack);
}

.node-queue{
    background:var(--queue-dim);
    border-color:var(--queue);
    color:var(--queue);
}

.node-list{
    background:var(--list-dim);
    border-color:var(--list);
    color:var(--list);
}

.log-table{
    width:100%;
    border-collapse:collapse;
    font-size:13px;
}

.log-table th{
    text-align:left;
    padding:8px 12px;
    font-family:var(--mono);
    font-size:10px;
    text-transform:uppercase;
    color:var(--text3);
    border-bottom:1px solid var(--border);
}

.log-table td{
    padding:10px 12px;
    border-bottom:1px solid var(--border);
    color:var(--text2);
}

.action-pill{
    font-family:var(--mono);
    font-size:10px;
    padding:3px 10px;
    border-radius:20px;
    font-weight:700;
}

.pill-push,
.pill-insert{
    background:var(--stack-dim);
    color:var(--stack);
    border:1px solid var(--stack);
}

.pill-pop,
.pill-delete{
    background:rgba(255,77,77,.1);
    color:var(--danger);
    border:1px solid var(--danger);
}

.pill-enqueue{
    background:var(--queue-dim);
    color:var(--queue);
    border:1px solid var(--queue);
}

.pill-dequeue{
    background:rgba(255,184,48,.1);
    color:var(--warning);
    border:1px solid var(--warning);
}

.pill-peek{
    background:var(--bg3);
    color:var(--text3);
    border:1px solid var(--border2);
}

.pill-clear{
    background:rgba(255,77,77,.1);
    color:var(--danger);
    border:1px solid var(--danger);
}

.status-pill{
    display:inline-flex;
    align-items:center;
    gap:6px;
    font-family:var(--mono);
    font-size:11px;
    padding:4px 12px;
    border-radius:20px;
}

#toast-container{
    position:fixed;
    bottom:28px;
    right:28px;
    display:flex;
    flex-direction:column;
    gap:8px;
    z-index:9999;
}

.toast{
    padding:12px 18px;
    background:var(--bg2);
    border:1px solid var(--border2);
    border-radius:var(--radius);
    font-size:13px;
    min-width:220px;
}

.toast.success{
    border-left:3px solid var(--success);
}

.toast.danger{
    border-left:3px solid var(--danger);
}

.toast.warning{
    border-left:3px solid var(--warning);
}

.node-highlight{
    animation:highlightNode 1.5s ease;
}

.pointer-changed{
    animation:highlightPointer 1.5s ease;
}

@keyframes highlightNode{
    0%{
        box-shadow:0 0 0px transparent;
    }
    30%{
        box-shadow:0 0 20px var(--list);
        background:var(--list-dim);
    }
    100%{
        box-shadow:0 0 0px transparent;
    }
}

@keyframes highlightPointer{
    0%{
        color:var(--text3);
    }
    30%{
        color:#ffffff;
        font-size:11px;
    }
    100%{
        color:var(--text3);
    }
}

@media(max-width:900px){
    .sidebar{
        width:180px;
    }

    .main{
        margin-left:180px;
        padding:24px;
    }
}

@media(max-width:680px){
    .sidebar{
        display:none;
    }

    .main{
        margin-left:0;
        padding:16px;
    }
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