<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['daftar_praktikum'])) {
    $id_mahasiswa = $_SESSION['id'];
    $id_praktikum = intval($_POST['praktikum_id']);

    // Cek apakah sudah terdaftar
    $cek = "SELECT id FROM peserta_praktikum WHERE id_mahasiswa = ? AND id_praktikum = ?";
    if ($stmt = mysqli_prepare($link, $cek)) {
        mysqli_stmt_bind_param($stmt, "ii", $id_mahasiswa, $id_praktikum);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {
            header("Location: katalog_praktikum.php?status=sudah_terdaftar");
            exit;
        } else {
            $sql = "INSERT INTO peserta_praktikum (id_mahasiswa, id_praktikum) VALUES (?, ?)";
            if ($stmt2 = mysqli_prepare($link, $sql)) {
                mysqli_stmt_bind_param($stmt2, "ii", $id_mahasiswa, $id_praktikum);
                if (mysqli_stmt_execute($stmt2)) {
                    header("Location: katalog_praktikum.php?status=berhasil");
                    exit;
                } else {
                    header("Location: katalog_praktikum.php?status=gagal");
                    exit;
                }
            }
        }
    }
}
header("Location: katalog_praktikum.php");
exit;
