<?php
$pageTitle  = 'Linked List';
$pageActive = 'linked-list';

require_once '../../config/database.php';

$db = getDB();

require_once '../../includes/header.php';

$nodes   = $db->query("SELECT * FROM playlist_nodes WHERE session_id = 1 ORDER BY position ASC")->fetchAll();
$logs    = $db->query("SELECT * FROM playlist_log WHERE session_id = 1 ORDER BY acted_at DESC LIMIT 10")->fetchAll();
$session = $db->query("SELECT * FROM playlist_sessions WHERE id = 1")->fetch();
?>

<style>
.node-highlight {
    animation: highlightNode 1.5s ease;
}
@keyframes highlightNode {
    0%   { box-shadow: 0 0 0px transparent; }
    30%  { box-shadow: 0 0 20px var(--list); background: var(--list-dim); }
    100% { box-shadow: 0 0 0px transparent; }
}
.pointer-changed {
    animation: highlightPointer 1.5s ease;
}
@keyframes highlightPointer {
    0%   { color: var(--text3); }
    30%  { color: #ffffff; font-size: 11px; }
    100% { color: var(--text3); }
}
</style>

<div class="page-header">
    <div class="page-header-left">
        <div class="eyebrow"></div>
        <h2>Linked List</h2>
    </div>
    <div class="page-header-right">
        <span class="status-pill" style="border-color:var(--list);color:var(--list);background:var(--list-dim);">
            Doubly Linked List
        </span>
    </div>
</div>

<div style="display:grid; grid-template-columns:1fr 340px; gap:16px;">

    <!-- KOLOM KIRI -->
    <div style="display:flex; flex-direction:column; gap:16px;">

        <!-- PANEL INPUT -->
        <div class="card">
            <div class="card-title">
                🎵 Playlist
                
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr 100px; gap:10px; margin-bottom:12px;">
                <input type="text" id="inputTitle" class="input-field" placeholder="Judul lagu...">
                <input type="text" id="inputArtist" class="input-field" placeholder="Artis...">
                <input type="text" id="inputDuration" class="input-field" placeholder="0:00">
            </div>

            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <button class="btn btn-list" type="button" onclick="doInsertHead()">⬅ INSERT HEAD</button>
                <button class="btn btn-list" type="button" onclick="doInsertTail()">➡ INSERT TAIL</button>
                <button class="btn btn-danger" type="button" onclick="doDeleteHead()">✕ DEL HEAD</button>
                <button class="btn btn-danger" type="button" onclick="doDeleteTail()">✕ DEL TAIL</button>
                <button class="btn btn-danger" type="button" onclick="doClear()" style="margin-left:auto;">🗑 CLEAR</button>
            </div>
        </div>

        <!-- VISUALIZER -->
        <div class="card">
            <div class="card-title">
                🔗 Visualisasi Doubly Linked List
                <span class="tag" id="nodeCount"><?php echo count($nodes); ?> node</span>
            </div>

            <!-- Label HEAD & TAIL -->
            <div style="display:flex; justify-content:space-between;
                        font-family:var(--mono); font-size:11px;
                        color:var(--text3); margin-bottom:8px; padding:0 4px;">
                <span style="color:var(--list);">HEAD ⬇</span>
                <span>⬇ TAIL</span>
            </div>

            <!-- Container node -->
            <div id="listViz" style="
                display:flex; flex-direction:row;
                align-items:center; gap:4px;
                flex-wrap:wrap; min-height:100px;
                padding:20px; background:var(--bg3);
                border:1px solid var(--border);
                border-radius:var(--radius);
                margin-bottom:16px; overflow-x:auto;">
                <?php if (empty($nodes)): ?>
                    <div class="viz-empty">Playlist kosong — tambahkan lagu</div>
                <?php else: ?>
                    <?php foreach ($nodes as $i => $node): ?>
                    <div style="display:flex; align-items:center; gap:4px;">
                        <?php if ($i > 0): ?>
                        <div style="display:flex; flex-direction:column; align-items:center; gap:2px;">
                            <span style="font-family:var(--mono); font-size:10px; color:var(--list);">←</span>
                            <span style="font-family:var(--mono); font-size:10px; color:var(--warning);">→</span>
                        </div>
                        <?php endif; ?>
                        <div class="node-val node-list" id="lnode-<?= $node['id'] ?>" style="
                            display:flex; flex-direction:column;
                            align-items:center; gap:3px;
                            min-width:110px; padding:10px 12px;">
                            <span style="font-size:9px; color:var(--text3); font-family:var(--mono);">
                                <?= $i === 0 ? 'HEAD' : ($i === count($nodes)-1 ? 'TAIL' : '['.$i.']') ?>
                            </span>
                            <span style="font-weight:700; font-size:13px; text-align:center;">
                                <?= htmlspecialchars($node['title']) ?>
                            </span>
                            <span style="font-size:11px; color:var(--text3);">
                                <?= htmlspecialchars($node['artist']) ?>
                            </span>
                            <span style="font-family:var(--mono); font-size:10px; color:var(--text3);">
                                <?= htmlspecialchars($node['duration']) ?>
                            </span>
                            <!-- Pointer info -->
                            <div style="display:flex; gap:6px; margin-top:4px; font-family:var(--mono); font-size:9px;">
                                <span style="color:var(--warning);">
                                    prev:<?= $node['prev_id'] ?? 'NULL' ?>
                                </span>
                                <span style="color:var(--list);">
                                    next:<?= $node['next_id'] ?? 'NULL' ?>
                                </span>
                            </div>
                            <button onclick="doDeleteAt(<?= $node['id'] ?>)" type="button"
                                style="margin-top:6px; font-size:10px; padding:2px 8px;
                                       border-radius:4px; background:rgba(255,77,77,0.1);
                                       border:1px solid var(--danger); color:var(--danger); cursor:pointer;">
                                ✕ Hapus
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div>

    </div>

    <!-- KOLOM KANAN — LOG -->
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
                <tr>
                    <th>Aksi</th>
                    <th>Nilai</th>
                    <th>Waktu</th>
                </tr>
            </thead>
            <tbody id="logBody">
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td>
                        <span class="action-pill pill-<?= strtolower(str_replace('_','-',$log['action'])) ?>">
                            <?= $log['action'] ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($log['value'] ?? '—') ?></td>
                    <td style="font-family:var(--mono); font-size:11px; color:var(--text3);">
                        <?= date('H:i:s', strtotime($log['acted_at'])) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($logs)): ?>
                <tr><td colspan="3" style="text-align:center; color:var(--text3);">Belum ada log</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<script>
