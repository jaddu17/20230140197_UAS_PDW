<?php
$pageTitle = 'Dashboard';
$activePage = 'dashboard';
require_once 'templates/header_mahasiswa.php';
require_once '../config.php';

// Ambil ID mahasiswa dari session
$mahasiswa_id = $_SESSION['id'];

// Fungsi untuk menghitung berbagai statistik
function getMahasiswaStats($link, $mahasiswa_id) {
    $stats = [
        'praktikum_diikuti' => 0,
        'tugas_dikumpulkan' => 0,
        'tugas_belum_dikumpulkan' => 0,
        'tugas_terlambat' => 0
    ];

    // 1. Hitung praktikum yang diikuti
    $sql = "SELECT COUNT(DISTINCT id_praktikum) as total 
            FROM peserta_praktikum 
            WHERE id_mahasiswa = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $mahasiswa_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($result)) {
            $stats['praktikum_diikuti'] = $row['total'];
        }
        mysqli_stmt_close($stmt);
    }

    // 2. Hitung tugas yang sudah dikumpulkan
    $sql = "SELECT COUNT(DISTINCT l.id_modul) as total 
            FROM laporan l
            WHERE l.id_user = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $mahasiswa_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($result)) {
            $stats['tugas_dikumpulkan'] = $row['total'];
        }
        mysqli_stmt_close($stmt);
    }

    // 3. Hitung tugas yang belum dikumpulkan
    $sql = "SELECT COUNT(DISTINCT m.id) as total
            FROM peserta_praktikum pp
            JOIN praktikum p ON pp.id_praktikum = p.id
            JOIN modul m ON p.id = m.id_praktikum
            LEFT JOIN laporan l ON m.id = l.id_modul AND l.id_user = ?
            WHERE pp.id_mahasiswa = ? AND l.id IS NULL";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "ii", $mahasiswa_id, $mahasiswa_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($result)) {
            $stats['tugas_belum_dikumpulkan'] = $row['total'];
        }
        mysqli_stmt_close($stmt);
    }

    // 4. Hitung tugas yang terlambat dikumpulkan (opsional)
    // Pastikan kolom 'batas_waktu' ada di tabel 'modul' dan 'tanggal_upload' di tabel 'laporan'
    $sql = "SELECT COUNT(DISTINCT m.id) as total
            FROM modul m
            JOIN laporan l ON m.id = l.id_modul
            WHERE l.id_user = ? AND l.tanggal_kumpul > m.batas_waktu"; // Menggunakan tanggal_kumpul
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $mahasiswa_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($result)) {
            $stats['tugas_terlambat'] = $row['total'];
        }
        mysqli_stmt_close($stmt);
    }

    return $stats;
}

