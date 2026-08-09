<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Perpustakaan Sekolah</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">

<div class="text-center">
    <div class="w-12 h-12 rounded-xl bg-orange-200 flex items-center justify-center mx-auto mb-3">
        <span class="text-xl">📚</span>
    </div>
    <p class="text-gray-500 text-sm">Memuat Perpustakaan Sekolah...</p>
</div>

<noscript>
    <p class="text-center text-sm text-red-600 mt-3">
        Aktifkan JavaScript di browser kamu untuk menggunakan aplikasi ini,
        atau buka langsung <a href="app/views/login.php" class="underline">halaman login</a>.
    </p>
</noscript>

<script>
    // index.php adalah entry point utama proyek ini.
    // Karena autentikasi memakai JWT yang disimpan di localStorage (bukan session PHP),
    // pengecekan "sudah login atau belum" dilakukan di sisi client lewat script ini,
    // lalu diarahkan ke halaman yang sesuai.

    const token = localStorage.getItem('token');
    const user = JSON.parse(localStorage.getItem('user') || 'null');

    if (token && user) {
        // Sudah login -> arahkan sesuai role
        window.location.href = user.role === 'admin'
            ? 'app/views/dashboard_admin.php'
            : 'app/views/peminjaman_siswa.php';
    } else {
        // Belum login -> arahkan ke halaman login
        window.location.href = 'app/views/login.php';
    }
</script>

</body>
</html>
