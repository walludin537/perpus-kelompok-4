<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Perpustakaan Sekolah</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">

    <div class="max-w-6xl mx-auto p-5">
        <div class="flex items-center justify-between bg-white border border-gray-100 rounded-xl px-5 py-3 mb-5">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-orange-200 flex items-center justify-center">📚</div>
                <div>
                    <p class="font-medium text-sm">Perpustakaan Sekolah</p>
                    <p class="text-xs text-gray-500">Panel admin</p>
                </div>
            </div>
            <div class="flex items-center gap-5 text-sm">
                <span class="font-medium border-b-2 border-orange-500 pb1">Dashboard</span>
                <a href="data_buku.php" class="text-gray-500 hover:text-gray800">Data buku</a>
                <a href="kelola_peminjaman.php" class="text-gray-500 hover:text-gray800">Peminjaman</a>
                <button onclick="logout()" class="text-gray-500 hover:text-red600">Keluar</button>
            </div>
        </div>

        <h1 class="text-xl font-medium mb-1">Dashboard</h1>
        <p class="text-gray-500 text-sm mb-5">Ringkasan kondisi perpustakaan hari ini.</p>

        <div id="stat-cards" class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
            <div class="bg-orange-50 rounded-xl p-4">
                <p class="text-xs text-orange-800 mb-1">Total judul buku</p>
                <p id="stat-total-buku" class="text-xl font-medium text-orange-900"></p>
            </div>
            <div class="bg-emerald-50 rounded-xl p-4">
                <p class="text-xs text-emerald-800 mb-1">Total stok eksemplar</p>
                <p id="stat-total-stok" class="text-xl font-medium text-emerald900">-</p>
            </div>
            <div class="bg-amber-50 rounded-xl p-4">
                <p class="text-xs text-amber-800 mb-1">Peminjaman aktif</p>
                <p id="stat-aktif" class="text-xl font-medium text-amber-900">-</p>
            </div>
            <div class="bg-pink-50 rounded-xl p-4">
                <p class="text-xs text-pink-800 mb-1">Terlambat kembali</p>
                <p id="stat-terlambat" class="text-xl font-medium text-pink-900"></p>
            </div>
        </div>

        <div class="bg-white border border-gray-100 rounded-xl p-4">
            <p class="font-medium text-sm mb-3">Aktivitas peminjaman terbaru</p>
            <div id="aktivitas-list" class="divide-y divide-gray-100 text-sm">
                <p class="text-gray-400 text-sm py-3">Memuat data...</p>
            </div>
        </div>
    </div>

    <script>
        const API_BASE = '../../api';

        function requireRole(requiredRole) {
            const token = localStorage.getItem('token');
            const user = JSON.parse(localStorage.getItem('user') || 'null');

            if (!token || !user) {
                window.location.href = 'login.php';
                return null;
            }

            if (user.role !== requiredRole) {
                window.location.href = user.role === 'admin' ? 'dashboard_admin.php' : 'peminjaman_siswa.php';
                return null;
            }

            return user;
        }

        async function apiFetch(path, options = {}) {
            const token = localStorage.getItem('token');
            const headers = { ...(options.headers || {}) };

            if (token) {
                headers.Authorization = `Bearer ${token}`;
            }

            if (options.body && !(options.body instanceof FormData)) {
                headers['Content-Type'] = headers['Content-Type'] || 'application/json';
            }

            const response = await fetch(`${API_BASE}${path.startsWith('/') ? path : '/' + path}`, {
                ...options,
                headers,
            });

            const text = await response.text();
            let payload = null;
            try {
                payload = text ? JSON.parse(text) : null;
            } catch (e) {
                payload = { success: false, message: 'Respons tidak valid' };
            }

            if (!response.ok || (payload && payload.success === false)) {
                throw new Error(payload?.message || 'Permintaan gagal');
            }

            return payload;
        }

        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, (m) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m]));
        }

        function formatTanggal(value) {
            if (!value) return '-';
            const date = new Date(value);
            if (Number.isNaN(date.getTime())) return value;
            return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
        }

        function normalizeLoanList(payload) {
            const raw = Array.isArray(payload)
                ? payload
                : Array.isArray(payload?.data)
                    ? payload.data
                    : Array.isArray(payload?.items)
                        ? payload.items
                        : [];

            return raw.map((loan) => ({
                id: loan?.id ?? 0,
                nama_siswa: loan?.nama_siswa || loan?.nama || loan?.user_name || 'Siswa',
                judul: loan?.judul || loan?.book_title || 'Judul tidak tersedia',
                status: String(loan?.status || 'dipinjam').toLowerCase(),
                tanggal_pinjam: loan?.tanggal_pinjam || loan?.created_at || null,
            }));
        }

        function logout() {
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            window.location.href = 'login.php';
        }

        requireRole('admin');

        async function loadDashboard() {
            try {
                const [booksRes, loansRes] = await Promise.all([
                    apiFetch('/books.php'),
                    apiFetch('/loans.php')
                ]);

                const books = Array.isArray(booksRes?.data) ? booksRes.data : [];
                const loans = normalizeLoanList(loansRes);

                document.getElementById('stat-total-buku').textContent = books.length;
                document.getElementById('stat-total-stok').textContent = books.reduce((sum, b) => sum + Number(b.stok || 0), 0);
                document.getElementById('stat-aktif').textContent = loans.filter(l => l.status === 'dipinjam').length;
                document.getElementById('stat-terlambat').textContent = loans.filter(l => l.status === 'terlambat').length;

                const list = document.getElementById('aktivitas-list');
                if (loans.length === 0) {
                    list.innerHTML = '<p class="text-gray-400 py-3">Belum ada aktivitas peminjaman.</p>';
                    return;
                }

                const badgeClass = {
                    dipinjam: 'bg-amber-50 text-amber-800',
                    dikembalikan: 'bg-blue-50 text-blue-800',
                    terlambat: 'bg-red-50 text-red-800'
                };

                const badgeLabel = {
                    dipinjam: 'Dipinjam',
                    dikembalikan: 'Dikembalikan',
                    terlambat: 'Terlambat'
                };

                list.innerHTML = loans.slice(0, 8).map(l => `
                    <div class="flex items-center justify-between py-3 gap-3">
                        <div class="min-w-0">
                            <p class="truncate"><span class="font-medium">${escapeHtml(l.nama_siswa)}</span> - ${escapeHtml(l.judul)}</p>
                            <p class="text-xs text-gray-400">Dipinjam ${formatTanggal(l.tanggal_pinjam)}</p>
                        </div>
                        <span class="text-xs px-2 py-1 rounded whitespace-nowrap ${badgeClass[l.status] || 'bg-gray-50 text-gray-700'}">${badgeLabel[l.status] || l.status}</span>
                    </div>
                `).join('');
            } catch (error) {
                document.getElementById('aktivitas-list').innerHTML = `<p class="text-red-500 py-3">${escapeHtml(error.message)}</p>`;
            }
        }

        loadDashboard();
    </script>
</body>
</html>