<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Buku - Perpustakaan Sekolah</title>
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
                <a href="dashboard_admin.php" class="text-gray-500 hover:text-gray800">Dashboard</a>
                <span class="font-medium border-b-2 border-orange-500 pb-1">Data buku</span>
                <a href="kelola_peminjaman.php" class="text-gray-500 hover:text-gray800">Peminjaman</a>
                <button onclick="logout()" class="text-gray-500 hover:text-red600">Keluar</button>
            </div>
        </div>

        <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
            <div>
                <h1 class="text-xl font-medium mb-1">Data buku</h1>
                <p class="text-gray-500 text-sm">Kelola koleksi buku perpustakaan.</p>
            </div>
            <div class="flex gap-2 flex-wrap">
                <button id="btn-add-category" onclick="openCategoryModal()" class="bg-gray-200 hover:bg-gray-300 text-gray-700 text-sm px-4 py-2 rounded-lg">+ Tambah kategori</button>
                <button id="btn-add-book" onclick="openModal()" class="bg-orange-500 hover:bg-orange-600 text-white text-sm px-4 py-2 rounded-lg">+ Tambah buku</button>
            </div>
        </div>

        <div class="flex gap-3 mb-4 flex-wrap">
            <input id="search" type="text" placeholder="Cari judul atau ISBN..." class="flex-1 min-w-[220px] border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <select id="filter-kategori" class="border border-gray-300 rounded-lg px-3 py-2 text-sm min-w-[160px]">
                <option value="">Semua kategori</option>
            </select>
        </div>

        <div class="bg-white border border-gray-100 rounded-xl overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 text-left">
                        <th class="px-4 py-3 font-medium">Judul buku</th>
                        <th class="px-4 py-3 font-medium">ISBN</th>
                        <th class="px-4 py-3 font-medium">Kategori</th>
                        <th class="px-4 py-3 font-medium text-center">Stok</th>
                        <th class="px-4 py-3 font-medium text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody id="table-buku" class="divide-y divide-gray-100">
                    <tr><td colspan="5" class="px-4 py-6 text-center text-gray400">Memuat data...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div id="modal" class="hidden fixed inset-0 bg-black/45 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl p-6 w-full max-w-sm">
            <div class="flex items-center justify-between mb-4">
                <p id="modal-title" class="font-medium">Tambah buku baru</p>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray700">&times;</button>
            </div>

            <form id="form-buku" class="space-y-3">
                <input type="hidden" id="book-id">

                <div>
                    <label class="text-xs text-gray-500 block mb-1">Judul buku</label>
                    <input id="judul" type="text" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>

                <div>
                    <label class="text-xs text-gray-500 block mb-1">Kode ISBN</label>
                    <input id="isbn" type="text" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>

                <div class="flex gap-3">
                    <div class="flex-1">
                            <label class="text-xs text-gray-500 block mb-1">Kategori</label>
                            <select id="category_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></select>
                        </div>
                        <div class="flex-1">
                            <label class="text-xs text-gray-500 block mb-1">Stok</label>
                            <input id="stok" type="number" min="0" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        </div>
                    </div>

                    <p id="form-error" class="text-sm text-red-600 hidden"></p>

                    <div class="flex gap-2 pt-2">
                        <button type="button" onclick="closeModal()" class="flex-1 border border-gray-300 rounded-lg py-2 text-sm">Batal</button>
                        <button type="submit" class="flex-1 bg-orange-500 hover:bg-orange-600 text-white rounded-lg py-2 text-sm">Simpan</button>
                    </div>
                </form>
            </div>
        </div>

        <div id="modal-category" class="hidden fixed inset-0 bg-black/45 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl p-6 w-full max-w-sm">
                <div class="flex items-center justify-between mb-4">
                    <p class="font-medium">Tambah kategori baru</p>
                    <button onclick="closeCategoryModal()" class="text-gray-400 hover:text-gray700">&times;</button>
                </div>

                <form id="form-kategori" class="space-y-3">
                    <div>
                        <label class="text-xs text-gray-500 block mb-1">Nama kategori</label>
                        <input id="nama-kategori" type="text" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    </div>

                    <p id="category-error" class="text-sm text-red-600 hidden"></p>

                    <div class="flex gap-2 pt-2">
                        <button type="button" onclick="closeCategoryModal()" class="flex-1 border border-gray-300 rounded-lg py-2 text-sm">Batal</button>
                        <button type="submit" class="flex-1 bg-orange-500 hover:bg-orange-600 text-white rounded-lg py-2 text-sm">Simpan kategori</button>
                    </div>
                </form>
            </div>
        </div>
</body>
</html>