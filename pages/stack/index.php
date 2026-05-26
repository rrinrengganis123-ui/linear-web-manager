
<?php
$pageTitle  = 'Stack';
$pageActive = 'stack';
require_once '../../config/database.php';
$db = getDB();
require_once '../../includes/header.php';

$items   = $db->query("SELECT * FROM stack_items WHERE session_id = 1 ORDER BY position ASC")->fetchAll();
$logs    = $db->query("SELECT * FROM stack_log WHERE session_id = 1 ORDER BY acted_at DESC LIMIT 10")->fetchAll();
?>

<div class="page-header">
    <div class="page-header-left">
        <div class="eyebrow">Struktur Data</div>
        <h2>Stack</h2>
    </div>
    <div class="page-header-right">
        <span class="status-pill">LIFO — Last In First Out</span>
    </div>
</div>

<div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">

    <!-- EDITOR SIMULASI -->
    <div class="card">
        <div class="card-title">
            ✏️ Simulasi Editor Teks
            <span class="tag">Undo/Redo Demo</span>
        </div>

        <!-- Toolbar -->
        <div style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:12px;">
            <button class="btn btn-ghost" type="button" onclick="editorAction('bold')"
                style="font-weight:700;">B Bold</button>
            <button class="btn btn-ghost" type="button" onclick="editorAction('italic')"
                style="font-style:italic;">I Italic</button>
            <button class="btn btn-ghost" type="button" onclick="editorAction('underline')"
                style="text-decoration:underline;">U Underline</button>
            <button class="btn btn-ghost" type="button" onclick="editorAction('fontSize')"
                >A+ Besar</button>
            <button class="btn btn-ghost" type="button" onclick="editorAction('fontSizeDown')"
                >A- Kecil</button>
            <button class="btn btn-ghost" type="button" onclick="editorAction('highlight')"
                style="background:rgba(255,184,48,0.2); border-color:var(--warning); color:var(--warning);">
                ★ Highlight</button>
            <button class="btn btn-danger" type="button" onclick="editorAction('clear')"
                >✕ Hapus Teks</button>
        </div>

        <!-- Area teks -->
        <div id="editorArea" contenteditable="true"
            style="min-height:160px; padding:16px;
                   background:var(--bg3); border:1px solid var(--border2);
                   border-radius:8px; color:var(--text);
                   font-size:15px; line-height:1.7;
                   outline:none; cursor:text;"
            placeholder="Ketik teks di sini lalu gunakan toolbar...">
        </div>

        <!-- Undo Redo buttons -->
        <div style="display:flex; gap:10px; margin-top:12px;">
            <button class="btn btn-stack" type="button" onclick="doUndo()" id="btnUndo">
                ↩ UNDO
            </button>
            <button class="btn btn-ghost" type="button" onclick="doRedo()" id="btnRedo">
                ↪ REDO
            </button>
            <span id="undoMsg" style="align-self:center; font-family:var(--mono);
                font-size:11px; color:var(--text3);"></span>
        </div>
    </div>

    <!-- PANEL OPERASI MANUAL -->
    <div class="card">
        <div class="card-title">
            ➕ Operasi Stack Manual
        </div>

        <div class="input-group">
            <input type="text" id="pushValue" class="input-field"
                placeholder="Ketik nilai untuk di-PUSH...">
            <button class="btn btn-stack" type="button" onclick="doPush()">⬆ PUSH</button>
        </div>

        <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <button class="btn btn-danger" type="button" onclick="doPop()">⬇ POP</button>
            <button class="btn btn-ghost"  type="button" onclick="doPeek()">👁 PEEK</button>
            <button class="btn btn-danger" type="button" onclick="doClear()"
                style="margin-left:auto;">🗑 CLEAR</button>
        </div>

        <!-- PEEK result -->
        <div id="peekResult" style="display:none; margin-top:12px;
            padding:12px 16px; background:var(--bg3);
            border:1px solid var(--stack); border-radius:8px;
            font-family:var(--mono); font-size:13px; color:var(--stack);">
        </div>
    </div>

</div>

