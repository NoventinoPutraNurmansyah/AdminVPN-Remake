<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
if (!isset($_SESSION['loggedin'])) {
    header("Location: index.php");
    exit();
}

require('routeros_api.class.php');

// Koneksi ke Mikrotik
$host = '103.100.27.198';
$user = 'WEB-VPN';
$password = 'WEB4P1VPN2024';

$api = new RouterosAPI();
if (!$api->connect($host, $user, $password)) {
    die('Koneksi ke Mikrotik gagal.');
}

// Koneksi ke PostgreSQL
$dbHost = 'localhost';
$dbPort = '5432';
$dbName = 'vpn_elite_global';
$dbUser = 'postgres';
$dbPassword = 'dbApiMikrotik2024!';
$conn = pg_connect("host=$dbHost port=$dbPort dbname=$dbName user=$dbUser password=$dbPassword");
if (!$conn) {
    die('Koneksi ke database PostgreSQL gagal.');
}

// Fungsi untuk menambah user secrets ke Mikrotik dan PostgreSQL
function addSecret($api, $name, $password, $phoneNumber, $conn) {
    // Tambahkan user ke Mikrotik
    $response = $api->comm('/ppp/secret/add', [
        'name' => $name,
        'password' => $password,
        'profile' => 'UserGIN',
        'service' => 'any',
    ]);

    if (isset($response['!trap'])) {
        echo 'Failed to add user in Mikrotik: ' . $response['!trap'][0]['message'];
        return;
    }

    // Tambahkan user ke PostgreSQL
    $query = "INSERT INTO vpn_users (user_vpn, password, no_hp) VALUES ($1, $2, $3)";
    pg_query_params($conn, $query, [$name, $password, $phoneNumber]);
}

// Mendapatkan semua user secrets dari PostgreSQL
function getAllSecrets($conn) {
    $query = "SELECT user_vpn, password, no_hp FROM vpn_users";
    $result = pg_query($conn, $query);
    return pg_fetch_all($result);
}

// Fungsi untuk memperbarui user secrets
function updateUser($api, $name, $newPassword, $newPhone, $conn) {
    // Validasi input
    if (empty($name) || empty($newPassword)) {
        echo "Invalid input.";
        return;
    }

    // Update di Mikrotik
    $response = $api->comm('/ppp/secret/set', [
        '.id' => getSecretId($api, $name),
        'password' => $newPassword
    ]);

    if (isset($response['!trap'])) {
        echo "Failed to update user in Mikrotik: " . $response['!trap'][0]['message'];
        return;
    }

    // Update di PostgreSQL
    $query = "UPDATE vpn_users SET password = $1, no_hp = $2 WHERE user_vpn = $3";
    $result = pg_query_params($conn, $query, [$newPassword, $newPhone, $name]);

    if (!$result) {
        echo "Failed to update user in database.";
    }
}

// Fungsi untuk menghapus user secrets
function deleteUser($api, $name, $conn) {
    // Validasi input
    if (empty($name)) {
        echo "Invalid name.";
        return;
    }

    // Hapus dari Mikrotik
    $response = $api->comm('/ppp/secret/remove', ['.id' => getSecretId($api, $name)]);

    if (isset($response['!trap'])) {
        echo "Failed to delete user in Mikrotik: " . $response['!trap'][0]['message'];
        return;
    }

    // Hapus dari PostgreSQL
    $query = "DELETE FROM vpn_users WHERE user_vpn = $1";
    $result = pg_query_params($conn, $query, [$name]);

    if (!$result) {
        echo "Failed to delete user from database.";
    }
}

// Fungsi untuk mendapatkan ID secret dari Mikrotik
function getSecretId($api, $name) {
    $secrets = $api->comm('/ppp/secret/print', [
        '?name' => $name
    ]);
    return $secrets[0]['.id'] ?? null;
}

// Memeriksa aksi yang dikirim melalui metode POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        addSecret($api, $_POST['name'], $_POST['password'], $_POST['phone_number'], $conn);
    } elseif ($action === 'update') {
        updateUser($api, $_POST['name'], $_POST['password'], $_POST['phone_number'], $conn);
    } elseif ($action === 'delete') {
        deleteUser($api, $_POST['name'], $conn);
    }

    header('Location: manage.php');
    exit;
}

// Mendapatkan semua user secrets untuk ditampilkan dari database PostgreSQL
$secrets = getAllSecrets($conn);

// Tutup koneksi ke Mikrotik dan PostgreSQL
$api->disconnect();
pg_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="../public/css/output.css" rel="stylesheet">
    <title>Manage User Secrets</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f9fafb;
        }
    </style>
