<?php
require_once '../config.php';
require_once 'templates/header_mahasiswa.php'; // Includes check_login_and_role("mahasiswa")

// Get praktikum that the current student has enrolled in
$enrolled_praktikum_list = [];
$mahasiswa_id = $_SESSION["id"];

$sql = "SELECT pp.id AS peserta_praktikum_id, p.id AS praktikum_id, p.nama_praktikum, p.deskripsi
        FROM peserta_praktikum pp
        JOIN praktikum p ON pp.id_praktikum = p.id
        WHERE pp.id_mahasiswa = ?
        ORDER BY p.nama_praktikum ASC";

if ($stmt = mysqli_prepare($link, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $mahasiswa_id);
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_bind_result($stmt, $peserta_praktikum_id, $praktikum_id, $nama_praktikum, $deskripsi);
        while (mysqli_stmt_fetch($stmt)) {
            $enrolled_praktikum_list[] = [
                'peserta_praktikum_id' => $peserta_praktikum_id,
                'praktikum_id' => $praktikum_id,
                'nama_praktikum' => $nama_praktikum,
                'deskripsi' => $deskripsi
            ];
        }
    } else {
        echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">Oops! Terjadi kesalahan saat mengambil data praktikum Anda.</div>';
    }
    mysqli_stmt_close($stmt);
}
?>

<h1 class="text-3xl font-bold mb-6">Praktikum yang Saya Ikuti</h1>

<?php if (empty($enrolled_praktikum_list)): ?>
    <p class="text-gray-600">Anda belum mendaftar ke praktikum manapun.</p>
    <p class="text-gray-600">Kunjungi <a href="<?php echo BASE_URL; ?>mahasiswa/katalog_praktikum.php" class="text-blue-500 hover:text-blue-700">Katalog Praktikum</a> untuk mendaftar.</p>
<?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($enrolled_praktikum_list as $praktikum): ?>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h3 class="text-xl font-semibold mb-2"><?php echo htmlspecialchars($praktikum['nama_praktikum']); ?></h3>
                <p class="text-gray-700 mb-4"><?php echo nl2br(htmlspecialchars($praktikum['deskripsi'])); ?></p>
                <a href="<?php echo BASE_URL; ?>mahasiswa/detail_praktikum.php?praktikum_id=<?php echo $praktikum['praktikum_id']; ?>" class="bg-indigo-500 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">Lihat Detail & Tugas</a>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once 'templates/footer_mahasiswa.php'; ?>
