<?php
require_once '../config.php';
require_once 'templates/header.php'; // Includes check_login_and_role("asisten")

$nama_praktikum = $deskripsi = "";
$nama_praktikum_err = $deskripsi_err = "";

// Process CREATE and UPDATE
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["add_praktikum"])) {
        // Validate nama_praktikum
        if (empty(trim($_POST["nama_praktikum"]))) {
            $nama_praktikum_err = "Nama praktikum tidak boleh kosong.";
        } else {
            $nama_praktikum = trim($_POST["nama_praktikum"]);
        }

        // Validate deskripsi
        $deskripsi = trim($_POST["deskripsi"]); // Deskripsi bisa kosong

        if (empty($nama_praktikum_err)) {
            $sql = "INSERT INTO praktikum (nama_praktikum, deskripsi) VALUES (?, ?)";
            if ($stmt = mysqli_prepare($link, $sql)) {
                mysqli_stmt_bind_param($stmt, "ss", $param_nama_praktikum, $param_deskripsi);
                $param_nama_praktikum = $nama_praktikum;
                $param_deskripsi = $deskripsi;
                if (mysqli_stmt_execute($stmt)) {
                    header("location: kelola_praktikum.php");
                    exit();
                } else {
                    echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">Terjadi kesalahan saat menambahkan praktikum. Silakan coba lagi nanti.</div>';
                }
                mysqli_stmt_close($stmt);
            }
        }
    } elseif (isset($_POST["update_praktikum"])) {
        $praktikum_id = $_POST["praktikum_id"];
        if (empty(trim($_POST["nama_praktikum"]))) {
            $nama_praktikum_err = "Nama praktikum tidak boleh kosong.";
        } else {
            $nama_praktikum = trim($_POST["nama_praktikum"]);
        }
        $deskripsi = trim($_POST["deskripsi"]);

        if (empty($nama_praktikum_err)) {
            $sql = "UPDATE praktikum SET nama_praktikum = ?, deskripsi = ? WHERE id = ?";
            if ($stmt = mysqli_prepare($link, $sql)) {
                mysqli_stmt_bind_param($stmt, "ssi", $param_nama_praktikum, $param_deskripsi, $param_praktikum_id);
                $param_nama_praktikum = $nama_praktikum;
                $param_deskripsi = $deskripsi;
                $param_praktikum_id = $praktikum_id;
                if (mysqli_stmt_execute($stmt)) {
                    header("location: kelola_praktikum.php");
                    exit();
                } else {
                    echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">Terjadi kesalahan saat memperbarui praktikum. Silakan coba lagi nanti.</div>';
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
}

// Process DELETE
if (isset($_GET["delete_id"]) && !empty(trim($_GET["delete_id"]))) {
    $delete_id = trim($_GET["delete_id"]);
    $sql = "DELETE FROM praktikum WHERE id = ?";
    if ($stmt = mysqli_prepare($link, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $param_id);
        $param_id = $delete_id;
        if (mysqli_stmt_execute($stmt)) {
            header("location: kelola_praktikum.php");
            exit();
        } else {
            echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">Terjadi kesalahan saat menghapus praktikum. Silakan coba lagi nanti.</div>';
        }
        mysqli_stmt_close($stmt);
    }
}

// Get praktikum data for display
$praktikum_list = [];
$sql = "SELECT id, nama_praktikum, deskripsi FROM praktikum ORDER BY nama_praktikum ASC";
if ($result = mysqli_query($link, $sql)) {
    while ($row = mysqli_fetch_assoc($result)) {
        $praktikum_list[] = $row;
    }
    mysqli_free_result($result);
} else {
    echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">Oops! Terjadi kesalahan saat mengambil data praktikum.</div>';
}
?>

<h1 class="text-3xl font-bold mb-6">Kelola Mata Praktikum</h1>

<!-- Form Tambah Praktikum -->
<div class="bg-white p-6 rounded-lg shadow-md mb-8">
    <h2 class="text-xl font-semibold mb-4">Tambah Praktikum Baru</h2>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <div class="mb-4">
            <label for="nama_praktikum" class="block text-gray-700 text-sm font-bold mb-2">Nama Praktikum:</label>
            <input type="text" name="nama_praktikum" id="nama_praktikum" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?php echo (!empty($nama_praktikum_err)) ? 'border-red-500' : ''; ?>" value="<?php echo $nama_praktikum; ?>">
            <span class="text-red-500 text-xs italic"><?php echo $nama_praktikum_err; ?></span>
        </div>
        <div class="mb-4">
            <label for="deskripsi" class="block text-gray-700 text-sm font-bold mb-2">Deskripsi:</label>
            <textarea name="deskripsi" id="deskripsi" rows="4" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"><?php echo $deskripsi; ?></textarea>
        </div>
        <button type="submit" name="add_praktikum" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Tambah Praktikum</button>
    </form>
</div>

<!-- Daftar Praktikum yang Ada -->
<div class="bg-white p-6 rounded-lg shadow-md">
    <h2 class="text-xl font-semibold mb-4">Daftar Praktikum</h2>
    <?php if (empty($praktikum_list)): ?>
        <p class="text-gray-600">Belum ada praktikum yang ditambahkan.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Praktikum</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deskripsi</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($praktikum_list as $praktikum): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($praktikum['nama_praktikum']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo nl2br(htmlspecialchars($praktikum['deskripsi'])); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="edit_praktikum.php?id=<?php echo $praktikum['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-4">Edit</a>
                                <a href="kelola_praktikum.php?delete_id=<?php echo $praktikum['id']; ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Apakah Anda yakin ingin menghapus praktikum ini? Modul dan pendaftaran terkait juga akan dihapus.');">Delete</a>
                                <a href="kelola_modul.php?praktikum_id=<?php echo $praktikum['id']; ?>" class="text-blue-600 hover:text-blue-900 ml-4">Kelola Modul</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'templates/footer.php'; ?>