<?php
$pageTitle  = 'Queue';
$pageActive = 'queue';
require_once '../../config/database.php';
$db = getDB();
require_once '../../includes/header.php';

$items   = $db->query("SELECT * FROM queue_items WHERE session_id = 1 ORDER BY id ASC")->fetchAll();
$logs    = $db->query("SELECT * FROM queue_log WHERE session_id = 1 ORDER BY acted_at DESC LIMIT 10")->fetchAll();
$session = $db->query("SELECT * FROM queue_sessions WHERE id = 1")->fetch();

$waiting = array_filter($items, function($i) {
    return $i['status'] === 'waiting';
});

$processing = array_filter($items, function($i) {
    return $i['status'] === 'processing';
});

$done = array_filter($items, function($i) {
    return $i['status'] === 'done';
});
?>

<div class="page-header">
    <div class="page-header-left">
        <div class="eyebrow"></div>
        <h2>Queue</h2>
    </div>
    <div class="page-header-right">
        <span class="status-pill" style="border-color:var(--queue);color:var(--queue);background:var(--queue-dim);">
            FIFO — First In First Out
        </span>
    </div>
</div>

<div style="display:grid; grid-template-columns:1fr 340px; gap:16px;">

    <!-- KOLOM KIRI -->
    <div style="display:flex; flex-direction:column; gap:16px;">

        <!-- PANEL INPUT -->
        <div class="card">
            <div class="card-title">
                ➕ Loket Antrian
                
                <span class="tag" style="color:var(--queue);">
                    maks: <?= $session['max_size'] ?? 10 ?> item
                </span>
            </div>

            <div class="input-group">
                <input type="text" id="enqueueValue" class="input-field"
                    placeholder="Ketik nilai untuk di-ENQUEUE...">
                <button class="btn btn-queue" type="button" onclick="doEnqueue()">➡ ENQUEUE</button>
            </div>

            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <button class="btn btn-danger" type="button" onclick="doDequeue()">⬅ DEQUEUE</button>
                <button class="btn btn-ghost"  type="button" onclick="doPeek()">👁 PEEK</button>
                <button class="btn btn-danger" type="button" onclick="doClear()"
                    style="margin-left:auto;">🗑 CLEAR</button>
            </div>
        </div>

        <!-- VISUALIZER QUEUE -->
        <div class="card">
            <div class="card-title">
                🎫 Visualisasi Queue
                <span class="tag" id="queueCount"><?php echo count($waiting); ?> menunggu</span>
            </div>

            <!-- Label FRONT & REAR -->
            <div style="display:flex; justify-content:space-between;
                        font-family:var(--mono); font-size:11px;
                        color:var(--text3); margin-bottom:8px; padding:0 4px;">
                <span style="color:var(--queue);">⬅ FRONT</span>
                <span>REAR ➡</span>
            </div>

            <!-- Container node -->
            <div id="queueViz" style="
                display:flex; flex-direction:row;
                align-items:center; gap:6px;
                flex-wrap:wrap; min-height:80px;
                padding:20px; background:var(--bg3);
                border:1px solid var(--border);
                border-radius:var(--radius);
                margin-bottom:16px;">
                <?php if (empty($waiting)): ?>
                    <div class="viz-empty">Antrean kosong — mulai ENQUEUE item</div>
                <?php else: ?>
                    <?php $waitArr = array_values($waiting); ?>
                    <?php foreach ($waitArr as $i => $item): ?>
                    <div class="node-box" id="qnode-<?= $item['id'] ?>" style="flex-direction:row; gap:4px;">
                        <div class="node-val node-queue" style="
                            display:flex; flex-direction:column;
                            align-items:center; gap:2px; min-width:90px;">
                            <span style="font-size:10px; color:var(--text3); font-family:var(--mono);">
                                <?= $i === 0 ? 'FRONT' : ($i === count($waitArr)-1 ? 'REAR' : "[$i]") ?>
                            </span>
                            <span><?= htmlspecialchars($item['value']) ?></span>
                            <?php if ($item['priority'] == 1): ?>
                            <span style="font-size:9px; color:var(--warning);">★ PRIORITAS</span>
                            <?php endif; ?>
                        </div>
                        <?php if ($i < count($waitArr)-1): ?>
                        <span style="color:var(--text3); font-size:18px;">→</span>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- PEEK result -->
            <div id="peekResult" style="display:none;
                padding:12px 16px; background:var(--bg3);
                border:1px solid var(--queue); border-radius:8px;
                font-family:var(--mono); font-size:13px;
                color:var(--queue); margin-top:8px;">
            </div>

            <!-- Statistik -->
            <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:10px; margin-top:12px;">
                <div style="background:var(--bg3); border:1px solid var(--border);
                            border-radius:8px; padding:12px; text-align:center;">
                    <div style="font-family:var(--mono); font-size:20px;
                                color:var(--warning);" id="statWaiting"><?php echo count($waiting); ?></div>
                    <div style="font-size:11px; color:var(--text3); margin-top:4px;">Menunggu</div>
                </div>
                <div style="background:var(--bg3); border:1px solid var(--border);
                            border-radius:8px; padding:12px; text-align:center;">
                    <div style="font-family:var(--mono); font-size:20px;
                                color:var(--queue);" id="statProcessing"><?= count($processing) ?></div>
                    <div style="font-size:11px; color:var(--text3); margin-top:4px;">Diproses</div>
                </div>
                <div style="background:var(--bg3); border:1px solid var(--border);
                            border-radius:8px; padding:12px; text-align:center;">
                    <div style="font-family:var(--mono); font-size:20px;
                                color:var(--text3);" id="statDone"><?= count($done) ?></div>
                    <div style="font-size:11px; color:var(--text3); margin-top:4px;">Selesai</div>
                </div>
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
                        <span class="action-pill pill-<?= strtolower($log['action']) ?>">
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
const API        = '/linear-web-manager/api/queue.php';
const SESSION_ID = 1;

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('enqueueValue').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') doEnqueue();
    });
});

