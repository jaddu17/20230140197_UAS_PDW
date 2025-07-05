<?php
require_once '../config.php';

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

    $sql_delete = "DELETE FROM laporan WHERE id = ?";
    if ($stmt = mysqli_prepare($link, $sql_delete)) {
        mysqli_stmt_bind_param($stmt, "i", $delete_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    header("Location: kelola_laporan.php");
    exit;
}

// Handle Update Nilai dan Feedback
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_laporan'])) {
    $id = intval($_POST['laporan_id']);
    $nilai = $_POST['nilai'];
    $feedback = $_POST['feedback'];

    $sql_update = "UPDATE laporan SET nilai = ?, feedback = ? WHERE id = ?";
    if ($stmt = mysqli_prepare($link, $sql_update)) {
        mysqli_stmt_bind_param($stmt, "ssi", $nilai, $feedback, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    header("Location: kelola_laporan.php");
    exit;
}

// Fetch all laporan - SESUAIKAN DENGAN NAMA TABEL USER ANDA
$sql = "SELECT l.id, l.file_laporan, l.tanggal_kumpul, l.nilai, l.feedback, 
               u.nama AS nama_user, 
               m.judul AS judul_modul
        FROM laporan l
        JOIN users u ON l.id_user = u.id  
        JOIN modul m ON l.id_modul = m.id
        ORDER BY l.tanggal_kumpul DESC";

$result = mysqli_query($link, $sql);

if (!$result) {
    die("Error dalam query: " . mysqli_error($link));
}

require_once 'templates/header.php';
?>

<div class="container mx-auto p-6">
    <h1 class="text-3xl font-bold mb-6">Kelola Laporan</h1>

    <table class="min-w-full bg-white border">
        <thead>
            <tr class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                <th class="py-3 px-6 text-left">ID</th>
                <th class="py-3 px-6 text-left">Mahasiswa</th>
                <th class="py-3 px-6 text-left">Modul</th>
                <th class="py-3 px-6 text-left">File Laporan</th>
                <th class="py-3 px-6 text-left">Tanggal Kumpul</th>
                <th class="py-3 px-6 text-left">Nilai</th>
                <th class="py-3 px-6 text-left">Feedback</th>
                <th class="py-3 px-6 text-left">Aksi</th>
            </tr>
        </thead>
        <tbody class="text-gray-600 text-sm">
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <tr class="border-b border-gray-200 hover:bg-gray-100">
                    <td class="py-3 px-6"><?php echo $row['id']; ?></td>
                    <td class="py-3 px-6"><?php echo htmlspecialchars($row['nama_user']); ?></td>
                    <td class="py-3 px-6"><?php echo htmlspecialchars($row['judul_modul']); ?></td>
                    <td class="py-3 px-6">
                        <?php if (!empty($row['file_laporan'])): ?>
                            <a href="<?php echo BASE_URL . 'uploads/' . $row['file_laporan']; ?>" target="_blank" class="text-blue-500 hover:underline">Lihat</a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td class="py-3 px-6"><?php echo $row['tanggal_kumpul'] ?? '-'; ?></td>
                    <td class="py-3 px-6">
                        <form method="post" class="flex items-center space-x-2">
                            <input type="hidden" name="laporan_id" value="<?php echo $row['id']; ?>">
                            <input type="text" name="nilai" value="<?php echo htmlspecialchars($row['nilai']); ?>" class="border px-2 py-1 w-16">
                    </td>
                    <td class="py-3 px-6">
                            <textarea name="feedback" rows="1" class="border px-2 py-1"><?php echo htmlspecialchars($row['feedback']); ?></textarea>
                    </td>
                    <td class="py-3 px-6">
                            <button type="submit" name="update_laporan" class="bg-green-500 hover:bg-green-700 text-white px-3 py-1 rounded text-xs">Simpan</button>
                        </form>
                        <a href="kelola_laporan.php?delete_id=<?php echo $row['id']; ?>" onclick="return confirm('Hapus laporan ini?')" class="bg-red-500 hover:bg-red-700 text-white px-3 py-1 rounded text-xs ml-2">Hapus</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php
require_once 'templates/footer.php';
?>
