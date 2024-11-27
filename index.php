<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Koneksi ke PostgreSQL
$conn = pg_connect("host=localhost dbname=<nama-database> user=<user-database> password=<password-database>");
if (!$conn) {
    die("Koneksi gagal: " . pg_last_error());
}

// Proses login ketika form dikirim
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['email'];
    $password = $_POST['password'];

    // Query untuk memeriksa user
    $query = "SELECT * FROM list_admin WHERE user_admin = $1 AND password_admin = $2";
    $result = pg_query_params($conn, $query, array($username, $password));

    if (!$result) {
        die("Query gagal: " . pg_last_error());
    }

    // Jika login berhasil, set sesi dan redirect
    if (pg_num_rows($result) > 0) {
        $_SESSION['loggedin'] = true;
        header("Location: tabel.php");
        exit();
    } else {
        $error = "Username atau password salah!";
    }
}

pg_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="../public/css/output.css" rel="stylesheet">
</head>
<body class="justify-center">
    <section class="bg-gray-900">
        <div class="flex flex-col items-center justify-center px-6 py-8 mx-auto md:h-screen lg:py-0">
            <a href="#" class="flex items-center mb-6 text-2xl font-semibold text-white">
                VPN ELITE GLOBAL
            </a>
            <div class="w-full rounded-lg shadow border md:mt-0 sm:max-w-md xl:p-0 bg-gray-800 border-gray-700">
                <div class="p-6 space-y-4 md:space-y-6 sm:p-8">
                    <h1 class="text-xl font-bold leading-tight tracking-tight md:text-2xl text-white">
                        Hello Admin VPN!!!
                    </h1>
                    <?php if (isset($error)): ?>
                        <p class="text-red-500"><?php echo $error; ?></p>
                    <?php endif; ?>
                    <form class="space-y-4 md:space-y-6" action="index.php" method="POST">
                        <div>
                            <label for="email" class="block mb-2 text-sm font-medium text-white">User Name</label>
                            <input type="text" name="email" id="email" class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 bg-gray-700 border-gray-600 placeholder-gray-400 text-white focus:ring-blue-500 focus:border-blue-500" placeholder="your user name" required>
                        </div>
                        <div>
                            <label for="password" class="block mb-2 text-sm font-medium text-white">Password</label>
                            <input type="password" name="password" id="password" placeholder="••••••••" class="bg-gray-50 border border-gray-300 text-gray-900 rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 bg-gray-700 border-gray-600 placeholder-gray-400 text-white focus:ring-blue-500 focus:border-blue-500" required>
                        </div>
                        <button type="submit" class="w-full text-white bg-primary-600 hover:bg-primary-700 focus:ring-4 focus:outline-none focus:ring-primary-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center bg-primary-600 hover:bg-primary-700 focus:ring-primary-800">Login</button>
                    </form>
                </div>
            </div>
        </div>
    </section>
</body>
</html>
