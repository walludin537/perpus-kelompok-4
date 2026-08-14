# Sistem Peminjaman Buku Perpustakaan Sekolah

Proyek kelompok - OOP + PDO + MVC + API CRUD, Tailwind CSS (CDN), autentikasi JWT.

## Struktur folder

```
PERPUS-KELOMPOK-4/
├── index.php                # Redirect jaga-jaga ke public/index.php
├── public/                  # SATU-SATUNYA entry point aplikasi (front controller)
│   ├── index.php            # Router utama, semua halaman lewat sini (?page=...)
│   └── assets/
│       └── auth-guard.js    # Helper JS bersama (auth, fetch API, dsb)
├── api/                   # Endpoint API (JSON, dilindungi JWT)
│   ├── auth.php           # POST ?action=login | ?action=register
│   ├── books.php          # GET/POST/PUT/DELETE
│   ├── categories.php     # GET/POST/PUT/DELETE
│   └── loans.php          # GET/POST/PUT
├── app/
│   ├── controller/        # Logika request -> model -> respons JSON
│   ├── models/             # OOP + PDO, satu class per tabel
│   └── views/              # Halaman HTML + Tailwind CDN (di-require oleh public/index.php, TIDAK diakses langsung)
├── config/
│   ├── Database.php        # Koneksi PDO (singleton)
│   ├── Jwt.php              # JWT custom (HS256, tanpa library eksternal)
│   ├── Auth.php             # Middleware cek token & role
│   └── schema.sql           # Skema database + data kategori contoh
└── README.md
```

## Cara mengakses halaman (routing)

Semua halaman sekarang lewat satu pintu masuk `public/index.php`, dengan parameter `?page=`:

| URL | Halaman |
|---|---|
| `public/index.php?page=login` | Login |
| `public/index.php?page=dashboard_admin` | Dashboard admin |
| `public/index.php?page=data_buku` | Data buku (admin) |
| `public/index.php?page=kelola_peminjaman` | Kelola peminjaman (admin) |
| `public/index.php?page=peminjaman_siswa` | Pinjam buku (siswa) |
| `public/index.php?page=riwayat_siswa` | Riwayat peminjaman (siswa) |

File di `app/views/*.php` **tidak lagi diakses langsung** oleh browser — kalau dibuka langsung (misal `app/views/login.php`), halaman itu masih akan jalan (PHP-nya valid), tapi **path relative-nya salah** (fetch API, link navigasi, dsb dihitung relative terhadap `public/`). Selalu akses lewat `public/index.php?page=...`.

## Cara setup

1. **Buat database**
   Import `config/schema.sql` ke MySQL (lewat phpMyAdmin atau `mysql -u root -p < config/schema.sql`).

2. **Sesuaikan koneksi database**
   Buka `config/Database.php`, ganti `$dbName`, `$username`, `$password` sesuai environment kamu.

3. **Buat akun admin pertama**
   Karena belum ada halaman register khusus admin, buat lewat API:
   ```
   POST /api/auth.php?action=register
   Body JSON: { "nama": "Admin", "username": "admin", "password": "admin123", "role": "admin" }
   ```
   Untuk akun siswa, ganti `"role": "siswa"`.

4. **Jalankan dengan PHP built-in server** (untuk development)
   Dari folder di atas `PERPUS-KELOMPOK-4`:
   ```
   php -S localhost:8000
   ```
   Lalu buka `http://localhost:8000/PERPUS-KELOMPOK-4/public/index.php?page=login`

   > Kalau pakai XAMPP/Laragon/AppServ, document root tetap folder project (bukan `public/` saja),
   > karena folder `api/` juga perlu bisa diakses langsung oleh browser (dipanggil lewat `fetch()`).
   > Jadi `public/` di sini murni konvensi/organisasi kode MVC, bukan pemisah akses web server yang ketat.

## Alur autentikasi

- Login mengembalikan JWT token, disimpan di `localStorage`.
- Setiap request ke API (kecuali `auth.php`) wajib menyertakan header `Authorization: Bearer <token>`.
- `config/Auth.php` memvalidasi token dan role (`admin` / `siswa`) sebelum request diteruskan ke controller.

## Aturan bisnis yang sudah diimplementasikan

- Siswa maksimal **3 buku** dipinjam aktif dalam waktu bersamaan (bisa diubah di `app/models/Loan.php`).
- Durasi peminjaman default **14 hari** dari tanggal pinjam.
- Status peminjaman otomatis berubah jadi `terlambat` kalau melewati `batas_kembali` (dicek tiap kali data peminjaman diambil).
- Stok buku otomatis berkurang saat dipinjam dan bertambah saat ditandai dikembalikan.
- ISBN unik, tidak bisa duplikat antar buku.

## Perbaikan yang sudah dilakukan (review kedua)

- Path API di frontend diganti jadi relative (`../../api`) agar tidak error kalau proyek dipindah folder/document root.
- `Jwt::getBearerToken()` sekarang punya fallback kalau fungsi `getallheaders()` tidak tersedia di server (umum terjadi di setup nginx + php-fpm).
- Proses **pinjam buku** dan **tandai kembali** sekarang dibungkus database transaction + row locking (`FOR UPDATE`), supaya stok tidak salah hitung kalau dua siswa mengajukan pinjam buku yang sama di waktu bersamaan.
- Tombol "Edit" di halaman Data Buku tidak lagi menyisipkan JSON mentah ke atribut `onclick` (berisiko rusak kalau judul buku mengandung tanda kutip) — sekarang pakai lookup by ID.
- Semua data dari database (judul, ISBN, nama kategori, nama siswa) di-escape sebelum ditampilkan di halaman, supaya karakter khusus tidak merusak tampilan.

## Yang masih perlu kamu lengkapi

- Halaman untuk admin mengelola kategori (CRUD kategori sudah ada di API `categories.php`, tinggal buat view-nya kalau perlu).
- Validasi format ISBN yang lebih ketat kalau diperlukan dosen/guru pembimbing.
- Penyesuaian `config/Jwt.php` `$secretKey` untuk production (jangan pakai nilai default).
