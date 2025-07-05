<?php
require_once '../config.php';
require_once 'templates/header_mahasiswa.php';

// Pastikan user adalah mahasiswa
check_login_and_role("mahasiswa");

$id_user = $_SESSION['id'];
$file_laporan_err = "";

// Pastikan modul_id dikirim
if (!isset($_GET['modul_id']) || empty($_GET['modul_id'])) {
    echo '<div class="bg-red-100 text-red-700 p-4 rounded mb-4">Modul tidak ditemukan.</div>';
    exit;
}

$modul_id = intval($_GET['modul_id']);

// Ambil data modul
$sql_modul = "SELECT id, judul, deskripsi FROM modul WHERE id = ?";
$stmt = mysqli_prepare($link, $sql_modul);
mysqli_stmt_bind_param($stmt, "i", $modul_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$modul_data = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$modul_data) {
    echo '<div class="bg-red-100 text-red-700 p-4 rounded mb-4">Modul tidak ditemukan.</div>';
    exit;
}

// Ambil data laporan jika sudah pernah upload
$sql_laporan = "SELECT * FROM laporan WHERE id_user = ? AND id_modul = ?";
$stmt = mysqli_prepare($link, $sql_laporan);
mysqli_stmt_bind_param($stmt, "ii", $id_user, $modul_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$laporan_data = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// Handle form submit
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submit"])) {

    if (isset($_FILES["file_laporan"]) && $_FILES["file_laporan"]["error"] == 0) {
        $allowed_types = [
            'application/pdf',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];

        $file_type = mime_content_type($_FILES["file_laporan"]["tmp_name"]);

        if (in_array($file_type, $allowed_types)) {
            $ext = pathinfo($_FILES["file_laporan"]["name"], PATHINFO_EXTENSION);
            $new_filename = uniqid('laporan_') . '_' . $id_user . '_' . $modul_id . '.' . $ext;
            $target_path = UPLOAD_DIR . $new_filename;

            if (move_uploaded_file($_FILES["file_laporan"]["tmp_name"], $target_path)) {

                if ($laporan_data) {
                    // Update laporan
                    $old_file = $laporan_data['file_laporan'];

                    $sql_update = "
                        UPDATE laporan
                        SET file_laporan = ?, tanggal_kumpul = CURRENT_TIMESTAMP
                        WHERE id_user = ? AND id_modul = ?
                    ";
                    $stmt = mysqli_prepare($link, $sql_update);
                    mysqli_stmt_bind_param($stmt, "sii", $new_filename, $id_user, $modul_id);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);

                    // hapus file lama
                    if ($old_file && file_exists(UPLOAD_DIR . $old_file)) {
                        unlink(UPLOAD_DIR . $old_file);
                    }

                    echo '<div class="bg-green-100 text-green-700 p-4 rounded mb-4">Laporan berhasil diperbarui.</div>';
                } else {
                    // Insert laporan baru
                    $sql_insert = "
                        INSERT INTO laporan (id_user, id_modul, file_laporan, tanggal_kumpul)
                        VALUES (?, ?, ?, CURRENT_TIMESTAMP)
                    ";
                    $stmt = mysqli_prepare($link, $sql_insert);
                    mysqli_stmt_bind_param($stmt, "iis", $id_user, $modul_id, $new_filename);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);

                    echo '<div class="bg-green-100 text-green-700 p-4 rounded mb-4">Laporan berhasil diunggah.</div>';
                }

                // Refresh page agar data terbaru tampil
                header("Location: upload_laporan.php?modul_id=" . $modul_id);
                exit;

            } else {
                $file_laporan_err = "Gagal menyimpan file laporan ke server.";
            }
        } else {
            $file_laporan_err = "Tipe file tidak diizinkan. Hanya PDF dan DOCX.";
        }
    } else {
        $file_laporan_err = "Silakan pilih file laporan.";
    }
}
?>

<h1 class="text-2xl font-bold mb-4">Upload Laporan</h1>

<div class="bg-white p-6 rounded shadow mb-6">
    <h2 class="text-xl font-semibold mb-2"><?php echo htmlspecialchars($modul_data['judul']); ?></h2>
    <p class="text-gray-700 mb-4">
        <?php echo nl2br(htmlspecialchars($modul_data['deskripsi'])); ?>
    </p>

    <form action="" method="post" enctype="multipart/form-data">
        <label class="block text-gray-700 font-semibold mb-2">
            Pilih File Laporan (PDF / DOCX):
        </label>
        <input type="file" name="file_laporan" class="mb-4 border rounded px-3 py-2 w-full">

        <?php if (!empty($file_laporan_err)) : ?>
            <div class="bg-red-100 text-red-700 p-2 rounded mb-4">
                <?php echo htmlspecialchars($file_laporan_err); ?>
            </div>
        <?php endif; ?>

        <button type="submit" name="submit"
                class="bg-indigo-600 hover:bg-indigo-800 text-white font-bold py-2 px-4 rounded">
            <?php echo $laporan_data ? 'Perbarui Laporan' : 'Unggah Laporan'; ?>
        </button>
    </form>

    <?php if ($laporan_data) : ?>
        <div class="mt-6">
            <p class="text-sm text-gray-600">
                Laporan terakhir diunggah pada:
                <?php echo date('d M Y H:i', strtotime($laporan_data['tanggal_kumpul'])); ?>
            </p>
            <a href="<?php echo BASE_URL . 'uploads/' . htmlspecialchars($laporan_data['file_laporan']); ?>"
               class="text-blue-500 hover:underline mt-2 inline-block" target="_blank">
                Lihat Laporan
            </a>

            <?php if (!is_null($laporan_data['nilai'])) : ?>
                <p class="mt-4 text-green-700 font-semibold">
                    Nilai: <?php echo htmlspecialchars($laporan_data['nilai']); ?>
                </p>
                <p class="text-gray-700">
                    Feedback: <?php echo nl2br(htmlspecialchars($laporan_data['feedback'])); ?>
                </p>
            <?php else: ?>
                <p class="text-gray-600 mt-4">Belum dinilai.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<a href="praktikum_saya.php"
   class="inline-block bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
    ← Kembali ke Praktikum Saya
</a>

<?php require_once 'templates/footer_mahasiswa.php'; ?>
