// Helper autentikasi & fetch API, dipakai bersama oleh semua halaman view.
// Path di file ini relative terhadap URL public/index.php (front controller),
// BUKAN terhadap lokasi fisik file ini di disk (public/assets/auth-guard.js).

// public/ ada 1 level di bawah root proyek, api/ ada di root -> cukup 1x '../'
const API_BASE = '../api';

function getToken() {
    return localStorage.getItem('token');
}

function getUser() {
    return JSON.parse(localStorage.getItem('user') || 'null');
}

function logout() { 
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    window.location.href = '?page=login';
}

// Pastikan user sudah login & role sesuai, kalau tidak redirect ke login.
function requireRole(requiredRole) {
    const token = getToken();
    const user = getUser();

    if (!token || !user) {
        window.location.href = '?page=login';
        return null;
    }

    if (requiredRole && user.role !== requiredRole) {
        window.location.href = user.role === 'admin' ? '?page=dashboard_admin' : '?page=peminjaman_siswa';
        return null;
    }

    return user;
}
// Wrapper fetch yang otomatis menyertakan header Authorization: Bearer <token>
async function apiFetch(path, options = {}) {
    const token = getToken();
    const headers = Object.assign(
        { 'Content-Type': 'application/json' },
        options.headers || {},
        token ? { Authorization: 'Bearer ' + token } : {}
    );

    const res = await fetch(API_BASE + path, Object.assign({}, options, { headers }));

    if (res.status === 401) {
        logout();
        return null;
    }

    return res.json();
}

function formatTanggal(dateStr) {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
}

// Mencegah data dari database (judul, ISBN, nama, dst) merusak HTML saat mengandung
// karakter seperti < > " ' &
function escapeHtml(value) {
    if (value === null || value === undefined) return '';
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