</head>
<body>
    <nav class="sticky top-0 border-gray-200 bg-gray-900">
        <div class="max-w-screen-xl flex flex-wrap items-center justify-between mx-auto p-4">
            <span class="self-center text-2xl font-semibold whitespace-nowrap text-white">Admin VPN</span>
            <div class="hidden w-full md:block md:w-auto">
                <ul class="font-medium flex flex-col p-4 md:p-0 mt-4 border border-gray-100 rounded-lg bg-gray-50 md:flex-row md:space-x-8 rtl:space-x-reverse md:mt-0 md:border-0 bg-gray-800 bg-gray-900 border-gray-700">
                    <li>
                        <a href="./tabel.php" class="block py-2 px-3 text-gray-900 rounded hover:bg-gray-100 md:hover:bg-transparent md:border-0 md:hover:text-blue-700 md:p-0 text-white md:hover:text-blue-500 hover:bg-gray-700 hover:text-white md:hover:bg-transparent">Active User</a>
                    </li>
                    <li>
                        <a href="./manage.php" class="block py-2 px-3 text-white bg-blue-700 rounded md:bg-transparent md:text-blue-700 md:p-0 text-white md:text-blue-500" aria-current="page">Manage User Secrets</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <div class="container mx-auto mt-10 p-5 rounded-lg shadow-lg bg-white">
        <h1 class="text-2xl font-bold text-center mb-6">Manage User Secrets</h1>

        <button onclick="toggleAddForm()" class="mb-4 px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">Add User</button>
        
        <form method="post" action="manage.php" id="add-user-form" class="mb-6 hidden">
            <input type="hidden" name="action" value="add">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <input type="text" name="name" class="border border-gray-300 rounded-md p-2" placeholder="Username" required>
                <input type="password" name="password" class="border border-gray-300 rounded-md p-2" placeholder="Password" required>
                <input type="text" name="phone_number" class="border border-gray-300 rounded-md p-2" placeholder="Nomor HP" required>
            </div>
            <button type="submit" class="mt-4 px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">Submit</button>
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-300 rounded-md">
                <thead>
                    <tr class="bg-blue-500 text-white">
                        <th class="py-2 px-4 border-b">Username</th>
                        <th class="py-2 px-4 border-b">Password</th>
                        <th class="py-2 px-4 border-b">Nomor HP</th>
                        <th class="py-2 px-4 border-b">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($secrets): ?>
                        <?php foreach ($secrets as $secret): ?>
                            <tr class="hover:bg-gray-100">
                                <td class="py-2 px-4 border-b text-center"><?= htmlspecialchars($secret['user_vpn']) ?></td>
                                <td class="py-2 px-4 border-b text-center"><?= htmlspecialchars($secret['password']) ?></td>
                                <td class="py-2 px-4 border-b text-center"><?= htmlspecialchars($secret['no_hp']) ?></td>
                                <td class="py-2 px-4 border-b">
                                   <div class="flex justify-center space-x-2">
                                    <button onclick="showUpdateForm('<?= htmlspecialchars($secret['user_vpn']) ?>', '<?= htmlspecialchars($secret['password']) ?>', '<?= htmlspecialchars($secret['no_hp']) ?>')" class="px-2 text-center  py-1 bg-yellow-500 text-white rounded hover:bg-yellow-600">Update</button>
                                    <form method="post" action="manage.php" class="inline">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="name" value="<?= htmlspecialchars($secret['user_vpn']) ?>">
                                        <button type="submit" class="px-2 py-1 text-center bg-red-500 text-white rounded hover:bg-red-600">Delete</button>
                                    </form>
                                  </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-2">No user secrets found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <form method="post" action="manage.php" id="update-user-form" class="hidden mt-6">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="name" id="update-username">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <input type="text" name="password" id="update-password" class="border border-gray-300 rounded-md p-2" placeholder="New Password" required>
                <input type="text" name="phone_number" id="update-phone" class="border border-gray-300 rounded-md p-2" placeholder="New Phone Number" required>
            </div>
            <button type="submit" class="mt-4 px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">Update User</button>
        </form>
    </div>

    <script>
        function toggleAddForm() {
            const form = document.getElementById('add-user-form');
            form.classList.toggle('hidden');
        }

        function showUpdateForm(username, password, phone) {
            document.getElementById('update-username').value = username;
            document.getElementById('update-password').value = password;
            document.getElementById('update-phone').value = phone;
            document.getElementById('update-user-form').classList.remove('hidden');
        }
    </script>
</body>
</html>
