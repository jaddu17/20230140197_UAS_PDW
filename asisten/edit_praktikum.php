<?php
// Aktifkan output buffering di awal skrip
ob_start();

// TAMPILKAN SEMUA ERROR (HANYA UNTUK DEBUGGING - HAPUS DI PRODUKSI!)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config.php';
require_once 'templates/header.php'; // Includes check_login_and_role("asisten")

$praktikum_id = $nama_praktikum = $deskripsi = "";
$nama_praktikum_err = $deskripsi_err = "";
$global_error_message = ""; // Variabel untuk menyimpan pesan error global

// Check if ID is provided in URL
if (isset($_GET["id"]) && !empty(trim($_GET["id"]))) {
    $praktikum_id = trim($_GET["id"]);

    // Prepare a select statement
    $sql = "SELECT nama_praktikum, deskripsi FROM praktikum WHERE id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $param_id);
        $param_id = $praktikum_id;
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_bind_result($stmt, $nama_praktikum, $deskripsi);
            if (!mysqli_stmt_fetch($stmt)) {
                $global_error_message = 'Praktikum tidak ditemukan.';
                // Jika praktikum tidak ditemukan, redirect atau tampilkan pesan dan keluar
                // Untuk kasus ini, kita akan redirect
                header("location: kelola_praktikum.php");
                exit();
            }
        } else {
            $global_error_message = 'Oops! Terjadi kesalahan saat mengambil data praktikum. Silakan coba lagi nanti. Error: ' . mysqli_error($link);
            // Jika ada error saat eksekusi query, kita juga bisa redirect atau keluar
            header("location: kelola_praktikum.php");
            exit();
        }
        mysqli_stmt_close($stmt);
    } else {
        $global_error_message = 'Terjadi kesalahan saat menyiapkan statement SQL untuk mengambil data. Silakan coba lagi nanti. Error: ' . mysqli_error($link);
        header("location: kelola_praktikum.php");
        exit();
    }
} else {
    header("location: kelola_praktikum.php");
    exit();
}

// Process form submission for update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $praktikum_id = $_POST["praktikum_id"];

    // Validate nama_praktikum
    if (empty(trim($_POST["nama_praktikum"]))) {
        $nama_praktikum_err = "Nama praktikum tidak boleh kosong.";
    } else {
        $nama_praktikum = trim($_POST["nama_praktikum"]);
    }

    // Validate deskripsi
    $deskripsi = trim($_POST["deskripsi"]);

    if (empty($nama_praktikum_err)) {
        $sql = "UPDATE praktikum SET nama_praktikum = ?, deskripsi = ? WHERE id = ?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssi", $param_nama_praktikum, $param_deskripsi, $param_id);
            $param_nama_praktikum = $nama_praktikum;
            $param_deskripsi = $deskripsi;
            $param_id = $praktikum_id;
            if (mysqli_stmt_execute($stmt)) {
                // Redirect setelah berhasil update
                header("location: kelola_praktikum.php");
                exit();
            } else {
                $global_error_message = 'Terjadi kesalahan saat memperbarui praktikum. Silakan coba lagi nanti. Error: ' . mysqli_error($link);
            }
            mysqli_stmt_close($stmt);
        } else {
            $global_error_message = 'Terjadi kesalahan saat menyiapkan statement SQL untuk update. Silakan coba lagi nanti. Error: ' . mysqli_error($link);
        }
    }
}
?>

<h1 class="text-3xl font-bold mb-6">Edit Praktikum</h1>

<?php 
// Tampilkan pesan error global jika ada
if (!empty($global_error_message)) {
    echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">' . htmlspecialchars($global_error_message) . '</div>';
}
?>

<div class="bg-white p-6 rounded-lg shadow-md mb-8">
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?id=" . $praktikum_id; ?>" method="post">
        <input type="hidden" name="praktikum_id" value="<?php echo $praktikum_id; ?>">
        <div class="mb-4">
            <label for="nama_praktikum" class="block text-gray-700 text-sm font-bold mb-2">Nama Praktikum:</label>
            <input type="text" name="nama_praktikum" id="nama_praktikum" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?php echo (!empty($nama_praktikum_err)) ? 'border-red-500' : ''; ?>" value="<?php echo htmlspecialchars($nama_praktikum); ?>">
            <span class="text-red-500 text-xs italic"><?php echo $nama_praktikum_err; ?></span>
        </div>
        <div class="mb-4">
            <label for="deskripsi" class="block text-gray-700 text-sm font-bold mb-2">Deskripsi:</label>
            <textarea name="deskripsi" id="deskripsi" rows="4" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"><?php echo htmlspecialchars($deskripsi); ?></textarea>
        </div>
        <div class="flex items-center justify-between">
            <button type="submit" name="update_praktikum" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Update Praktikum</button>
            <a href="kelola_praktikum.php" class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800">Batal</a>
        </div>
    </form>
</div>

<?php 
require_once 'templates/footer.php'; 
// Akhiri output buffering dan kirimkan semua output
ob_end_flush();
?>