async function doEnqueue() {
    const input = document.getElementById('enqueueValue');
    const value = input.value.trim();
    if (!value) { showToast('Isi nilai terlebih dahulu', 'warning'); return; }

    const res = await apiCall(API, { action: 'ENQUEUE', session_id: SESSION_ID, value: value });
    if (res.success) {
        renderQueue(res.items);
        addLog('ENQUEUE', value);
        showToast(res.message, 'success');
        input.value = '';
        input.focus();
    } else {
        showToast(res.message, 'danger');
    }
}

async function doDequeue() {
    const res = await apiCall(API, { action: 'DEQUEUE', session_id: SESSION_ID });
    if (res.success) {
        renderQueue(res.items);
        addLog('DEQUEUE', res.dequeued);
        showToast(res.message, 'success');
        hidePeek();
    } else {
        showToast(res.message, 'danger');
    }
}

async function doPeek() {
    const res = await apiCall(API, { action: 'PEEK', session_id: SESSION_ID });
    if (res.success) {
        addLog('PEEK', res.front);
        showToast(res.message, 'success');
        const el = document.getElementById('peekResult');
        el.style.display = 'block';
        el.textContent   = 'PEEK: "' + res.front + '" ada di depan antrean';
        setTimeout(function() { hidePeek(); }, 3000);
    } else {
        showToast(res.message, 'danger');
    }
}

async function doClear() {
    if (!confirmDelete('Yakin ingin menghapus semua item dalam antrean?')) return;
    const res = await apiCall(API, { action: 'CLEAR', session_id: SESSION_ID });
    if (res.success) {
        renderQueue([]);
        addLog('CLEAR', '—');
        showToast(res.message, 'success');
        hidePeek();
    } else {
        showToast(res.message, 'danger');
    }
}

