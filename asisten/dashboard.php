<?php
// TAMPILKAN SEMUA ERROR (HANYA UNTUK DEBUGGING - HAPUS DI PRODUKSI!)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Atur zona waktu default PHP. Sesuaikan dengan zona waktu server database atau zona waktu lokal Anda.
// Contoh: 'Asia/Jakarta' untuk WIB, 'Asia/Makassar' untuk WITA, 'Asia/Jayapura' untuk WIT.
// Jika waktu di database tersimpan dalam UTC, Anda mungkin perlu mengatur ini ke 'UTC'.
date_default_timezone_set('Asia/Jakarta'); 

// 1. Definisi Variabel untuk Template
$pageTitle = 'Dashboard Asisten';
$activePage = 'dashboard';

// 2. Panggil Header
require_once '../config.php'; // Pastikan path ke config.php benar
require_once 'templates/header.php';

// Inisialisasi variabel untuk menampung data
$total_modul = 0;
$total_laporan_masuk = 0;
$laporan_belum_dinilai = 0;
$aktivitas_laporan_terbaru = [];

// --- Ambil Data Dinamis dari Database ---

// Query untuk Total Modul Diajarkan
$sql_total_modul = "SELECT COUNT(m.id) AS total_modul FROM modul m";
if ($stmt = mysqli_prepare($link, $sql_total_modul)) {
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($result)) {
            $total_modul = $row['total_modul'];
        }
    } else {
        error_log("Error executing total modul query: " . mysqli_error($link));
    }
    mysqli_stmt_close($stmt);
} else {
    error_log("Error preparing total modul query: " . mysqli_error($link));
}

// Query untuk Total Laporan Masuk
$sql_total_laporan = "SELECT COUNT(id) AS total_laporan FROM laporan";
if ($stmt = mysqli_prepare($link, $sql_total_laporan)) {
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($result)) {
            $total_laporan_masuk = $row['total_laporan'];
        }
    } else {
        error_log("Error executing total laporan query: " . mysqli_error($link));
    }
    mysqli_stmt_close($stmt);
} else {
    error_log("Error preparing total laporan query: " . mysqli_error($link));
}

// Query untuk Laporan Belum Dinilai
$sql_belum_dinilai = "SELECT COUNT(id) AS belum_dinilai FROM laporan WHERE nilai IS NULL";
if ($stmt = mysqli_prepare($link, $sql_belum_dinilai)) {
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($result)) {
            $laporan_belum_dinilai = $row['belum_dinilai'];
        }
    } else {
        error_log("Error executing belum dinilai query: " . mysqli_error($link));
    }
    mysqli_stmt_close($stmt);
} else {
    error_log("Error preparing belum dinilai query: " . mysqli_error($link));
}

// Query untuk Aktivitas Laporan Terbaru (misal 5 laporan terakhir)
$sql_aktivitas_terbaru = "SELECT 
                            l.tanggal_kumpul,
                            u.nama AS nama_mahasiswa,
                            m.judul AS judul_modul
                          FROM laporan l
                          JOIN users u ON l.id_user = u.id
                          JOIN modul m ON l.id_modul = m.id
                          ORDER BY l.tanggal_kumpul DESC
                          LIMIT 5";
if ($stmt = mysqli_prepare($link, $sql_aktivitas_terbaru)) {
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $aktivitas_laporan_terbaru[] = $row;
        }
    } else {
        error_log("Error executing aktivitas terbaru query: " . mysqli_error($link));
    }
    mysqli_stmt_close($stmt);
} else {
    error_log("Error preparing aktivitas terbaru query: " . mysqli_error($link));
}

// Fungsi bantu untuk menghitung waktu relatif (misal: "10 menit lalu")
function time_elapsed_string($datetime, $full = false) {
    if (empty($datetime) || $datetime === '0000-00-00 00:00:00') {
        return "tanggal tidak tersedia";
    }

    try {
        $now = new DateTime();
        $ago = new DateTime($datetime);
    } catch (Exception $e) {
        error_log("Error parsing datetime in time_elapsed_string: " . $e->getMessage() . " for datetime: '" . $datetime . "'");
        return "tanggal tidak valid"; // Fallback message
    }

    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'tahun',
        'm' => 'bulan',
        'w' => 'minggu',
        'd' => 'hari',
        'h' => 'jam',
        'i' => 'menit',
        's' => 'detik',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? '' : ''); // No plural for Indonesian
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' lalu' : 'baru saja';
}
?>

<div class="container mx-auto p-6">
    <h1 class="text-3xl font-bold text-gray-900 mb-6">Dashboard</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg shadow-md flex items-center space-x-4 transition-transform transform hover:scale-105">
            <div class="bg-blue-100 p-3 rounded-full">
                <svg class="w-8 h-8 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" /></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500">Total Modul Diajarkan</p>
                <p class="text-3xl font-bold text-gray-800"><?= htmlspecialchars($total_modul) ?></p>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-md flex items-center space-x-4 transition-transform transform hover:scale-105">
            <div class="bg-green-100 p-3 rounded-full">
                <svg class="w-8 h-8 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500">Total Laporan Masuk</p>
                <p class="text-3xl font-bold text-gray-800"><?= htmlspecialchars($total_laporan_masuk) ?></p>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-md flex items-center space-x-4 transition-transform transform hover:scale-105">
            <div class="bg-yellow-100 p-3 rounded-full">
                <svg class="w-8 h-8 text-yellow-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
            <div>
                <p class="text-sm text-gray-500">Laporan Belum Dinilai</p>
                <p class="text-3xl font-bold text-gray-800"><?= htmlspecialchars($laporan_belum_dinilai) ?></p>
            </div>
        </div>
    </div>

    <!-- Aktivitas Laporan Terbaru Section -->
    <div class="bg-white p-6 rounded-lg shadow-md mt-8">
        <h3 class="text-2xl font-bold text-gray-800 mb-4">Aktivitas Laporan Terbaru</h3>
        <?php if (empty($aktivitas_laporan_terbaru)): ?>
            <p class="text-gray-600">Belum ada aktivitas laporan terbaru.</p>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($aktivitas_laporan_terbaru as $aktivitas): ?>
                    <div class="flex items-center p-2 rounded-md hover:bg-gray-50 transition-colors">
                        <div class="w-12 h-12 rounded-full bg-gray-200 flex items-center justify-center mr-4 flex-shrink-0">
                            <span class="font-bold text-gray-500 text-lg">
                                <?php
                                    $initials = '';
                                    $words = explode(' ', $aktivitas['nama_mahasiswa']);
                                    foreach ($words as $word) {
                                        $initials .= strtoupper(substr($word, 0, 1));
                                    }
                                    echo htmlspecialchars($initials);
                                ?>
                            </span>
                        </div>
                        <div>
                            <p class="text-gray-800 text-lg">
                                <strong><?= htmlspecialchars($aktivitas['nama_mahasiswa']) ?></strong> mengumpulkan laporan untuk 
                                <strong><?= htmlspecialchars($aktivitas['judul_modul']) ?></strong>
                            </p>
                            <p class="text-sm text-gray-500">
                                <?php
                                    // Menampilkan waktu relatif yang sudah dihitung
                                    echo time_elapsed_string($aktivitas['tanggal_kumpul']);
                                ?>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// 3. Panggil Footer
require_once 'templates/footer.php';
?>
