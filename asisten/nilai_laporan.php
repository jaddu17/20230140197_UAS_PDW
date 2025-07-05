<?php
require_once '../config.php';
require_once 'templates/header.php'; // Includes check_login_and_role("asisten")

if (!isset($_GET['laporan_id']) || empty(trim($_GET['laporan_id']))) {
    header("location: laporan_masuk.php");
    exit;
}

$laporan_id = trim($_GET['laporan_id']);
$nilai = $komentar = "";
$nilai_err = "";

$laporan_detail = [];

// Fetch laporan details
$sql_laporan = "SELECT
                    l.id AS laporan_id,
                    l.file_laporan,
                    l.tanggal_upload,
                    m.id AS modul_id,
                    m.judul AS nama_modul,
                    p.nama_praktikum,
                    u.id AS mahasiswa_id,
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
                WHERE
                    l.id = ?";

if ($stmt_laporan = mysqli_prepare($link, $sql_laporan)) {
    mysqli_stmt_bind_param($stmt_laporan, "i", $laporan_id);
    if (mysqli_stmt_execute($stmt_laporan)) {
        $result_laporan = mysqli_stmt_get_result($stmt_laporan);
        if (mysqli_num_rows($result_laporan) == 1) {
            $laporan_detail = mysqli_fetch_assoc($result_laporan);
            $nilai = $laporan_detail['nilai'];
            $komentar = $laporan_detail['komentar'];
        } else {
            echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">Laporan tidak ditemukan.</div>';
            exit;
        }
        mysqli_free_result($result_laporan);
    } else {
        echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">Oops! Terjadi kesalahan saat mengambil detail laporan.</div>';
        exit;
    }
    mysqli_stmt_close($stmt_laporan);
}

// Process form submission for giving/updating nilai
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $input_nilai = trim($_POST["nilai"]);
    $input_komentar = trim($_POST["komentar"]);
    $mahasiswa_id = $_POST["mahasiswa_id"];
    $modul_id = $_POST["modul_id"];

    // Validate nilai (can be empty, but if not empty, must be numeric)
    if (!empty($input_nilai) && !is_numeric($input_nilai)) {
        $nilai_err = "Nilai harus berupa angka.";
    } else {
        $nilai = $input_nilai;
    }
    $komentar = $input_komentar;

    if (empty($nilai_err)) {
        // Check if nilai already exists for this user and modul
        $sql_check_nilai = "SELECT id FROM nilai WHERE id_user = ? AND id_modul = ?";
        if ($stmt_check_nilai = mysqli_prepare($link, $sql_check_nilai)) {
            mysqli_stmt_bind_param($stmt_check_nilai, "ii", $mahasiswa_id, $modul_id);
            mysqli_stmt_execute($stmt_check_nilai);
            mysqli_stmt_store_result($stmt_check_nilai);

            if (mysqli_stmt_num_rows($stmt_check_nilai) > 0) {
                // Update existing nilai
                $sql_upsert = "UPDATE nilai SET nilai = ?, komentar = ?, tanggal_nilai = CURRENT_TIMESTAMP WHERE id_user = ? AND id_modul = ?";
                if ($stmt_upsert = mysqli_prepare($link, $sql_upsert)) {
                    mysqli_stmt_bind_param($stmt_upsert, "ssii", $param_nilai, $param_komentar, $mahasiswa_id, $modul_id);
                    $param_nilai = $nilai;
                    $param_komentar = $komentar;
                    if (mysqli_stmt_execute($stmt_upsert)) {
                        echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">Nilai berhasil diperbarui!</div>';
                        // Refresh data after update
                        header("location: nilai_laporan.php?laporan_id=" . $laporan_id);
                        exit();
                    } else {
                        echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">Terjadi kesalahan saat memperbarui nilai.</div>';
                    }
                    mysqli_stmt_close($stmt_upsert);
                }
            } else {
                // Insert new nilai
                $sql_upsert = "INSERT INTO nilai (id_user, id_modul, nilai, komentar) VALUES (?, ?, ?, ?)";
                if ($stmt_upsert = mysqli_prepare($link, $sql_upsert)) {
                    mysqli_stmt_bind_param($stmt_upsert, "iiss", $mahasiswa_id, $modul_id, $param_nilai, $param_komentar);
                    $param_nilai = $nilai;
                    $param_komentar = $komentar;
                    if (mysqli_stmt_execute($stmt_upsert)) {
                        echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">Nilai berhasil ditambahkan!</div>';
                        // Refresh data after insert
                        header("location: nilai_laporan.php?laporan_id=" . $laporan_id);
                        exit();
                    } else {
                        echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">Terjadi kesalahan saat menambahkan nilai.</div>';
                    }
                    mysqli_stmt_close($stmt_upsert);
                }
            }
            mysqli_stmt_close($stmt_check_nilai);
        }
    }
}
?>

<h1 class="text-3xl font-bold mb-6">Beri/Edit Nilai Laporan</h1>

<div class="bg-white p-6 rounded-lg shadow-md mb-8">
    <h2 class="text-xl font-semibold mb-4">Detail Laporan</h2>
    <div class="mb-4">
        <p><strong class="font-semibold">Praktikum:</strong> <?php echo htmlspecialchars($laporan_detail['nama_praktikum']); ?></p>
        <p><strong class="font-semibold">Modul:</strong> <?php echo htmlspecialchars($laporan_detail['nama_modul']); ?></p>
        <p><strong class="font-semibold">Mahasiswa:</strong> <?php echo htmlspecialchars($laporan_detail['nama_mahasiswa']); ?> (<?php echo htmlspecialchars($laporan_detail['email_mahasiswa']); ?>)</p>
        <p><strong class="font-semibold">Tanggal Upload:</strong> <?php echo date('d M Y H:i', strtotime($laporan_detail['tanggal_upload'])); ?></p>
        <p class="mt-2">
            <a href="<?php echo BASE_URL . 'uploads/' . htmlspecialchars($laporan_detail['file_laporan']); ?>" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded inline-flex items-center" target="_blank">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                Unduh Laporan
            </a>
        </p>
    </div>

    <h2 class="text-xl font-semibold mb-4 mt-6">Form Penilaian</h2>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?laporan_id=" . $laporan_id; ?>" method="post">
        <input type="hidden" name="mahasiswa_id" value="<?php echo htmlspecialchars($laporan_detail['mahasiswa_id']); ?>">
        <input type="hidden" name="modul_id" value="<?php echo htmlspecialchars($laporan_detail['modul_id']); ?>">

        <div class="mb-4">
            <label for="nilai" class="block text-gray-700 text-sm font-bold mb-2">Nilai (Angka):</label>
            <input type="text" name="nilai" id="nilai" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?php echo (!empty($nilai_err)) ? 'border-red-500' : ''; ?>" value="<?php echo htmlspecialchars($nilai); ?>">
            <span class="text-red-500 text-xs italic"><?php echo $nilai_err; ?></span>
        </div>
        <div class="mb-4">
            <label for="komentar" class="block text-gray-700 text-sm font-bold mb-2">Feedback (Teks):</label>
            <textarea name="komentar" id="komentar" rows="5" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"><?php echo htmlspecialchars($komentar); ?></textarea>
        </div>
        <div class="flex items-center justify-between">
            <button type="submit" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Simpan Nilai</button>
            <a href="laporan_masuk.php" class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800">Kembali ke Laporan Masuk</a>
        </div>
    </form>
</div>

<?php require_once 'templates/footer.php'; ?>