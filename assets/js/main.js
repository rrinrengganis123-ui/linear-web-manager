'use strict';

function showToast(message, type, duration) {
    if (!type) type = 'success';
    if (!duration) duration = 3000;
    var container = document.getElementById('toast-container');
    if (!container) return;
    var icons = { success: 'OK', danger: 'X', warning: '!' };
    var toast = document.createElement('div');
    toast.className = 'toast ' + type;
    toast.innerHTML = '<span>' + (icons[type] || '-') + '</span><span>' + message + '</span>';
    container.appendChild(toast);
    setTimeout(function() {
        toast.style.animation = 'toastOut 0.3s ease forwards';
        setTimeout(function() { toast.remove(); }, 300);
    }, duration);
}

function updateClock() {
    var el = document.getElementById('clock');
    if (!el) return;
    var now = new Date();
    var pad = function(n) { return String(n).padStart(2, '0'); };
    var d = now.toLocaleDateString('id-ID', {
        weekday: 'long', day: 'numeric',
        month: 'long', year: 'numeric'
    });
    el.innerHTML = d + '<br>' + pad(now.getHours()) + ':' + pad(now.getMinutes()) + ':' + pad(now.getSeconds());
}

if (document.getElementById('clock')) {
    updateClock();
    setInterval(updateClock, 1000);
}

function confirmDelete(message) {
    if (!message) message = 'Yakin ingin menghapus?';
    return window.confirm(message);
}

async function apiCall(url, data) {
    if (!data) data = {};
    try {
        var res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        return await res.json();
    } catch (err) {
        console.error('API Error:', err);
        showToast('Terjadi kesalahan koneksi', 'danger');
        return { success: false, message: err.message };
    }
}

function animateRemove(el, callback) {
    el.style.transition = 'all 0.3s ease';
    el.style.opacity = '0';
    el.style.transform = 'scale(0.8) translateY(10px)';
    setTimeout(function() {
        el.remove();
        if (callback) callback();
    }, 300);
}

document.querySelectorAll('.flash-message').forEach(function(el) {
    setTimeout(function() {
        el.style.animation = 'toastOut 0.3s ease forwards';
        setTimeout(function() { el.remove(); }, 300);
    }, 3000);
});