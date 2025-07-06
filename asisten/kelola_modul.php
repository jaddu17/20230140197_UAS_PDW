<?php
// HAPUS BARIS INI DI LINGKUNGAN PRODUKSI UNTUK MENYEMBUNYIKAN ERROR
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

require_once '../config.php';
require_once 'templates/header.php'; // Includes check_login_and_role("asisten")

// Inisialisasi variabel untuk form tambah modul
$judul = $deskripsi = "";
$judul_err = $file_materi_err = $praktikum_id_err = "";

// PROSES OPERASI CREATE (TAMBAH MODUL BARU)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_modul"])) {
    // Validasi judul modul
    if (empty(trim($_POST["judul"]))) {
        $judul_err = "Judul modul tidak boleh kosong.";
    } else {
        $judul = trim($_POST["judul"]);
    }

    // Validasi pemilihan praktikum
    $selected_praktikum_id = trim($_POST["id_praktikum"] ?? '');
    if (empty($selected_praktikum_id)) {
        $praktikum_id_err = "Silakan pilih praktikum untuk modul ini.";
    }

    $deskripsi = trim($_POST["deskripsi"]); // Deskripsi bisa kosong
    $file_materi = null;

    // Menangani unggah file materi (PDF/DOCX)
    if (isset($_FILES["file_materi"]) && $_FILES["file_materi"]["error"] == 0) {
        $allowed_types = ['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']; // Tipe file yang diizinkan
        $file_type = mime_content_type($_FILES["file_materi"]["tmp_name"]);

        if (in_array($file_type, $allowed_types)) {
            $file_extension = pathinfo($_FILES["file_materi"]["name"], PATHINFO_EXTENSION);
            $file_name = uniqid('modul_') . '.' . $file_extension; // Nama file unik
            $target_file = UPLOAD_DIR . $file_name;
            if (move_uploaded_file($_FILES["file_materi"]["tmp_name"], $target_file)) {
                $file_materi = $file_name;
            } else {
                $file_materi_err = "Gagal mengunggah file materi.";
            }
        } else {
            $file_materi_err = "Tipe file tidak diizinkan. Hanya PDF dan DOCX.";
        }
    } else if ($_FILES["file_materi"]["error"] != 4) { // Error 4 = UPLOAD_ERR_NO_FILE (tidak ada file diunggah)
        $file_materi_err = "Terjadi kesalahan saat mengunggah file. Kode error: " . $_FILES["file_materi"]["error"];
    }

    if (empty($judul_err) && empty($praktikum_id_err) && empty($file_materi_err)) {
        $sql = "INSERT INTO modul (id_praktikum, id_asisten, judul, deskripsi, file_materi) VALUES (?, ?, ?, ?, ?)";
        if ($stmt = mysqli_prepare($link, $sql)) {
            // PENTING: Pastikan jumlah 's' sesuai dengan jumlah kolom string yang di-insert
            // (id_praktikum, id_asisten, judul, deskripsi, file_materi) -> i, i, s, s, s = iisss
            mysqli_stmt_bind_param($stmt, "iisss", $param_praktikum_id, $param_asisten_id, $param_judul, $param_deskripsi, $param_file_materi);
            $param_praktikum_id = $selected_praktikum_id;
            $param_asisten_id = $_SESSION['id']; // Asisten yang sedang login
            $param_judul = $judul;
            $param_deskripsi = $deskripsi;
            $param_file_materi = $file_materi;
            if (mysqli_stmt_execute($stmt)) {
                header("location: kelola_modul.php"); // Redirect ke halaman ini lagi setelah sukses
                exit();
            } else {
                // Tambahkan pesan error MySQL untuk debugging lebih lanjut
                echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">Terjadi kesalahan saat menambahkan modul. Silakan coba lagi nanti. Error: ' . mysqli_error($link) . '</div>';
            }
            mysqli_stmt_close($stmt);
        } else {
            echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">Terjadi kesalahan saat menyiapkan statement SQL. Silakan coba lagi nanti. Error: ' . mysqli_error($link) . '</div>';
        }
    }
}

