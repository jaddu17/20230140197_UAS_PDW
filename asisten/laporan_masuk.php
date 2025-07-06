<?php
// TAMPILKAN SEMUA ERROR (HANYA UNTUK DEBUGGING - HAPUS DI PRODUKSI!)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config.php';
require_once 'templates/header.php';

// Inisialisasi variabel filter
$filter_modul_id = isset($_GET['filter_modul']) ? intval($_GET['filter_modul']) : '';

// Handle Delete
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    // Ambil nama file dulu supaya bisa dihapus dari folder
    $sql_get_file = "SELECT file_laporan FROM laporan WHERE id = ?";
    if ($stmt = mysqli_prepare($link, $sql_get_file)) {
        mysqli_stmt_bind_param($stmt, "i", $delete_id);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_bind_result($stmt, $file_laporan);
            if (mysqli_stmt_fetch($stmt)) {
                $file_path = UPLOAD_DIR . $file_laporan;
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }
        }
        mysqli_stmt_close($stmt);
    }

    // Hapus data laporan dari database
    $sql_delete = "DELETE FROM laporan WHERE id = ?";
    if ($stmt = mysqli_prepare($link, $sql_delete)) {
        mysqli_stmt_bind_param($stmt, "i", $delete_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    // Return JSON response untuk AJAX
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success']);
    exit;
}

// Handle Update Nilai dan Feedback
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_laporan'])) {
    $id = intval($_POST['laporan_id']);
    $nilai = trim($_POST['nilai']);
    $feedback = trim($_POST['feedback']);

    // Konversi nilai kosong menjadi NULL untuk database
    $nilai = ($nilai === '') ? NULL : intval($nilai);
    $feedback = ($feedback === '') ? NULL : $feedback;

    $sql_update = "UPDATE laporan SET nilai = ?, feedback = ? WHERE id = ?";
    if ($stmt = mysqli_prepare($link, $sql_update)) {
        mysqli_stmt_bind_param($stmt, "isi", $nilai, $feedback, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    
    // Return JSON response untuk AJAX
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success']);
    exit;
}

// --- Ambil Data untuk Filter Dropdown Modul ---
$modules = [];
$sql_modules = "SELECT id, judul FROM modul ORDER BY judul ASC";
if ($result_modules = mysqli_query($link, $sql_modules)) {
    while ($row = mysqli_fetch_assoc($result_modules)) {
        $modules[] = $row;
    }
    mysqli_free_result($result_modules);
}

// Jika request AJAX untuk filter
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    // --- Fetch Laporan dengan Filter Modul ---
    $sql = "SELECT 
                l.id, 
                l.file_laporan, 
                l.tanggal_kumpul, 
                l.nilai, 
                l.feedback, 
                u.nama AS nama_user, 
                m.judul AS judul_modul
            FROM laporan l
            JOIN users u ON l.id_user = u.id 
            JOIN modul m ON l.id_modul = m.id";

    $conditions = [];
    $params = [];
    $param_types = "";

    if (!empty($filter_modul_id)) {
        $conditions[] = "l.id_modul = ?";
        $params[] = $filter_modul_id;
        $param_types .= "i";
    }

    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }

    $sql .= " ORDER BY l.tanggal_kumpul DESC";

    $stmt = mysqli_prepare($link, $sql);

    if ($stmt) {
        if (!empty($params)) {
            mysqli_stmt_bind_param($stmt, $param_types, ...$params);
        }
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $reports = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $reports[] = $row;
        }
        
        header('Content-Type: application/json');
        echo json_encode($reports);
        exit;
    } else {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database error']);
        exit;
    }
}

// --- Fetch Laporan untuk tampilan awal ---
$sql_initial = "SELECT 
                l.id, 
                l.file_laporan, 
                l.tanggal_kumpul, 
                l.nilai, 
                l.feedback, 
                u.nama AS nama_user, 
                m.judul AS judul_modul
            FROM laporan l
            JOIN users u ON l.id_user = u.id 
            JOIN modul m ON l.id_modul = m.id";

