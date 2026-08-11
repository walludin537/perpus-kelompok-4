<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pinjam Buku - Perpustakaan Sekolah</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">

    <div class="max-w-6xl mx-auto p-5">
        <div class="flex items-center justify-between bg-white border border-gray-100 rounded-xl px-5 py-3 mb-5">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-orange-200 flex items-center justify-center">📚</div>
                <div>
                    <p class="font-medium text-sm">Perpustakaan Sekolah</p>
                    <p class="text-xs text-gray-500">Sistem peminjaman buku</p>
                </div>
            </div>
            <div class="flex items-center gap-5 text-sm">
                <span class="font-medium border-b-2 border-orange-500 pb1">Peminjaman</span>
                <a href="riwayat_siswa.php" class="text-gray-500 hover:text-gray800">Riwayat</a>
                <button onclick="logout()" class="text-gray-500 hover:text-red600">Keluar</button>
            </div>
        </div>

        <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
            <div>
                <h1 class="text-xl font-medium mb-1">Pinjam buku</h1>
                <p class="text-gray-500 text-sm">Pilih buku yang tersedia lalu ajukan peminjaman.</p>
            </div>
            <div class="bg-white rounded-lg px-4 py-2 text-sm border border-gray100">
                Sedang dipinjam: <span id="jumlah-pinjam" class="font-medium"></span>/<span id="batas-pinjam">3</span>
            </div>
        </div>

        <div class="flex gap-3 mb-5 flex-wrap">
            <input id="search" type="text" placeholder="Cari judul, penulis, atau ISBN..." class="flex-1 min-w-[220px] border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <select id="filter-kategori" class="border border-gray-300 rounded-lg px3 py-2 text-sm min-w-[160px]">
                <option value="">Semua kategori</option>
            </select>
        </div>

        <p id="pesan" class="text-sm mb-4 hidden"></p>

        <div id="grid-buku" class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <p class="text-gray-400 text-sm col-span-4 text-center py-6">Memuat buku...</p>
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

        function logout() {
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            window.location.href = 'login.php';
        }

        const user = requireRole('siswa');

        let categories = [];

        async function loadCategories() {
            const res = await apiFetch('/categories.php');
            if (!res) return;
            categories = res.data || [];

            const filterEl = document.getElementById('filter-kategori');
            filterEl.innerHTML = '<option value="">Semua kategori</option>' + categories.map(c => `<option value="${c.id}">${escapeHtml(c.nama_kategori)}</option>`).join('');
        }

        async function loadJumlahPinjam() {
            const res = await apiFetch('/loans.php?mine=1&status=dipinjam');
            if (!res) return;
            document.getElementById('jumlah-pinjam').textContent = (res.data || []).length;
        }

        async function loadBooks() {
            const search = document.getElementById('search').value;
            const categoryId = document.getElementById('filter-kategori').value;

            const params = new URLSearchParams();
            if (search) params.append('search', search);
            if (categoryId) params.append('category_id', categoryId);

            const res = await apiFetch('/books.php?' + params.toString());
            if (!res) return;

            const grid = document.getElementById('grid-buku');
            const books = res.data || [];

            if (books.length === 0) {
                grid.innerHTML = '<p class="text-gray-400 text-sm col-span-4 text-center py-6">Buku tidak ditemukan.</p>';
                return;
            }

            grid.innerHTML = books.map(b => `
                <div class="bg-white border border-gray-100 rounded-xl overflow-hidden">
                    <div class="h-24 bg-orange-200 flex items-center justify-center text2xl">📖</div>
                    <div class="p-3">
                        <span class="text-xs px-2 py-0.5 rounded bg-orange-50 text-orange-800">${escapeHtml(b.nama_kategori)}</span>
                        <p class="font-medium text-sm mt-1.5">${escapeHtml(b.judul)}</p>
                        <p class="text-xs text-gray-500 mb-2">ISBN ${escapeHtml(b.isbn)}</p>
                        <div class="flex items-center justify-between">
                            <span class="text-xs ${b.stok == 0 ? 'text-red-600' : 'textemerald-700'}">Stok: ${b.stok}</span>
                            <button
                                onclick="pinjamBuku(${b.id})"
                                ${b.stok == 0 ? 'disabled' : ''}
                                class="text-xs px-3 py-1 rounded-lg border border-gray-300 ${b.stok == 0 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-50'}"
                            >${b.stok == 0 ? 'Habis' : 'Pinjam'}</button>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        async function pinjamBuku(bookId) {
            const pesan = document.getElementById('pesan');
            pesan.classList.add('hidden');

            const res = await apiFetch('/loans.php', {
                method: 'POST',
                body: JSON.stringify({ book_id: bookId })
            });

            pesan.textContent = res.message;
            pesan.className = 'text-sm mb-4 ' + (res.success ? 'text-emerald-700' : 'text-red-600');
            pesan.classList.remove('hidden');

            if (res.success) {
                loadBooks();
                loadJumlahPinjam();
            }
        }

        document.getElementById('search').addEventListener('input', debounce(loadBooks, 400));
        document.getElementById('filter-kategori').addEventListener('change', loadBooks);

        function debounce(fn, delay) {
            let timer;
            return (...args) => {
                clearTimeout(timer);
                timer = setTimeout(() => fn(...args), delay);
            };
        }

        loadCategories().then(loadBooks);
        loadJumlahPinjam();
    </script>
</body>
</html>