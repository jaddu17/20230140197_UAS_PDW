<?php
require_once '../config.php';
require_once 'templates/header.php'; // Includes check_login_and_role("asisten")

$nama = $email = $password = $confirm_password = $role = "";
$nama_err = $email_err = $password_err = $confirm_password_err = $role_err = "";

// Process CREATE and UPDATE
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["add_user"])) {
        // Validate nama
        if (empty(trim($_POST["nama"]))) {
            $nama_err = "Nama tidak boleh kosong.";
        } else {
            $nama = trim($_POST["nama"]);
        }

        // Validate email
        if (empty(trim($_POST["email"]))) {
            $email_err = "Email tidak boleh kosong.";
        } elseif (!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)) {
            $email_err = "Format email tidak valid.";
        } else {
            $sql_check_email = "SELECT id FROM users WHERE email = ?";
            if ($stmt_check = mysqli_prepare($link, $sql_check_email)) {
                mysqli_stmt_bind_param($stmt_check, "s", $param_email);
                $param_email = trim($_POST["email"]);
                if (mysqli_stmt_execute($stmt_check)) {
                    mysqli_stmt_store_result($stmt_check);
                    if (mysqli_stmt_num_rows($stmt_check) == 1) {
                        $email_err = "Email ini sudah terdaftar.";
                    } else {
                        $email = trim($_POST["email"]);
                    }
                } else {
                    echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">Oops! Terjadi kesalahan saat memeriksa email. Silakan coba lagi nanti.</div>';
                }
                mysqli_stmt_close($stmt_check);
            }
        }

        // Validate password
        if (empty(trim($_POST["password"]))) {
            $password_err = "Password tidak boleh kosong.";
        } elseif (strlen(trim($_POST["password"])) < 6) {
            $password_err = "Password minimal 6 karakter.";
        } else {
            $password = trim($_POST["password"]);
        }

        // Validate confirm password
        if (empty(trim($_POST["confirm_password"]))) {
            $confirm_password_err = "Konfirmasi password tidak boleh kosong.";
        } else {
            $confirm_password = trim($_POST["confirm_password"]);
            if (empty($password_err) && ($password != $confirm_password)) {
                $confirm_password_err = "Password tidak cocok.";
            }
        }

        // Validate role
        if (empty(trim($_POST["role"]))) {
            $role_err = "Role tidak boleh kosong.";
        } else {
            $role = trim($_POST["role"]);
        }

        if (empty($nama_err) && empty($email_err) && empty($password_err) && empty($confirm_password_err) && empty($role_err)) {
            $sql = "INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, ?)";
            if ($stmt = mysqli_prepare($link, $sql)) {
                mysqli_stmt_bind_param($stmt, "ssss", $param_nama, $param_email, $param_password, $param_role);
                $param_nama = $nama;
                $param_email = $email;
                $param_password = password_hash($password, PASSWORD_DEFAULT);
                $param_role = $role;
                if (mysqli_stmt_execute($stmt)) {
                    header("location: kelola_akun.php");
                    exit();
                } else {
                    echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">Terjadi kesalahan saat menambahkan akun. Silakan coba lagi nanti.</div>';
                }
                mysqli_stmt_close($stmt);
            }
        }
    } elseif (isset($_POST["update_user"])) {
        $user_id = $_POST["user_id"];
        // Validate nama
        if (empty(trim($_POST["nama"]))) {
            $nama_err = "Nama tidak boleh kosong.";
        } else {
            $nama = trim($_POST["nama"]);
        }

        // Validate email (check uniqueness, but allow current email)
        if (empty(trim($_POST["email"]))) {
            $email_err = "Email tidak boleh kosong.";
        } elseif (!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)) {
            $email_err = "Format email tidak valid.";
        } else {
            $sql_check_email = "SELECT id FROM users WHERE email = ? AND id != ?";
            if ($stmt_check = mysqli_prepare($link, $sql_check_email)) {
                mysqli_stmt_bind_param($stmt_check, "si", $param_email, $param_id);
                $param_email = trim($_POST["email"]);
                $param_id = $user_id;
                if (mysqli_stmt_execute($stmt_check)) {
                    mysqli_stmt_store_result($stmt_check);
                    if (mysqli_stmt_num_rows($stmt_check) == 1) {
                        $email_err = "Email ini sudah terdaftar untuk pengguna lain.";
                    } else {
                        $email = trim($_POST["email"]);
                    }
                } else {
                    echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">Oops! Terjadi kesalahan saat memeriksa email. Silakan coba lagi nanti.</div>';
                }
                mysqli_stmt_close($stmt_check);
            }
        }

        // Validate role
        if (empty(trim($_POST["role"]))) {
            $role_err = "Role tidak boleh kosong.";
        } else {
            $role = trim($_POST["role"]);
        }

        if (empty($nama_err) && empty($email_err) && empty($role_err)) {
            $sql = "UPDATE users SET nama = ?, email = ?, role = ? WHERE id = ?";
            if ($stmt = mysqli_prepare($link, $sql)) {
                mysqli_stmt_bind_param($stmt, "sssi", $param_nama, $param_email, $param_role, $param_id);
                $param_nama = $nama;
                $param_email = $email;
                $param_role = $role;
                $param_id = $user_id;
                if (mysqli_stmt_execute($stmt)) {
                    header("location: kelola_akun.php");
                    exit();
                } else {
                    echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">Terjadi kesalahan saat memperbarui akun. Silakan coba lagi nanti.</div>';
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
}

// Process DELETE
if (isset($_GET["delete_id"]) && !empty(trim($_GET["delete_id"]))) {
    $delete_id = trim($_GET["delete_id"]);
    // Prevent deleting own account
    if ($delete_id == $_SESSION['id']) {
        echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">Anda tidak bisa menghapus akun Anda sendiri.</div>';
    } else {
        $sql = "DELETE FROM users WHERE id = ?";
        if ($stmt = mysqli_prepare($link, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $param_id);
            $param_id = $delete_id;
            if (mysqli_stmt_execute($stmt)) {
                header("location: kelola_akun.php");
                exit();
            } else {
                echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">Terjadi kesalahan saat menghapus akun. Silakan coba lagi nanti.</div>';
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Get user data for display
$user_list = [];
$sql = "SELECT id, nama, email, role FROM users ORDER BY nama ASC";
if ($result = mysqli_query($link, $sql)) {
    while ($row = mysqli_fetch_assoc($result)) {
        $user_list[] = $row;
    }
    mysqli_free_result($result);
} else {
    echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">Oops! Terjadi kesalahan saat mengambil data pengguna.</div>';
}
?>

<h1 class="text-3xl font-bold mb-6">Kelola Akun Pengguna</h1>

<!-- Form Tambah Akun -->
<div class="bg-white p-6 rounded-lg shadow-md mb-8">
    <h2 class="text-xl font-semibold mb-4">Tambah Akun Baru</h2>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <div class="mb-4">
            <label for="nama" class="block text-gray-700 text-sm font-bold mb-2">Nama Lengkap:</label>
            <input type="text" name="nama" id="nama" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?php echo (!empty($nama_err)) ? 'border-red-500' : ''; ?>" value="<?php echo $nama; ?>">
            <span class="text-red-500 text-xs italic"><?php echo $nama_err; ?></span>
        </div>
        <div class="mb-4">
            <label for="email" class="block text-gray-700 text-sm font-bold mb-2">Email:</label>
            <input type="email" name="email" id="email" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?php echo (!empty($email_err)) ? 'border-red-500' : ''; ?>" value="<?php echo $email; ?>">
            <span class="text-red-500 text-xs italic"><?php echo $email_err; ?></span>
        </div>
        <div class="mb-4">
            <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Password:</label>
            <input type="password" name="password" id="password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline <?php echo (!empty($password_err)) ? 'border-red-500' : ''; ?>">
            <span class="text-red-500 text-xs italic"><?php echo $password_err; ?></span>
        </div>
        <div class="mb-6">
            <label for="confirm_password" class="block text-gray-700 text-sm font-bold mb-2">Konfirmasi Password:</label>
            <input type="password" name="confirm_password" id="confirm_password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline <?php echo (!empty($confirm_password_err)) ? 'border-red-500' : ''; ?>">
            <span class="text-red-500 text-xs italic"><?php echo $confirm_password_err; ?></span>
        </div>
        <div class="mb-4">
            <label for="role" class="block text-gray-700 text-sm font-bold mb-2">Role:</label>
            <select name="role" id="role" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?php echo (!empty($role_err)) ? 'border-red-500' : ''; ?>">
                <option value="">Pilih Role</option>
                <option value="mahasiswa" <?php echo ($role == 'mahasiswa') ? 'selected' : ''; ?>>Mahasiswa</option>
                <option value="asisten" <?php echo ($role == 'asisten') ? 'selected' : ''; ?>>Asisten</option>
            </select>
            <span class="text-red-500 text-xs italic"><?php echo $role_err; ?></span>
        </div>
        <button type="submit" name="add_user" class="bg-pink-500 hover:bg-pink-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Tambah Akun</button>
    </form>
</div>

<!-- Daftar Akun yang Ada -->
<div class="bg-white p-6 rounded-lg shadow-md">
    <h2 class="text-xl font-semibold mb-4">Daftar Akun Pengguna</h2>
    <?php if (empty($user_list)): ?>
        <p class="text-gray-600">Belum ada akun pengguna.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($user_list as $user): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($user['nama']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($user['email']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars(ucfirst($user['role'])); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="edit_akun.php?id=<?php echo $user['id']; ?>" class="text-indigo-600 hover:text-indigo-900 mr-4">Edit</a>
                                <?php if ($user['id'] != $_SESSION['id']): // Prevent deleting own account ?>
                                    <a href="kelola_akun.php?delete_id=<?php echo $user['id']; ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Apakah Anda yakin ingin menghapus akun ini?');">Delete</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'templates/footer.php'; ?>
