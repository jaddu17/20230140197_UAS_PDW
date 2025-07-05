<?php
require_once '../config.php';
require_once 'templates/header.php'; // Includes check_login_and_role("asisten")

$modul_id = $praktikum_id = "";
$judul = $deskripsi = $current_file_materi = "";
$judul_err = $file_materi_err = "";

// Check if ID and praktikum_id are provided in URL
if (isset($_GET["id"]) && !empty(trim($_GET["id"])) && isset($_GET["praktikum_id"]) && !empty(trim($_GET["praktikum_id"]))) {
    $modul_id = trim($_GET["id"]);
    $praktikum_id = trim($_GET["praktikum_id"]);

    // Prepare a select statement
    $sql = "SELECT judul, deskripsi, file_materi FROM modul WHERE id = ? AND id_praktikum = ? AND id_asisten = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "iii", $param_modul_id, $param_praktikum_id, $param_asisten_id);
        $param_modul_id = $modul_id;
        $param_praktikum_id = $praktikum_id;
        $param_asisten_id = $_SESSION['id'];
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_bind_result($stmt, $judul, $deskripsi, $current_file_materi);
            if (!mysqli_stmt_fetch($stmt)) {
                echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">Modul tidak ditemukan atau Anda tidak memiliki akses.</div>';
                exit();
            }
        } else {
            echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">Oops! Terjadi kesalahan. Silakan coba lagi nanti.</div>';
            exit();
        }
        mysqli_stmt_close($stmt);
    }
} else {
    header("location: kelola_praktikum.php"); // Redirect if IDs are not provided
    exit();
}

// Process form submission for update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $modul_id = $_POST["modul_id"];
    $praktikum_id = $_POST["praktikum_id"];

    // Validate judul
    if (empty(trim($_POST["judul"]))) {
        $judul_err = "Judul modul tidak boleh kosong.";
    } else {
        $judul = trim($_POST["judul"]);
    }
    $deskripsi = trim($_POST["deskripsi"]);
    $file_materi = $_POST["current_file_materi"] ?? null; // Keep existing file by default

    // Handle new file upload for update
    if (isset($_FILES["file_materi"]) && $_FILES["file_materi"]["error"] == 0) {
        $allowed_types = ['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        $file_type = mime_content_type($_FILES["file_materi"]["tmp_name"]);

        if (in_array($file_type, $allowed_types)) {
            // Delete old file if exists
            if (!empty($file_materi) && file_exists(UPLOAD_DIR . $file_materi)) {
                unlink(UPLOAD_DIR . $file_materi);
            }
            $file_extension = pathinfo($_FILES["file_materi"]["name"], PATHINFO_EXTENSION);
            $file_name = uniqid('modul_') . '.' . $file_extension;
            $target_file = UPLOAD_DIR . $file_name;
            if (move_uploaded_file($_FILES["file_materi"]["tmp_name"], $target_file)) {
                $file_materi = $file_name;
            } else {
                $file_materi_err = "Gagal mengunggah file materi baru.";
            }
        } else {
            $file_materi_err = "Tipe file tidak diizinkan untuk materi baru. Hanya PDF dan DOCX.";
        }
    }

    if (empty($judul_err) && empty($file_materi_err)) {
        $sql = "UPDATE modul SET judul = ?, deskripsi = ?, file_materi = ? WHERE id = ? AND id_praktikum = ? AND id_asisten = ?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "sssiii", $param_judul, $param_deskripsi, $param_file_materi, $param_modul_id, $param_praktikum_id, $param_asisten_id);
            $param_judul = $judul;
            $param_deskripsi = $deskripsi;
            $param_file_materi = $file_materi;
            $param_modul_id = $modul_id;
            $param_praktikum_id = $praktikum_id;
            $param_asisten_id = $_SESSION['id'];
            if (mysqli_stmt_execute($stmt)) {
                header("location: kelola_modul.php?praktikum_id=" . $praktikum_id);
                exit();
            } else {
                echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">Terjadi kesalahan saat memperbarui modul. Silakan coba lagi nanti.</div>';
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<h1 class="text-3xl font-bold mb-6">Edit Modul</h1>

<div class="bg-white p-6 rounded-lg shadow-md mb-8">
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?id=" . $modul_id . "&praktikum_id=" . $praktikum_id; ?>" method="post" enctype="multipart/form-data">
        <input type="hidden" name="modul_id" value="<?php echo $modul_id; ?>">
        <input type="hidden" name="praktikum_id" value="<?php echo $praktikum_id; ?>">
        <input type="hidden" name="current_file_materi" value="<?php echo htmlspecialchars($current_file_materi); ?>">

        <div class="mb-4">
            <label for="judul" class="block text-gray-700 text-sm font-bold mb-2">Judul Modul:</label>
            <input type="text" name="judul" id="judul" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?php echo (!empty($judul_err)) ? 'border-red-500' : ''; ?>" value="<?php echo htmlspecialchars($judul); ?>">
            <span class="text-red-500 text-xs italic"><?php echo $judul_err; ?></span>
        </div>
        <div class="mb-4">
            <label for="deskripsi" class="block text-gray-700 text-sm font-bold mb-2">Deskripsi Modul (Opsional):</label>
            <textarea name="deskripsi" id="deskripsi" rows="3" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"><?php echo htmlspecialchars($deskripsi); ?></textarea>
        </div>
        <div class="mb-4">
            <label for="file_materi" class="block text-gray-700 text-sm font-bold mb-2">File Materi (PDF/DOCX):</label>
            <?php if (!empty($current_file_materi)): ?>
                <p class="text-gray-600 text-sm mb-2">File saat ini: <a href="<?php echo BASE_URL . 'uploads/' . htmlspecialchars($current_file_materi); ?>" target="_blank" class="text-blue-500 hover:underline"><?php echo htmlspecialchars($current_file_materi); ?></a></p>
                <p class="text-gray-600 text-sm mb-2">Unggah file baru untuk mengganti yang sudah ada.</p>
            <?php else: ?>
                <p class="text-gray-600 text-sm mb-2">Belum ada file materi.</p>
            <?php endif; ?>
            <input type="file" name="file_materi" id="file_materi" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?php echo (!empty($file_materi_err)) ? 'border-red-500' : ''; ?>">
            <span class="text-red-500 text-xs italic"><?php echo $file_materi_err; ?></span>
        </div>
        <div class="flex items-center justify-between">
            <button type="submit" name="update_modul" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Update Modul</button>
            <a href="kelola_modul.php?praktikum_id=<?php echo $praktikum_id; ?>" class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800">Batal</a>
        </div>
    </form>
</div>

<?php require_once 'templates/footer.php'; ?>