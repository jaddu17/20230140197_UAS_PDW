<?php
// Database credentials
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root'); // Ganti dengan username database Anda
define('DB_PASSWORD', '');     // Ganti dengan password database Anda
define('DB_NAME', 'simpra_db'); // Ganti dengan nama database Anda

// Attempt to connect to MySQL database
$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if($link === false){
    die("ERROR: Could not connect. " . mysqli_connect_error());
}

// Start session
session_start();

// Base URL for easier navigation (optional but recommended)
// Sesuaikan dengan path proyek Anda. Contoh: jika di htdocs/SIMPRAK2/, maka 'http://localhost/SIMPRAK2/'
define('BASE_URL', 'http://localhost/SIMPRAK2/');

// Path for uploaded files (materi and laporan)
define('UPLOAD_DIR', __DIR__ . '/uploads/');
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}

// Function to check if user is logged in and has a specific role
function check_login_and_role($required_role) {
    if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
        header("location: " . BASE_URL . "login.php");
        exit;
    }
    if ($_SESSION["role"] !== $required_role) {
        // Redirect to their own dashboard or an access denied page
        if ($_SESSION["role"] == "asisten") {
            header("location: " . BASE_URL . "asisten/dashboard.php");
        } else {
            header("location: " . BASE_URL . "mahasiswa/dashboard.php");
        }
        exit;
    }
}
?>