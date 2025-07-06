<?php
require_once 'config.php';

// Check if the user is already logged in, if yes then redirect to dashboard
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    if ($_SESSION["role"] == "asisten") {
        header("location: asisten/dashboard.php");
    } else {
        header("location: mahasiswa/dashboard.php");
    }
    exit;
}

$nama = $email = $password = $confirm_password = $role = "";
$nama_err = $email_err = $password_err = $confirm_password_err = $role_err = "";

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){

    // Validate nama
    if(empty(trim($_POST["nama"]))){
        $nama_err = "Please enter your name.";
    } else {
        $nama = trim($_POST["nama"]);
    }

    // Validate email
    if(empty(trim($_POST["email"]))){
        $email_err = "Please enter an email.";
    } elseif(!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)){
        $email_err = "Invalid email format.";
    } else{
        // Prepare a select statement
        $sql = "SELECT id FROM users WHERE email = ?";

        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "s", $param_email);
            $param_email = trim($_POST["email"]);

            if(mysqli_stmt_execute($stmt)){
                mysqli_stmt_store_result($stmt);
                if(mysqli_stmt_num_rows($stmt) == 1){
                    $email_err = "This email is already registered.";
                } else{
                    $email = trim($_POST["email"]);
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }
            mysqli_stmt_close($stmt);
        }
    }

    // Validate password
    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter a password.";
    } elseif(strlen(trim($_POST["password"])) < 6){
        $password_err = "Password must have at least 6 characters.";
    } else{
        $password = trim($_POST["password"]);
    }

    // Validate confirm password
    if(empty(trim($_POST["confirm_password"]))){
        $confirm_password_err = "Please confirm password.";
    } else{
        $confirm_password = trim($_POST["confirm_password"]);
        if(empty($password_err) && ($password != $confirm_password)){
            $confirm_password_err = "Password did not match.";
        }
    }

    // Validate role
    if(empty(trim($_POST["role"])) || !in_array(trim($_POST["role"]), ['mahasiswa', 'asisten'])){
        $role_err = "Please select a valid role.";
    } else {
        $role = trim($_POST["role"]);
    }


    // Check input errors before inserting in database
    if(empty($nama_err) && empty($email_err) && empty($password_err) && empty($confirm_password_err) && empty($role_err)){

        // Prepare an insert statement
        // Changed 'mahasiswa' to $param_role to use the selected role
        $sql = "INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, ?)"; 

        if($stmt = mysqli_prepare($link, $sql)){
            mysqli_stmt_bind_param($stmt, "ssss", $param_nama, $param_email, $param_password, $param_role);

            $param_nama = $nama;
            $param_email = $email;
            $param_password = password_hash($password, PASSWORD_DEFAULT); // Creates a password hash
            $param_role = $role; // Assign the selected role

            if(mysqli_stmt_execute($stmt)){
                header("location: login.php");
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }
            mysqli_stmt_close($stmt);
        }
    }
    // Close connection (only if it was opened in this script and not needed further)
    // In a larger app, you might keep it open until the end of the request.
    mysqli_close($link);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register KELASKU</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="w-full max-w-md bg-white p-8 rounded-lg shadow-md">
        <h2 class="text-2xl font-bold text-center mb-6">Register SIMPRAK</h2>
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
                <label for="confirm_password" class="block text-gray-700 text-sm font-bold mb-2">Confirm Password:</label>
                <input type="password" name="confirm_password" id="confirm_password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline <?php echo (!empty($confirm_password_err)) ? 'border-red-500' : ''; ?>">
                <span class="text-red-500 text-xs italic"><?php echo $confirm_password_err; ?></span>
            </div>

            <!-- New: Role Selection -->
            <div class="mb-6">
                <label for="role" class="block text-gray-700 text-sm font-bold mb-2">Daftar Sebagai:</label>
                <select name="role" id="role" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?php echo (!empty($role_err)) ? 'border-red-500' : ''; ?>">
                    <option value="">Pilih Role</option>
                    <option value="mahasiswa" <?php echo ($role == 'mahasiswa') ? 'selected' : ''; ?>>Mahasiswa</option>
                    <option value="asisten" <?php echo ($role == 'asisten') ? 'selected' : ''; ?>>Asisten</option>
                </select>
                <span class="text-red-500 text-xs italic"><?php echo $role_err; ?></span>
            </div>

            <div class="flex items-center justify-between">
                <button type="submit" class="bg-blue-300 hover:bg-blue-500 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Register</button>
                <p class="text-sm">Already have an account? <a href="login.php" class="text-blue-500 hover:text-blue-800">Login here</a>.</p>
            </div>
        </form>
    </div>
</body>
</html>
