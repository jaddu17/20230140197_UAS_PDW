<?php
require_once '../config.php';
require_once 'templates/header.php'; // Includes check_login_and_role("asisten")

$filter_modul = $_GET['filter_modul'] ?? '';
$filter_mahasiswa = $_GET['filter_mahasiswa'] ?? '';
$filter_status = $_GET['filter_status'] ?? ''; // 'sudah_dinilai', 'belum_dinilai', 'semua'

$laporan_list = [];
$sql_params = [];
$sql_types = "";

$sql = "SELECT
            l.id AS laporan_id,
            l.file_laporan,
            l.tanggal_upload,
            m.judul AS nama_modul,
            p.nama_praktikum,
            u.nama AS nama_mahasiswa,
            u.email AS email_mahasiswa,
            n.nilai,
            n.komentar
        FROM
            laporan l
        JOIN
            modul m ON l.id_modul = m.id
        JOIN
            praktikum p ON m.id_praktikum = p.id
        JOIN
            users u ON l.id_user = u.id
        LEFT JOIN
            nilai n ON l.id_user = n.id_user AND l.id_modul = n.id_modul
        WHERE 1=1"; // Start with a true condition

// Apply filters
if (!empty($filter_modul)) {
    $sql .= " AND m.id = ?";
    $sql_types .= "i";
    $sql_params[] = $filter_modul;
}
if (!empty($filter_mahasiswa)) {
    $sql .= " AND u.id = ?";
    $sql_types .= "i";
    $sql_params[] = $filter_mahasiswa;
}
if ($filter_status === 'sudah_dinilai') {
    $sql .= " AND n.nilai IS NOT NULL";
} elseif ($filter_status === 'belum_dinilai') {
    $sql .= " AND n.nilai IS NULL";
}

$sql .= " ORDER BY l.tanggal_upload DESC";

if ($stmt = mysqli_prepare($link, $sql)) {
    if (!empty($sql_params)) {
        mysqli_stmt_bind_param($stmt, $sql_types, ...$sql_params);
    }
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $laporan_list[] = $row;
        }
        mysqli_free_result($result);
    } else {
        echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">Oops! Terjadi kesalahan saat mengambil data laporan.</div>';
    }
    mysqli_stmt_close($stmt);
}

// Get lists for filter options
$all_modul = [];
$sql_modul = "SELECT id, judul FROM modul ORDER BY judul ASC";
if ($result_modul = mysqli_query($link, $sql_modul)) {
    while ($row = mysqli_fetch_assoc($result_modul)) {
        $all_modul[] = $row;
    }
    mysqli_free_result($result_modul);
}

$all_mahasiswa = [];
$sql_mahasiswa = "SELECT id, nama FROM users WHERE role = 'mahasiswa' ORDER BY nama ASC";
if ($result_mahasiswa = mysqli_query($link, $sql_mahasiswa)) {
    while ($row = mysqli_fetch_assoc($result_mahasiswa)) {
        $all_mahasiswa[] = $row;
    }
    mysqli_free_result($result_mahasiswa);
}
?>

<h1 class="text-3xl font-bold mb-6">Laporan Masuk Mahasiswa</h1>

<!-- Filter Section -->
<div class="bg-white p-6 rounded-lg shadow-md mb-8">
    <h2 class="text-xl font-semibold mb-4">Filter Laporan</h2>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="get" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label for="filter_modul" class="block text-gray-700 text-sm font-bold mb-2">Berdasarkan Modul:</label>
            <select name="filter_modul" id="filter_modul" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                <option value="">Semua Modul</option>
                <?php foreach ($all_modul as $modul): ?>
                    <option value="<?php echo $modul['id']; ?>" <?php echo ($filter_modul == $modul['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($modul['judul']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="filter_mahasiswa" class="block text-gray-700 text-sm font-bold mb-2">Berdasarkan Mahasiswa:</label>
            <select name="filter_mahasiswa" id="filter_mahasiswa" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                <option value="">Semua Mahasiswa</option>
                <?php foreach ($all_mahasiswa as $mahasiswa): ?>
                    <option value="<?php echo $mahasiswa['id']; ?>" <?php echo ($filter_mahasiswa == $mahasiswa['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($mahasiswa['nama']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="filter_status" class="block text-gray-700 text-sm font-bold mb-2">Berdasarkan Status:</label>
            <select name="filter_status" id="filter_status" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                <option value="semua" <?php echo ($filter_status == 'semua') ? 'selected' : ''; ?>>Semua Status</option>
                <option value="sudah_dinilai" <?php echo ($filter_status == 'sudah_dinilai') ? 'selected' : ''; ?>>Sudah Dinilai</option>
                <option value="belum_dinilai" <?php echo ($filter_status == 'belum_dinilai') ? 'selected' : ''; ?>>Belum Dinilai</option>
            </select>
        </div>
        <div class="md:col-span-3 flex justify-end">
            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Terapkan Filter</button>
        </div>
    </form>
</div>

<!-- Laporan List -->
<div class="bg-white p-6 rounded-lg shadow-md">
    <h2 class="text-xl font-semibold mb-4">Daftar Laporan</h2>
    <?php if (empty($laporan_list)): ?>
        <p class="text-gray-600">Tidak ada laporan yang ditemukan dengan filter yang dipilih.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Praktikum</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Modul</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mahasiswa</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Upload</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nilai</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($laporan_list as $laporan): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($laporan['nama_praktikum']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($laporan['nama_modul']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($laporan['nama_mahasiswa']); ?> (<?php echo htmlspecialchars($laporan['email_mahasiswa']); ?>)</td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo date('d M Y H:i', strtotime($laporan['tanggal_upload'])); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php echo !empty($laporan['nilai']) ? htmlspecialchars($laporan['nilai']) : '<span class="text-red-500">Belum Dinilai</span>'; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="<?php echo BASE_URL . 'uploads/' . htmlspecialchars($laporan['file_laporan']); ?>" class="text-blue-600 hover:text-blue-900 mr-4" target="_blank">Unduh Laporan</a>
                                <a href="nilai_laporan.php?laporan_id=<?php echo $laporan['laporan_id']; ?>" class="text-indigo-600 hover:text-indigo-900">Beri/Edit Nilai</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'templates/footer.php'; ?>