// PROSES OPERASI DELETE (HAPUS MODUL)
if (isset($_GET["delete_modul_id"]) && !empty(trim($_GET["delete_modul_id"]))) {
    $delete_modul_id = trim($_GET["delete_modul_id"]);
    $modul_praktikum_id = trim($_GET['modul_praktikum_id']); // Dapatkan praktikum_id dari modul yang akan dihapus

    // Ambil path file materi untuk dihapus dari server fisik
    $sql_select_file = "SELECT file_materi FROM modul WHERE id = ? AND id_praktikum = ? AND id_asisten = ?";
    if ($stmt_select = mysqli_prepare($link, $sql_select_file)) {
        mysqli_stmt_bind_param($stmt_select, "iii", $delete_modul_id, $modul_praktikum_id, $_SESSION['id']);
        if (mysqli_stmt_execute($stmt_select)) {
            mysqli_stmt_bind_result($stmt_select, $file_to_delete);
            if (mysqli_stmt_fetch($stmt_select)) {
                if ($file_to_delete && file_exists(UPLOAD_DIR . $file_to_delete)) {
                    unlink(UPLOAD_DIR . $file_to_delete); // Hapus file fisik
                }
            }
        } else {
            echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">Oops! Terjadi kesalahan saat mengambil info file untuk dihapus. Error: ' . mysqli_error($link) . '</div>';
        }
        mysqli_stmt_close($stmt_select);
    }

    // Hapus record modul dari database
    $sql = "DELETE FROM modul WHERE id = ? AND id_praktikum = ? AND id_asisten = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "iii", $param_id, $param_praktikum_id, $param_asisten_id);
        $param_id = $delete_modul_id;
        $param_praktikum_id = $modul_praktikum_id;
        $param_asisten_id = $_SESSION['id'];
        if (mysqli_stmt_execute($stmt)) {
            header("location: kelola_modul.php"); // Redirect ke halaman ini lagi setelah sukses
            exit();
        } else {
            echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">Terjadi kesalahan saat menghapus modul. Silakan coba lagi nanti. Error: ' . mysqli_error($link) . '</div>';
        }
        mysqli_stmt_close($stmt);
    } else {
        echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">Terjadi kesalahan saat menyiapkan statement SQL untuk delete. Silakan coba lagi nanti. Error: ' . mysqli_error($link) . '</div>';
    }
}

// MENGAMBIL DATA MODUL UNTUK DITAMPILKAN
// Query ini mengambil semua modul yang diunggah oleh asisten yang sedang login,
// dan juga nama praktikumnya.
$modul_list = [];
$sql = "SELECT m.id, m.judul, m.deskripsi, m.file_materi, m.id_praktikum, p.nama_praktikum
        FROM modul m
        JOIN praktikum p ON m.id_praktikum = p.id
        WHERE m.id_asisten = ?"; // Hanya menampilkan modul yang diunggah oleh asisten yang sedang login

$sql_types = "i";
$sql_params = [$_SESSION['id']];

$sql .= " ORDER BY p.nama_praktikum ASC, m.judul ASC";

if ($stmt = mysqli_prepare($link, $sql)) {
    // Menggunakan operator spread (...) untuk sql_params
    mysqli_stmt_bind_param($stmt, $sql_types, ...$sql_params);
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_bind_result($stmt, $id, $judul_modul, $desc, $file, $modul_praktikum_id, $nama_praktikum);
        while (mysqli_stmt_fetch($stmt)) {
            $modul_list[] = [
                'id' => $id,
                'judul' => $judul_modul,
                'deskripsi' => $desc,
                'file_materi' => $file,
                'id_praktikum' => $modul_praktikum_id,
                'nama_praktikum' => $nama_praktikum
            ];
        }
    } else {
        echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">Oops! Terjadi kesalahan saat mengambil data modul. Error: ' . mysqli_error($link) . '</div>';
    }
    mysqli_stmt_close($stmt);
} else {
    echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">Terjadi kesalahan saat menyiapkan statement SQL untuk mengambil data. Silakan coba lagi nanti. Error: ' . mysqli_error($link) . '</div>';
}