$conditions_initial = [];
$params_initial = [];
$param_types_initial = "";

if (!empty($filter_modul_id)) {
    $conditions_initial[] = "l.id_modul = ?";
    $params_initial[] = $filter_modul_id;
    $param_types_initial .= "i";
}

if (!empty($conditions_initial)) {
    $sql_initial .= " WHERE " . implode(" AND ", $conditions_initial);
}

$sql_initial .= " ORDER BY l.tanggal_kumpul DESC";

$stmt_initial = mysqli_prepare($link, $sql_initial);

if ($stmt_initial) {
    if (!empty($params_initial)) {
        mysqli_stmt_bind_param($stmt_initial, $param_types_initial, ...$params_initial);
    }
    mysqli_stmt_execute($stmt_initial);
    $result_initial = mysqli_stmt_get_result($stmt_initial);
}
?>

<div class="container mx-auto p-6">
    <h1 class="text-3xl font-bold mb-6 text-gray-800">Laporan Masuk</h1>

    <!-- Filter Form (Hanya Modul) -->
    <div class="bg-white p-6 rounded-lg shadow-md mb-6">
        <h2 class="text-xl font-semibold mb-4 text-gray-700">Filter Laporan</h2>
        <form id="filterForm" class="grid grid-cols-1 md:grid-cols-2 gap-4 items-end">
            <div>
                <label for="filter_modul" class="block text-gray-700 text-sm font-bold mb-2">Modul:</label>
                <select name="filter_modul" id="filter_modul" class="shadow border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    <option value="">Semua Modul</option>
                    <?php foreach ($modules as $modul): ?>
                        <option value="<?= htmlspecialchars($modul['id']) ?>" <?= ($filter_modul_id == $modul['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($modul['judul']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex justify-end md:col-span-1">
                <button type="submit" class="bg-pink-500 hover:bg-pink-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline transition-colors">
                    Terapkan Filter
                </button>
                <button type="button" id="resetFilter" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded ml-2 focus:outline-none focus:shadow-outline transition-colors">
                    Reset Filter
                </button>
            </div>
        </form>
    </div>

    <div id="reportContainer">
        <!-- Tampilkan tabel laporan awal -->
        <?php if (isset($result_initial) && mysqli_num_rows($result_initial) == 0): ?>
            <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-4 rounded-md" role="alert">
                <p>Tidak ada laporan yang ditemukan dengan filter ini.</p>
            </div>
        <?php elseif (isset($result_initial)): ?>
            <div class="overflow-x-auto bg-white rounded-lg shadow-md">
                <table class="min-w-full leading-normal">
                    <thead>
                        <tr class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                            <th class="py-3 px-6 text-left">ID</th>
                            <th class="py-3 px-6 text-left">Mahasiswa</th>
                            <th class="py-3 px-6 text-left">Modul</th>
                            <th class="py-3 px-6 text-left">File Laporan</th>
                            <th class="py-3 px-6 text-left">Tanggal Kumpul</th>
                            <th class="py-3 px-6 text-left">Nilai</th>
                            <th class="py-3 px-6 text-left">Feedback</th>
                            <th class="py-3 px-6 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-600 text-sm">
                        <?php while ($row = mysqli_fetch_assoc($result_initial)): ?>
                            <tr class="border-b border-gray-200 hover:bg-gray-100">
                                <td class="py-3 px-6 whitespace-nowrap"><?php echo $row['id']; ?></td>
                                <td class="py-3 px-6 whitespace-nowrap"><?php echo htmlspecialchars($row['nama_user']); ?></td>
                                <td class="py-3 px-6 whitespace-nowrap"><?php echo htmlspecialchars($row['judul_modul']); ?></td>
                                <td class="py-3 px-6">
                                    <?php if (!empty($row['file_laporan'])): ?>
                                        <a href="<?php echo BASE_URL . 'uploads/' . htmlspecialchars($row['file_laporan']); ?>" target="_blank" class="text-blue-500 hover:underline">Lihat</a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-6 whitespace-nowrap"><?php echo $row['tanggal_kumpul'] ?? '-'; ?></td>
                                <td class="py-3 px-6">
                                    <form method="post" class="update-form flex flex-col sm:flex-row items-start sm:items-center space-y-2 sm:space-y-0 sm:space-x-2">
                                        <input type="hidden" name="laporan_id" value="<?php echo $row['id']; ?>">
                                        <input type="text" name="nilai" value="<?= htmlspecialchars($row['nilai'] ?? '') ?>" class="border rounded px-2 py-1 w-20 text-center text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </td>
                                <td class="py-3 px-6">
                                        <textarea name="feedback" rows="2" class="border rounded px-2 py-1 w-full text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Tambahkan feedback..."><?= htmlspecialchars($row['feedback'] ?? '') ?></textarea>
                                </td>
                                <td class="py-3 px-6 text-center whitespace-nowrap">
                                        <button type="submit" name="update_laporan" class="bg-pink-500 hover:bg-pink-700 text-white px-3 py-1 rounded text-xs transition-colors mb-2 sm:mb-0">Simpan</button>
                                    </form>
                                    <a href="#" class="delete-report bg-red-500 hover:bg-red-700 text-white px-3 py-1 rounded text-xs transition-colors" data-id="<?php echo $row['id']; ?>">Hapus</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterForm = document.getElementById('filterForm');
    const resetFilter = document.getElementById('resetFilter');
    const reportContainer = document.getElementById('reportContainer');
    
    // Fungsi untuk memuat data laporan via AJAX
    function loadReports(filterModul = '') {
        const xhr = new XMLHttpRequest();
        xhr.open('GET', `kelola_laporan.php?ajax=1&filter_modul=${filterModul}`, true);
        
        xhr.onload = function() {
            if (this.status === 200) {
                const reports = JSON.parse(this.responseText);
                
                // Buat tabel HTML dari data
                let html = `
                    <div class="overflow-x-auto bg-white rounded-lg shadow-md">
                        <table class="min-w-full leading-normal">
                            <thead>
                                <tr class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                                    <th class="py-3 px-6 text-left">ID</th>
                                    <th class="py-3 px-6 text-left">Mahasiswa</th>
                                    <th class="py-3 px-6 text-left">Modul</th>
                                    <th class="py-3 px-6 text-left">File Laporan</th>
                                    <th class="py-3 px-6 text-left">Tanggal Kumpul</th>
                                    <th class="py-3 px-6 text-left">Nilai</th>
                                    <th class="py-3 px-6 text-left">Feedback</th>
                                    <th class="py-3 px-6 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-600 text-sm">`;
                
                if (reports.length === 0) {
                    html += `
                        <tr>
                            <td colspan="8" class="py-4 text-center">
                                <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded-md">
                                    Tidak ada laporan yang ditemukan dengan filter ini.
                                </div>
                            </td>
                        </tr>`;
                } else {
                    reports.forEach(row => {
                        html += `
                            <tr class="border-b border-gray-200 hover:bg-gray-100">
                                <td class="py-3 px-6 whitespace-nowrap">${row.id}</td>
                                <td class="py-3 px-6 whitespace-nowrap">${escapeHtml(row.nama_user)}</td>
                                <td class="py-3 px-6 whitespace-nowrap">${escapeHtml(row.judul_modul)}</td>
                                <td class="py-3 px-6">`;
                        
                        if (row.file_laporan) {
                            html += `<a href="<?php echo BASE_URL ?>uploads/${escapeHtml(row.file_laporan)}" target="_blank" class="text-blue-500 hover:underline">Lihat</a>`;
                        } else {
                            html += '-';
                        }
                        
                        html += `</td>
                                <td class="py-3 px-6 whitespace-nowrap">${row.tanggal_kumpul || '-'}</td>
                                <td class="py-3 px-6">
                                    <form method="post" class="update-form flex flex-col sm:flex-row items-start sm:items-center space-y-2 sm:space-y-0 sm:space-x-2">
                                        <input type="hidden" name="laporan_id" value="${row.id}">
                                        <input type="text" name="nilai" value="${row.nilai || ''}" class="border rounded px-2 py-1 w-20 text-center text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </td>
                                <td class="py-3 px-6">
                                        <textarea name="feedback" rows="2" class="border rounded px-2 py-1 w-full text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Tambahkan feedback...">${row.feedback || ''}</textarea>
                                </td>
                                <td class="py-3 px-6 text-center whitespace-nowrap">
                                        <button type="submit" name="update_laporan" class="bg-pink-500 hover:bg-pink-700 text-white px-3 py-1 rounded text-xs transition-colors mb-2 sm:mb-0">Simpan</button>
                                    </form>
                                    <a href="#" class="delete-report bg-red-500 hover:bg-red-700 text-white px-3 py-1 rounded text-xs transition-colors" data-id="${row.id}">Hapus</a>
                                </td>
                            </tr>`;
                    });
                }
                
                html += `</tbody></table></div>`;
                reportContainer.innerHTML = html;
                
                // Tambahkan event listener untuk form update
                document.querySelectorAll('.update-form').forEach(form => {
                    form.addEventListener('submit', function(e) {
                        e.preventDefault();
                        
                        const formData = new FormData(this);
                        formData.append('update_laporan', '1');
                        
                        const xhr = new XMLHttpRequest();
                        xhr.open('POST', 'kelola_laporan.php', true);
                        
                        xhr.onload = function() {
                            if (this.status === 200) {
                                const response = JSON.parse(this.responseText);
                                if (response.status === 'success') {
                                    alert('Data berhasil diperbarui');
                                    // Reload reports dengan filter yang sama
                                    loadReports(document.getElementById('filter_modul').value);
                                }
                            }
                        };
                        
                        xhr.send(formData);
                    });
                });
                
                // Tambahkan event listener untuk tombol hapus
                document.querySelectorAll('.delete-report').forEach(btn => {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        
                        if (confirm('Apakah Anda yakin ingin menghapus laporan ini? Tindakan ini tidak dapat dibatalkan.')) {
                            const id = this.getAttribute('data-id');
                            const filterModul = document.getElementById('filter_modul').value;
                            
                            const xhr = new XMLHttpRequest();
                            xhr.open('GET', `kelola_laporan.php?delete_id=${id}&ajax=1`, true);
                            
                            xhr.onload = function() {
                                if (this.status === 200) {
                                    const response = JSON.parse(this.responseText);
                                    if (response.status === 'success') {
                                        alert('Laporan berhasil dihapus');
                                        // Reload reports dengan filter yang sama
                                        loadReports(filterModul);
                                    }
                                }
                            };
                            
                            xhr.send();
                        }
                    });
                });
            }
        };
        
        xhr.send();
    }
    
    // Fungsi untuk escape HTML
    function escapeHtml(unsafe) {
        if (!unsafe) return '';
        return unsafe
            .toString()
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
    
    // Event listener untuk form filter
    filterForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const filterModul = document.getElementById('filter_modul').value;
        loadReports(filterModul);
    });
    
    // Event listener untuk reset filter
    resetFilter.addEventListener('click', function() {
        document.getElementById('filter_modul').value = '';
        loadReports();
    });
    
    // Jika ada filter di URL saat pertama kali load
    <?php if (!empty($filter_modul_id)): ?>
        loadReports('<?= $filter_modul_id ?>');
    <?php endif; ?>
});
</script>

<?php
require_once 'templates/footer.php';
?>