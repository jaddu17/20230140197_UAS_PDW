<?php
require_once '../config.php';
require_once 'templates/header_mahasiswa.php';

$id_mahasiswa = $_SESSION['id'];
$alert_message = null;

// Proses Pendaftaran
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['daftar'])) {
        $id_praktikum = intval($_POST['praktikum_id']);
        $stmt = mysqli_prepare($link, "SELECT id FROM peserta_praktikum WHERE id_mahasiswa = ? AND id_praktikum = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ii", $id_mahasiswa, $id_praktikum);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            if (mysqli_stmt_num_rows($stmt) > 0) {
                $alert_message = ['warning', 'Anda sudah terdaftar dalam praktikum ini.'];
            } else {
                $insert = mysqli_prepare($link, "INSERT INTO peserta_praktikum (id_mahasiswa, id_praktikum) VALUES (?, ?)");
                if ($insert) {
                    mysqli_stmt_bind_param($insert, "ii", $id_mahasiswa, $id_praktikum);
                    if (mysqli_stmt_execute($insert)) {
                        $alert_message = ['success', 'Berhasil mendaftar ke praktikum!'];
                    } else {
                        $alert_message = ['danger', 'Gagal mendaftar. Error: ' . mysqli_error($link)];
                    }
                    mysqli_stmt_close($insert);
                } else {
                    $alert_message = ['danger', 'Gagal menyiapkan query pendaftaran. Error: ' . mysqli_error($link)];
                }
            }
            mysqli_stmt_close($stmt);
        } else {
            $alert_message = ['danger', 'Gagal menyiapkan query cek pendaftaran. Error: ' . mysqli_error($link)];
        }
    }

    // Proses Pembatalan
    if (isset($_POST['batal'])) {
        $id_praktikum = intval($_POST['praktikum_id']);
        $delete = mysqli_prepare($link, "DELETE FROM peserta_praktikum WHERE id_mahasiswa = ? AND id_praktikum = ?");
        if ($delete) {
            mysqli_stmt_bind_param($delete, "ii", $id_mahasiswa, $id_praktikum);
            if (mysqli_stmt_execute($delete)) {
                $alert_message = ['success', 'Berhasil membatalkan pendaftaran.'];
            } else {
                $alert_message = ['danger', 'Gagal membatalkan pendaftaran. Error: ' . mysqli_error($link)];
            }
            mysqli_stmt_close($delete);
        } else {
            $alert_message = ['danger', 'Gagal menyiapkan query pembatalan. Error: ' . mysqli_error($link)];
        }
    }
}

// Ambil daftar praktikum
$praktikum_list = [];
$sql = "SELECT id, nama_praktikum, deskripsi FROM praktikum ORDER BY nama_praktikum ASC";
$result = mysqli_query($link, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $praktikum_list[] = $row;
    }
    mysqli_free_result($result);
} else {
    $alert_message = ['danger', "Query error: " . mysqli_error($link)];
}

// Ambil daftar yang sudah terdaftar
$praktikum_terdaftar = [];
$sql_cek = "SELECT id_praktikum FROM peserta_praktikum WHERE id_mahasiswa = ?";
if ($stmt = mysqli_prepare($link, $sql_cek)) {
    mysqli_stmt_bind_param($stmt, "i", $id_mahasiswa);
    mysqli_stmt_execute($stmt);
    $result_cek = mysqli_stmt_get_result($stmt);
    if ($result_cek) {
        while ($row = mysqli_fetch_assoc($result_cek)) {
            $praktikum_terdaftar[] = $row['id_praktikum'];
        }
    }
    mysqli_stmt_close($stmt);
} else {
    // Jika ada error di sini, tambahkan ke alert_message jika belum ada
    if (is_null($alert_message)) {
        $alert_message = ['danger', "Query error cek pendaftaran: " . mysqli_error($link)];
    }
}
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-6">Katalog Praktikum</h1>

    <?php if ($alert_message): ?>
        <div class="px-4 py-3 rounded relative mb-4 
            <?php
                if ($alert_message[0] === 'success') echo 'bg-green-100 border border-green-400 text-green-700';
                elseif ($alert_message[0] === 'warning') echo 'bg-yellow-100 border border-yellow-400 text-yellow-700';
                else echo 'bg-red-100 border border-red-400 text-red-700';
            ?>">
            <?php echo htmlspecialchars($alert_message[1]); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($praktikum_list)): ?>
        <p class="text-gray-600">Belum ada praktikum yang tersedia.</p>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($praktikum_list as $praktikum): ?>
                <div class="bg-white p-6 rounded-lg shadow-md flex flex-col"> <!-- Added flex flex-col -->
                    <h3 class="text-xl font-semibold mb-2"><?php echo htmlspecialchars($praktikum['nama_praktikum']); ?></h3>
                    <p class="text-gray-700 mb-4 flex-grow"><?php echo nl2br(htmlspecialchars($praktikum['deskripsi'])); ?></p> <!-- Added flex-grow -->
                    <form method="post" class="mt-auto"> <!-- Added mt-auto -->
                        <input type="hidden" name="praktikum_id" value="<?php echo $praktikum['id']; ?>">
                        <?php if (in_array($praktikum['id'], $praktikum_terdaftar)): ?>
                            <button type="submit" name="batal"
                                class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded w-full">
                                Batalkan Pendaftaran
                            </button>
                        <?php else: ?>
                            <button type="submit" name="daftar"
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded w-full">
                                Daftar Praktikum
                            </button>
                        <?php endif; ?>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'templates/footer_mahasiswa.php'; ?>