// Mengambil daftar praktikum untuk dropdown form tambah modul
$all_praktikum = [];
$sql_praktikum_dropdown = "SELECT id, nama_praktikum FROM praktikum ORDER BY nama_praktikum ASC";
if ($result_praktikum_dropdown = mysqli_query($link, $sql_praktikum_dropdown)) {
    while ($row = mysqli_fetch_assoc($result_praktikum_dropdown)) {
        $all_praktikum[] = $row;
    }
    mysqli_free_result($result_praktikum_dropdown);
} else {
    echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">Oops! Terjadi kesalahan saat mengambil daftar praktikum. Error: ' . mysqli_error($link) . '</div>';
}
?>

<h1 class="text-3xl font-bold mb-6">Kelola Modul Praktikum</h1>

<!-- Filter Section - DIHAPUS -->
<!-- <div class="bg-white p-6 rounded-lg shadow-md mb-8">
    <h2 class="text-xl font-semibold mb-4">Filter Modul</h2>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="get">
        <div class="mb-4">
            <label for="filter_praktikum" class="block text-gray-700 text-sm font-bold mb-2">Filter Berdasarkan Praktikum:</label>
            <select name="filter_praktikum" id="filter_praktikum" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                <option value="">Semua Praktikum</option>
                <?php foreach ($all_praktikum as $praktikum_option): ?>
                    <option value="<?php echo $praktikum_option['id']; ?>" <?php echo ($filter_praktikum_id == $praktikum_option['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($praktikum_option['nama_praktikum']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Terapkan Filter</button>
    </form>
</div> -->

<!-- Form Tambah Modul Baru -->
<div class="bg-white p-6 rounded-lg shadow-md mb-8">
    <h2 class="text-xl font-semibold mb-4">Tambah Modul Baru</h2>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
        <div class="mb-4">
            <label for="id_praktikum" class="block text-gray-700 text-sm font-bold mb-2">Pilih Praktikum:</label>
            <select name="id_praktikum" id="id_praktikum" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?php echo (!empty($praktikum_id_err)) ? 'border-red-500' : ''; ?>">
                <option value="">-- Pilih Praktikum --</option>
                <?php foreach ($all_praktikum as $praktikum_option): ?>
                    <option value="<?php echo $praktikum_option['id']; ?>" <?php echo (isset($_POST['id_praktikum']) && $_POST['id_praktikum'] == $praktikum_option['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($praktikum_option['nama_praktikum']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <span class="text-red-500 text-xs italic"><?php echo $praktikum_id_err; ?></span>
        </div>
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
            <input type="file" name="file_materi" id="file_materi" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?php echo (!empty($file_materi_err)) ? 'border-red-500' : ''; ?>">
            <span class="text-red-500 text-xs italic"><?php echo $file_materi_err; ?></span>
        </div>
        <button type="submit" name="add_modul" class="bg-pink-500 hover:bg-pink-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Tambah Modul</button>
    </form>
</div>

<!-- Daftar Modul yang Ada -->
<div class="bg-white p-6 rounded-lg shadow-md">
    <h2 class="text-xl font-semibold mb-4">Daftar Modul</h2>
    <?php if (empty($modul_list)): ?>
        <p class="text-gray-600">Belum ada modul yang diunggah oleh Anda.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Praktikum</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Judul Modul</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deskripsi</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Materi</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($modul_list as $modul): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($modul['nama_praktikum']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($modul['judul']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo nl2br(htmlspecialchars($modul['deskripsi'])); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if (!empty($modul['file_materi'])): ?>
                                    <a href="<?php echo BASE_URL . 'uploads/' . htmlspecialchars($modul['file_materi']); ?>" class="text-blue-500 hover:text-blue-700" target="_blank">Unduh Materi</a>
                                <?php else: ?>
                                    Tidak ada
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="edit_modul.php?id=<?php echo $modul['id']; ?>&praktikum_id=<?php echo $modul['id_praktikum']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-4">Edit</a>
                                <a href="kelola_modul.php?delete_modul_id=<?php echo $modul['id']; ?>&modul_praktikum_id=<?php echo $modul['id_praktikum']; ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Apakah Anda yakin ingin menghapus modul ini?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'templates/footer.php'; ?>