<!-- VISUALIZER + LOG -->
<div style="display:grid; grid-template-columns:1fr 340px; gap:16px;">

    <!-- VISUALIZER -->
    <div class="card">
        <div class="card-title">
            📚 Visualisasi Stack
            <span class="tag" id="stackCount"><?php echo count($items); ?> item</span>
        </div>

        <div style="display:flex; justify-content:space-between;
                    font-family:var(--mono); font-size:11px;
                    color:var(--text3); margin-bottom:8px; padding:0 4px;">
            <span>BOTTOM</span>
            <span style="color:var(--stack);">▲ TOP</span>
        </div>

        <div class="viz-container" id="stackViz" style="
            flex-direction:column-reverse;
            align-items:stretch;
            gap:6px; min-height:200px;">
            <?php if (empty($items)): ?>
                <div class="viz-empty">Stack kosong — mulai PUSH item</div>
            <?php else: ?>
                <?php foreach ($items as $i => $item): ?>
                <div class="node-box" id="node-<?php echo $item['id']; ?>"
                    <div class="node-val node-stack <?= $i===count($items)-1 ? 'top-node':'' ?>"
                         style="width:100%; display:flex;
                                justify-content:space-between; align-items:center;">
                        <span style="font-family:var(--mono); font-size:10px; color:var(--text3);">[<?= $item['position'] ?>]</span>
                        <span><?php echo htmlspecialchars($item['value']); ?></span>
                        <span style="font-size:10px; color:var(--text3);"><?= $i===count($items)-1 ? '← TOP':'' ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- LOG -->
    <div class="card" style="align-self:start;">
        <div class="card-title">
            📋 Log Aktivitas
            <button class="btn btn-danger" type="button" onclick="clearLog()"
                style="margin-left:auto; padding:4px 12px; font-size:11px;">
                🗑 Hapus Log
            </button>
        </div>
        <table class="log-table">
            <thead>
                <tr><th>Aksi</th><th>Nilai</th><th>Waktu</th></tr>
            </thead>
            <tbody id="logBody">
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td><span class="action-pill pill-<?= strtolower($log['action']) ?>"><?= $log['action'] ?></span></td>
                    <td><?= htmlspecialchars($log['value'] ?? '—') ?></td>
                    <td style="font-family:var(--mono); font-size:11px; color:var(--text3);"><?= date('H:i:s', strtotime($log['acted_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($logs)): ?>
                <tr><td colspan="3" style="text-align:center; color:var(--text3);">Belum ada log</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<style>
[contenteditable]:empty:before {
    content: attr(placeholder);
    color: var(--text3);
    pointer-events: none;
}
@keyframes popAnim {
    0%   { transform: translateY(0) scale(1); opacity:1; }
    50%  { transform: translateY(-10px) scale(1.05); opacity:0.5; }
    100% { transform: translateY(0) scale(1); opacity:0; }
}
.node-pop { animation: popAnim 0.4s ease forwards; }
</style>

<script>
const API = '/api/stack.php';
const SESSION_ID = 1;

// ── EDITOR STATE ─────────────────────────────
var editorHistory = [];
var redoHistory   = [];

document.addEventListener('DOMContentLoaded', function() {
    var editor = document.getElementById('editorArea');

    // Simpan state awal
    editorHistory.push({ html: '', action: 'Init' });

    // Auto-push saat user ketik
    var typingTimer;
    editor.addEventListener('input', function() {
        clearTimeout(typingTimer);
        typingTimer = setTimeout(function() {
            var html = editor.innerHTML;
            var last = editorHistory[editorHistory.length - 1];
            if (last && last.html !== html) {
                var snippet = editor.innerText.slice(-20) || '(teks)';
                pushToStack('Ketik: ' + snippet);
                editorHistory.push({ html: html, action: 'Ketik' });
                redoHistory = [];
            }
        }, 800);
    });

    document.getElementById('pushValue').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') doPush();
    });
});

// ── EDITOR ACTIONS ────────────────────────────
function editorAction(type) {
    var editor = document.getElementById('editorArea');
    editor.focus();

    var actionName = '';
    switch(type) {
        case 'bold':
            document.execCommand('bold');
            actionName = 'Bold Teks';
            break;
        case 'italic':
            document.execCommand('italic');
            actionName = 'Italic Teks';
            break;
        case 'underline':
            document.execCommand('underline');
            actionName = 'Underline Teks';
            break;
        case 'fontSize':
            document.execCommand('fontSize', false, '5');
            actionName = 'Perbesar Font';
            break;
        case 'fontSizeDown':
            document.execCommand('fontSize', false, '2');
            actionName = 'Perkecil Font';
            break;
        case 'highlight':
            document.execCommand('hiliteColor', false, '#ffb830');
            actionName = 'Highlight Teks';
            break;
        case 'clear':
            editor.innerHTML = '';
            actionName = 'Hapus Semua Teks';
            break;
    }

    // Simpan state ke history dan PUSH ke stack
    editorHistory.push({ html: editor.innerHTML, action: actionName });
    redoHistory = [];
    pushToStack(actionName);
}

// ── UNDO ──────────────────────────────────────
async function doUndo() {
    if (editorHistory.length <= 1) {
        showToast('Tidak ada aksi yang bisa di-Undo', 'warning');
        return;
    }

    var current = editorHistory.pop();
    redoHistory.push(current);

    var prev = editorHistory[editorHistory.length - 1];
    document.getElementById('editorArea').innerHTML = prev.html;

    // POP dari stack DB
    var res = await apiCall(API, { action: 'POP', session_id: SESSION_ID });
    if (res.success) {
        renderStack(res.items);
        addLog('POP', res.popped);
        showToast('UNDO: ' + current.action, 'success');
        document.getElementById('undoMsg').textContent = 'Undo: ' + current.action;
        setTimeout(function() { document.getElementById('undoMsg').textContent = ''; }, 2000);
    }
}

// ── REDO ──────────────────────────────────────
async function doRedo() {
    if (redoHistory.length === 0) {
        showToast('Tidak ada aksi yang bisa di-Redo', 'warning');
        return;
    }

    var next = redoHistory.pop();
    editorHistory.push(next);
    document.getElementById('editorArea').innerHTML = next.html;

    // PUSH kembali ke stack DB
    await pushToStack('Redo: ' + next.action);
    showToast('REDO: ' + next.action, 'success');
    document.getElementById('undoMsg').textContent = 'Redo: ' + next.action;
    setTimeout(function() { document.getElementById('undoMsg').textContent = ''; }, 2000);
}

// ── PUSH ke DB ────────────────────────────────
async function pushToStack(value) {
    var res = await apiCall(API, { action: 'PUSH', session_id: SESSION_ID, value: value });
    if (res.success) {
        renderStack(res.items);
        addLog('PUSH', value);
    }
}

// ── OPERASI MANUAL ────────────────────────────
async function doPush() {
    var input = document.getElementById('pushValue');
    var value = input.value.trim();
    if (!value) { showToast('Isi nilai terlebih dahulu', 'warning'); return; }
    var res = await apiCall(API, { action: 'PUSH', session_id: SESSION_ID, value: value });
    if (res.success) {
        renderStack(res.items);
        addLog('PUSH', value);
        showToast(res.message, 'success');
        input.value = '';
        input.focus();
    } else {
        showToast(res.message, 'danger');
    }
}

async function doPop() {
    var res = await apiCall(API, { action: 'POP', session_id: SESSION_ID });
    if (res.success) {
        renderStack(res.items);
        addLog('POP', res.popped);
        showToast(res.message, 'success');
        hidePeek();
    } else {
        showToast(res.message, 'danger');
    }
}

async function doPeek() {
    var res = await apiCall(API, { action: 'PEEK', session_id: SESSION_ID });
    if (res.success) {
        addLog('PEEK', res.top);
        showToast(res.message, 'success');
        var el = document.getElementById('peekResult');
        el.style.display = 'block';
        el.textContent = 'PEEK: "' + res.top + '" ada di puncak stack';
        setTimeout(function() { hidePeek(); }, 3000);
    } else {
        showToast(res.message, 'danger');
    }
}

async function doClear() {
    if (!confirmDelete('Yakin ingin menghapus semua item dalam stack?')) return;
    var res = await apiCall(API, { action: 'CLEAR', session_id: SESSION_ID });
    if (res.success) {
        renderStack([]);
        addLog('CLEAR', '—');
        showToast(res.message, 'success');
        hidePeek();
        editorHistory = [{ html: '', action: 'Init' }];
        redoHistory   = [];
        document.getElementById('editorArea').innerHTML = '';
    }
}

async function clearLog() {
    if (!confirmDelete('Yakin ingin menghapus semua log aktivitas?')) return;
    var res = await apiCall(API, { action: 'CLEAR_LOG', session_id: SESSION_ID });
    if (res.success) {
        document.getElementById('logBody').innerHTML =
            '<tr><td colspan="3" style="text-align:center;color:var(--text3);">Belum ada log</td></tr>';
        showToast('Log berhasil dihapus', 'success');
    }
}

// ── RENDER STACK ──────────────────────────────
function renderStack(items) {
    var viz   = document.getElementById('stackViz');
    var count = document.getElementById('stackCount');
    count.textContent = items.length + ' item';
    if (items.length === 0) {
        viz.innerHTML = '<div class="viz-empty">Stack kosong — mulai PUSH item</div>';
        return;
    }
    viz.innerHTML = '';
    items.forEach(function(item, i) {
        var isTop = i === items.length - 1;
        var node  = document.createElement('div');
        node.className = 'node-box';
        node.id = 'node-' + item.id;
        node.innerHTML =
            '<div class="node-val node-stack ' + (isTop ? 'top-node' : '') + '" ' +
            'style="width:100%;display:flex;justify-content:space-between;align-items:center;">' +
                '<span style="font-family:var(--mono);font-size:10px;color:var(--text3);">[' + item.position + ']</span>' +
                '<span>' + item.value + '</span>' +
                '<span style="font-size:10px;color:var(--text3);">' + (isTop ? '← TOP' : '') + '</span>' +
            '</div>';
        viz.appendChild(node);
    });
}

function addLog(action, value) {
    var tbody = document.getElementById('logBody');
    var now   = new Date();
    var time  = [now.getHours(), now.getMinutes(), now.getSeconds()]
                    .map(function(n) { return String(n).padStart(2,'0'); }).join(':');
    var pills = { PUSH:'pill-push', POP:'pill-pop', PEEK:'pill-peek', CLEAR:'pill-clear' };
    var row   = document.createElement('tr');
    row.innerHTML =
        '<td><span class="action-pill ' + (pills[action]||'') + '">' + action + '</span></td>' +
        '<td>' + (value||'—') + '</td>' +
        '<td style="font-family:var(--mono);font-size:11px;color:var(--text3);">' + time + '</td>';
    tbody.insertBefore(row, tbody.firstChild);
    if (tbody.rows.length > 10) tbody.deleteRow(tbody.rows.length - 1);
}

function hidePeek() {
    document.getElementById('peekResult').style.display = 'none';
}
</script>

<?php require_once '../../includes/footer.php'; ?>