async function clearLog() {
    if (!confirmDelete('Yakin ingin menghapus semua log aktivitas?')) return;
    const res = await apiCall(API, { action: 'CLEAR_LOG', session_id: SESSION_ID });
    if (res.success) {
        document.getElementById('logBody').innerHTML =
            '<tr><td colspan="3" style="text-align:center;color:var(--text3);">Belum ada log</td></tr>';
        showToast('Log aktivitas berhasil dihapus', 'success');
    }
}

function renderQueue(items) {
    const viz = document.getElementById('queueViz');
    const waiting = items.filter(function(i) { return i.status === 'waiting'; });

    document.getElementById('queueCount').textContent = waiting.length + ' menunggu';
    document.getElementById('statWaiting').textContent    = waiting.length;
    document.getElementById('statProcessing').textContent = items.filter(function(i) { return i.status === 'processing'; }).length;
    document.getElementById('statDone').textContent       = items.filter(function(i) { return i.status === 'done'; }).length;

    if (waiting.length === 0) {
        viz.innerHTML = '<div class="viz-empty">Antrean kosong — mulai ENQUEUE item</div>';
        return;
    }

    viz.innerHTML = '';
    waiting.forEach(function(item, i) {
        var label = i === 0 ? 'FRONT' : (i === waiting.length - 1 ? 'REAR' : '[' + i + ']');
        var node  = document.createElement('div');
        node.className = 'node-box';
        node.id        = 'qnode-' + item.id;
        node.style.cssText = 'flex-direction:row; gap:4px;';
        node.innerHTML =
    '<div class="node-val node-queue" style="display:flex;flex-direction:column;align-items:center;gap:4px;min-width:90px;">' +
        '<span style="font-size:10px;color:var(--text3);font-family:var(--mono);">' + label + '</span>' +
        '<span>' + item.value + '</span>' +
        (item.priority == 1 ? '<span style="font-size:9px;color:var(--warning);">★ PRIORITAS</span>' : '') +
        '<button onclick="doProcess(' + item.id + ')" type="button" ' +
        'style="margin-top:4px;font-size:10px;padding:2px 8px;border-radius:4px;' +
        'background:var(--queue-dim);border:1px solid var(--queue);color:var(--queue);cursor:pointer;">' +
        'Proses</button>' +
    '</div>' +
    (i < waiting.length - 1 ? '<span style="color:var(--text3);font-size:18px;">→</span>' : '');
        viz.appendChild(node);
    });
}

function addLog(action, value) {
    var tbody = document.getElementById('logBody');
    var now   = new Date();
    var time  = [now.getHours(), now.getMinutes(), now.getSeconds()]
                    .map(function(n) { return String(n).padStart(2,'0'); }).join(':');
    var pills = { ENQUEUE:'pill-enqueue', DEQUEUE:'pill-dequeue', PEEK:'pill-peek', CLEAR:'pill-clear' };
    var row   = document.createElement('tr');
    row.innerHTML =
        '<td><span class="action-pill ' + (pills[action] || '') + '">' + action + '</span></td>' +
        '<td>' + (value || '—') + '</td>' +
        '<td style="font-family:var(--mono);font-size:11px;color:var(--text3);">' + time + '</td>';
    tbody.insertBefore(row, tbody.firstChild);
    if (tbody.rows.length > 10) tbody.deleteRow(tbody.rows.length - 1);
}

async function doProcess(itemId) {
    const res = await apiCall(API, { action: 'PROCESS', session_id: SESSION_ID, item_id: itemId });
    if (res.success) {
        renderQueue(res.items);
        showToast('Item sedang diproses', 'success');
    } else {
        showToast(res.message, 'danger');
    }
}

function hidePeek() {
    document.getElementById('peekResult').style.display = 'none';
}
</script>

<?php require_once '../../includes/footer.php'; ?>