const API        = '/api/linked-list.php';
const SESSION_ID = 1;

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('inputTitle').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') doInsertTail();
    });
});

async function doInsertHead() {
    var title    = document.getElementById('inputTitle').value.trim();
    var artist   = document.getElementById('inputArtist').value.trim() || 'Unknown';
    var duration = document.getElementById('inputDuration').value.trim() || '0:00';
    if (!title) { showToast('Isi judul lagu terlebih dahulu', 'warning'); return; }
    var res = await apiCall(API, { action: 'INSERT_HEAD', session_id: SESSION_ID, title: title, artist: artist, duration: duration });
    if (res.success) { renderList(res.nodes); addLog('INSERT_HEAD', title); showToast(res.message, 'success'); clearInputs(); }
    else { showToast(res.message, 'danger'); }
}



async function doInsertTail() {
    var title    = document.getElementById('inputTitle').value.trim();
    var artist   = document.getElementById('inputArtist').value.trim() || 'Unknown';
    var duration = document.getElementById('inputDuration').value.trim() || '0:00';
    if (!title) { showToast('Isi judul lagu terlebih dahulu', 'warning'); return; }
    var res = await apiCall(API, { action: 'INSERT_TAIL', session_id: SESSION_ID, title: title, artist: artist, duration: duration });
    if (res.success) { renderList(res.nodes); addLog('INSERT_TAIL', title); showToast(res.message, 'success'); clearInputs(); }
    else { showToast(res.message, 'danger'); }
}


async function doDeleteHead() {
    if (!confirmDelete('Hapus node HEAD?')) return;
    var res = await apiCall(API, { action: 'DELETE_HEAD', session_id: SESSION_ID });
    if (res.success) { renderList(res.nodes); addLog('DELETE_HEAD', '—'); showToast(res.message, 'success'); }
    else { showToast(res.message, 'danger'); }
}


async function doDeleteTail() {
    if (!confirmDelete('Hapus node TAIL?')) return;
    var res = await apiCall(API, { action: 'DELETE_TAIL', session_id: SESSION_ID });
    if (res.success) { renderList(res.nodes); addLog('DELETE_TAIL', '—'); showToast(res.message, 'success'); }
    else { showToast(res.message, 'danger'); }
}


async function doDeleteAt(nodeId) {
    if (!confirmDelete('Hapus node ini?')) return;
    var res = await apiCall(API, { action: 'DELETE_AT', session_id: SESSION_ID, node_id: nodeId });
    if (res.success) { renderList(res.nodes); addLog('DELETE_AT', '—'); showToast(res.message, 'success'); }
    else { showToast(res.message, 'danger'); }
}