// Ambil data
$stats = getMahasiswaStats($link, $mahasiswa_id);
// Notifikasi dan progress dihapus, jadi tidak perlu memanggil fungsi getNotifications
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - SIMPRAK</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <!-- Header Selamat Datang -->
        <div class="bg-gradient-to-r from-pink-300 to-cyan-400 text-white p-8 rounded-xl shadow-lg mb-8">
            <h1 class="text-3xl font-bold">Selamat Datang Kembali, <?= htmlspecialchars($_SESSION['nama']) ?>!</h1>
            <p class="mt-2 opacity-90">Terus semangat dalam menyelesaikan semua modul praktikummu.</p>
        </div>

        <!-- Statistik -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-6 mb-8">
            <!-- Praktikum Diikuti -->
            <div class="bg-white p-6 rounded-xl shadow-md flex flex-col items-center justify-center hover:shadow-lg transition-shadow border-l-4 border-blue-500">
                <div class="text-5xl font-extrabold text-blue-600"><?= $stats['praktikum_diikuti'] ?></div>
                <div class="mt-2 text-lg text-gray-600">Praktikum Diikuti</div>
                <a href="praktikum_saya.php" class="mt-2 text-sm text-blue-500 hover:underline flex items-center">
                    <i class="fas fa-arrow-circle-right mr-1"></i> Lihat Detail
                </a>
            </div>
            
            <!-- Tugas Dikumpulkan -->
            <div class="bg-white p-6 rounded-xl shadow-md flex flex-col items-center justify-center hover:shadow-lg transition-shadow border-l-4 border-green-500">
                <div class="text-5xl font-extrabold text-green-500"><?= $stats['tugas_dikumpulkan'] ?></div>
                <div class="mt-2 text-lg text-gray-600">Tugas Dikumpulkan</div>
                <a href="praktikum_saya.php?filter=dikumpulkan" class="mt-2 text-sm text-green-500 hover:underline flex items-center">
                    <i class="fas fa-arrow-circle-right mr-1"></i> Lihat Detail
                </a>
            </div>
            
            <!-- Tugas Belum Dikumpulkan -->
            <div class="bg-white p-6 rounded-xl shadow-md flex flex-col items-center justify-center hover:shadow-lg transition-shadow border-l-4 border-yellow-500">
                <div class="text-5xl font-extrabold text-yellow-500"><?= $stats['tugas_belum_dikumpulkan'] ?></div>
                <div class="mt-2 text-lg text-gray-600">Tugas Belum Dikumpulkan</div>
                <a href="praktikum_saya.php?filter=belum_dikumpulkan" class="mt-2 text-sm text-yellow-500 hover:underline flex items-center">
                    <i class="fas fa-arrow-circle-right mr-1"></i> Lihat Detail
                </a>
            </div>
            
            <!-- Tugas Terlambat -->
            <div class="bg-white p-6 rounded-xl shadow-md flex flex-col items-center justify-center hover:shadow-lg transition-shadow border-l-4 border-red-500">
                <div class="text-5xl font-extrabold text-red-500"><?= $stats['tugas_terlambat'] ?></div>
                <div class="mt-2 text-lg text-gray-600">Tugas Terlambat</div>
                <a href="praktikum_saya.php?filter=terlambat" class="mt-2 text-sm text-red-500 hover:underline flex items-center">
                    <i class="fas fa-arrow-circle-right mr-1"></i> Lihat Detail
                </a>
            </div>
        </div>

        <!-- Notifikasi Terbaru - DIHAPUS -->
        <!-- <div class="bg-white p-6 rounded-xl shadow-md">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-2xl font-bold text-gray-800 flex items-center">
                    <i class="fas fa-bell mr-2 text-blue-500"></i> Notifikasi Terbaru
                </h3>
                <a href="notifikasi.php" class="text-sm text-blue-500 hover:underline flex items-center">
                    <i class="fas fa-list mr-1"></i> Lihat Semua
                </a>
            </div>
            
            <?php if (empty($notifications)): ?>
                <div class="text-center py-4 text-gray-500">
                    <i class="fas fa-info-circle text-2xl mb-2"></i>
                    <p>Tidak ada notifikasi terbaru</p>
                </div>
            <?php else: ?>
                <ul class="space-y-3">
                    <?php foreach ($notifications as $notif): ?>
                        <li class="flex items-start p-4 rounded-lg <?= $notif['dibaca'] ? 'bg-gray-50' : 'bg-blue-50' ?> hover:bg-gray-100 transition-colors">
                            <div class="mr-4 mt-1 text-xl">
                                <?php if (strpos($notif['judul'], 'Nilai') !== false): ?>
                                    <i class="fas fa-star text-yellow-500"></i>
                                <?php elseif (strpos($notif['judul'], 'Batas') !== false): ?>
                                    <i class="fas fa-clock text-red-500"></i>
                                <?php else: ?>
                                    <i class="fas fa-info-circle text-blue-500"></i>
                                <?php endif; ?>
                            </div>
                            <div class="flex-1">
                                <div class="font-semibold <?= $notif['dibaca'] ? 'text-gray-700' : 'text-gray-900' ?>">
                                    <?= htmlspecialchars($notif['judul']) ?>
                                </div>
                                <p class="text-gray-600 text-sm mt-1">
                                    <?= htmlspecialchars($notif['deskripsi']) ?>
                                    <?php if ($notif['modul_judul']): ?>
                                        untuk <a href="detail_praktikum.php?modul_id=<?= $notif['modul_id'] ?>" class="font-medium text-blue-500 hover:underline">
                                            <?= htmlspecialchars($notif['modul_judul']) ?>
                                        </a>
                                    <?php endif; ?>
                                </p>
                                <div class="text-xs text-gray-400 mt-2">
                                    <i class="far fa-clock mr-1"></i> <?= date('d M Y H:i', strtotime($notif['tanggal'])) ?>
                                </div>
                            </div>
                            <?php if (!$notif['dibaca']): ?>
                                <span class="w-2 h-2 bg-red-500 rounded-full ml-2 mt-2"></span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div> -->
        
        
        <!-- Grafik Progress (Opsional) - DIHAPUS -->
        <!-- <div class="bg-white p-6 rounded-xl shadow-md mt-8">
            <h3 class="text-2xl font-bold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-chart-line mr-2 text-pink-500"></i> Progress Praktikum
            </h3>
            <div class="h-64 flex items-center justify-center bg-gray-50 rounded-lg">
                <p class="text-gray-500">Grafik progress akan ditampilkan di sini</p>
            </div>
        </div> -->
    </div>

    <?php
    require_once 'templates/footer_mahasiswa.php';
    ?>
</body>
</html>
