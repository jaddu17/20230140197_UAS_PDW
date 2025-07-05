<?php
require_once '../config.php';
require_once 'templates/header_mahasiswa.php'; // header mahasiswa

check_login_and_role("mahasiswa");

if (!isset($_GET['praktikum_id']) || empty($_GET['praktikum_id'])) {
    header("Location: praktikum_saya.php");
    exit;
}

$praktikum_id = intval($_GET['praktikum_id']);
$praktikum_detail = [];
$modul_list = [];

// Ambil data praktikum
$sql_praktikum = "SELECT id, nama_praktikum, deskripsi FROM praktikum WHERE id = ?";
$stmt = mysqli_prepare($link, $sql_praktikum);
mysqli_stmt_bind_param($stmt, "i", $praktikum_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result && mysqli_num_rows($result) > 0) {
    $praktikum_detail = mysqli_fetch_assoc($result);
} else {
    echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        Praktikum tidak ditemukan.
    </div>';
    require_once 'templates/footer_mahasiswa.php';
    exit;
}
mysqli_stmt_close($stmt);

// Ambil modul-modul praktikum ini
$sql_modul = "SELECT id, judul, deskripsi, file_materi 
              FROM modul 
              WHERE id_praktikum = ?
              ORDER BY id ASC";
$stmt = mysqli_prepare($link, $sql_modul);
mysqli_stmt_bind_param($stmt, "i", $praktikum_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $modul_list[] = $row;
    }
}
mysqli_stmt_close($stmt);
?>

<h1 class="text-3xl font-bold mb-4">
    Detail Praktikum: <?php echo htmlspecialchars($praktikum_detail['nama_praktikum']); ?>
</h1>

<p class="text-gray-700 mb-6">
    <?php echo nl2br(htmlspecialchars($praktikum_detail['deskripsi'])); ?>
</p>

<h2 class="text-2xl font-semibold mb-4">Daftar Modul</h2>

<?php if (empty($modul_list)) : ?>
    <p class="text-gray-600">Belum ada modul untuk praktikum ini.</p>
<?php else: ?>
    <?php foreach ($modul_list as $modul) : ?>
        <div class="bg-white p-6 rounded shadow mb-6">
            <h3 class="text-xl font-semibold mb-2">
                <?php echo htmlspecialchars($modul['judul']); ?>
            </h3>
            
            <?php if (!empty($modul['deskripsi'])) : ?>
                <p class="text-gray-700 mb-3">
                    <?php echo nl2br(htmlspecialchars($modul['deskripsi'])); ?>
                </p>
            <?php endif; ?>
            
            <div class="mt-4">
                <h4 class="text-lg font-semibold mb-2">File Materi:</h4>
                <?php if (!empty($modul['file_materi'])) : ?>
                    <a href="<?php echo BASE_URL . 'uploads/' . htmlspecialchars($modul['file_materi']); ?>"
                       class="inline-block bg-blue-600 hover:bg-blue-800 text-white font-bold py-2 px-4 rounded mb-2"
                       target="_blank">
                        Unduh Materi
                    </a>
                <?php else : ?>
                    <p class="text-gray-600">Belum ada file materi untuk modul ini.</p>
                <?php endif; ?>
            </div>

            <div class="mt-4">
                <a href="upload_laporan.php?modul_id=<?php echo $modul['id']; ?>"
                   class="inline-block bg-green-600 hover:bg-green-800 text-white font-bold py-2 px-4 rounded">
                    Upload Laporan
                </a>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<a href="praktikum_saya.php"
   class="inline-block bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded mb-6">
   ← Kembali ke Praktikum Saya
</a>

<?php require_once 'templates/footer_mahasiswa.php'; ?>
