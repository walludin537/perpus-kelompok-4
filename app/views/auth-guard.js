const API_BASE = '../../api';

function getToken() {
    return localStorage.getItem('token');
}

function logout() {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    window.location.href = 'login.php';
}

function requireRole(role) {
    const token = getToken();
    const user = JSON.parse(localStorage.getItem('user') || 'null');

    if (!token || !user || user.role !== role) {
        window.location.href = 'login.php';
        return;
    }
}

function escapeHtml(value) {
    if (value === null || value === undefined) return '';
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function formatTanggal(value) {
    if (!value) return '';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value;
    return date.toLocaleDateString('id-ID', {
        day: '2-digit', month: 'short', year: 'numeric'
    });
}

async function apiFetch(path, options = {}) {
    if (!path.startsWith('/')) {
        path = '/' + path;
    }

    const url = API_BASE + path;
    const token = getToken();

    const headers = {
        'Content-Type': 'application/json',
        ...(options.headers || {})
    };

    if (token) {
        headers['Authorization'] = 'Bearer ' + token;
    }

    try {
        const res = await fetch(url, {
            ...options,
            headers,
        });

        const json = await res.json();
        return json;
    } catch (err) {
        return null;
    }
}