async function doClear() {
    if (!confirmDelete('Hapus semua node dalam playlist?')) return;
    var db_res = await apiCall('/linear-web-manager/api/linked-list.php', { action: 'CLEAR_ALL', session_id: SESSION_ID });
    renderList([]);
    showToast('Playlist dikosongkan', 'success');
}

async function clearLog() {
    if (!confirmDelete('Hapus semua log aktivitas?')) return;
    var res = await apiCall(API, { action: 'CLEAR_LOG', session_id: SESSION_ID });
    if (res.success) {
        document.getElementById('logBody').innerHTML =
            '<tr><td colspan="3" style="text-align:center;color:var(--text3);">Belum ada log</td></tr>';
        showToast('Log berhasil dihapus', 'success');
    }
}

function renderList(nodes) {
    var viz = document.getElementById('listViz');
    document.getElementById('nodeCount').textContent = nodes.length + ' node';

    if (nodes.length === 0) {
        viz.innerHTML = '<div class="viz-empty">Playlist kosong — tambahkan lagu</div>';
        return;
    }

    viz.innerHTML = '';
    nodes.forEach(function(node, i) {
        var label = i === 0 ? 'HEAD' : (i === nodes.length - 1 ? 'TAIL' : '[' + i + ']');
        var wrapper = document.createElement('div');
        wrapper.style.cssText = 'display:flex;align-items:center;gap:4px;';

        if (i > 0) {
            var arrows = document.createElement('div');
            arrows.style.cssText = 'display:flex;flex-direction:column;align-items:center;gap:2px;';
            arrows.innerHTML =
                '<span style="font-family:var(--mono);font-size:10px;color:var(--list);">←</span>' +
                '<span style="font-family:var(--mono);font-size:10px;color:var(--warning);">→</span>';
            wrapper.appendChild(arrows);
        }

        var nodeEl = document.createElement('div');
        nodeEl.className = 'node-val node-list' + (window._highlightIds && window._highlightIds.includes(node.id) ? ' node-highlight' : '');
        nodeEl.id = 'lnode-' + node.id;
        nodeEl.style.cssText = 'display:flex;flex-direction:column;align-items:center;gap:3px;min-width:110px;padding:10px 12px;';
        nodeEl.innerHTML =
            '<span style="font-size:9px;color:var(--text3);font-family:var(--mono);">' + label + '</span>' +
            '<span style="font-weight:700;font-size:13px;text-align:center;">' + node.title + '</span>' +
            '<span style="font-size:11px;color:var(--text3);">' + node.artist + '</span>' +
            '<span style="font-family:var(--mono);font-size:10px;color:var(--text3);">' + node.duration + '</span>' +
            '<div style="display:flex;gap:6px;margin-top:4px;font-family:var(--mono);font-size:9px;">' +
                '<span style="color:var(--warning);">prev:' + (node.prev_id || 'NULL') + '</span>' +
                '<span style="color:var(--list);">next:' + (node.next_id || 'NULL') + '</span>' +
            '</div>' +
            '<button onclick="doDeleteAt(' + node.id + ')" type="button" ' +
            'style="margin-top:6px;font-size:10px;padding:2px 8px;border-radius:4px;' +
            'background:rgba(255,77,77,0.1);border:1px solid var(--danger);color:var(--danger);cursor:pointer;">' +
            '✕ Hapus</button>';

        wrapper.appendChild(nodeEl);
        viz.appendChild(wrapper);
    });
}

function addLog(action, value) {
    var tbody = document.getElementById('logBody');
    var now   = new Date();
    var time  = [now.getHours(), now.getMinutes(), now.getSeconds()]
                    .map(function(n) { return String(n).padStart(2,'0'); }).join(':');
    var pills = {
        'INSERT_HEAD':'pill-insert','INSERT_TAIL':'pill-insert',
        'DELETE_HEAD':'pill-delete','DELETE_TAIL':'pill-delete','DELETE_AT':'pill-delete'
    };
    var row = document.createElement('tr');
    row.innerHTML =
        '<td><span class="action-pill ' + (pills[action] || 'pill-peek') + '">' + action + '</span></td>' +
        '<td>' + (value || '—') + '</td>' +
        '<td style="font-family:var(--mono);font-size:11px;color:var(--text3);">' + time + '</td>';
    tbody.insertBefore(row, tbody.firstChild);
    if (tbody.rows.length > 10) tbody.deleteRow(tbody.rows.length - 1);
}

function clearInputs() {
    document.getElementById('inputTitle').value    = '';
    document.getElementById('inputArtist').value   = '';
    document.getElementById('inputDuration').value = '';
}
</script>

<?php require_once '../../includes/footer.php'; ?>