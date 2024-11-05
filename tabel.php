<?php
session_start();
if (!isset($_SESSION['loggedin'])) {
    header("Location: index.php");
    exit();
}

require_once 'routeros_api.class.php';
$API = new RouterosAPI();

function getActiveVPNUsers($API) {
    $active_users = array();

    if ($API->connect('103.100.27.198', 'nopen', 'TamanS1s54!125#')) {
        $active_connections = $API->comm("/ppp/active/print");

        foreach ($active_connections as $connection) {
            $active_users[] = array(
                'username' => $connection['name'],
                'ip' => $connection['address'],
                'uptime' => $connection['uptime']
            );
        }

        $API->disconnect();
    }

    return $active_users;
}

$active_users = getActiveVPNUsers($API);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <title>Active VPN Users</title>
    <style>
        body {
            background-color: #f9fafb;
        }
    </style>
</head>
<body>
<nav class="border-gray-200 bg-gray-900">
    <div class="max-w-screen-xl flex flex-wrap items-center justify-between mx-auto p-4">
        <a href="#" class="flex items-center space-x-3 rtl:space-x-reverse">
            <span class="self-center text-2xl font-semibold whitespace-nowrap text-white">Admin VPN</span>
        </a>
        <button data-collapse-toggle="navbar-default" type="button" class="inline-flex items-center p-2 w-10 h-10 justify-center text-sm text-gray-500 rounded-lg md:hidden hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200 text-gray-400 hover:bg-gray-700 focus:ring-gray-600" aria-controls="navbar-default" aria-expanded="false">
            <span class="sr-only">Open main menu</span>
            <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 17 14">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 1h15M1 7h15M1 13h15"/>
            </svg>
        </button>
        <div class="hidden w-full md:block md:w-auto" id="navbar-default">
            <ul class="font-medium flex flex-col p-4 md:p-0 mt-4 border rounded-lg md:flex-row md:space-x-8 rtl:space-x-reverse md:mt-0 md:border-0 bg-gray-800 md:bg-gray-900 border-gray-700">
                <li>
                    <a href="./tabel.php" class="block py-2 px-3 text-white bg-blue-700 rounded md:bg-transparent md:text-blue-700 md:p-0 text-white md:text-blue-500" aria-current="page">Active User</a>
                </li>
                <li>
                    <a href="./manage.php" class="block py-2 px-3 rounded hover:bg-gray-100 md:hover:bg-transparent md:border-0 md:hover:text-blue-700 md:p-0 text-white md:hover:text-blue-500 hover:bg-gray-700 hover:text-white md:hover:bg-transparent">Manage User</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
<div class="mx-20 my-8">
    <h1 class="text-2xl font-bold text-center mb-6">Active VPN Users</h1>
    <div class="overflow-x-auto shadow-lg rounded-lg">
        <table class="w-full text-sm text-center text-gray-500 bg-white border border-gray-200 rounded-lg">
            <thead class="text-xs text-gray-700 uppercase bg-blue-500 text-white">
                <tr>
                    <th scope="col" class="px-6 py-3">Username</th>
                    <th scope="col" class="px-6 py-3">IP Address</th>
                    <th scope="col" class="px-6 py-3">Time Connected</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($active_users) > 0): ?>
                    <?php foreach ($active_users as $user): ?>
                        <tr class="bg-white hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                                <?= htmlspecialchars($user['username']) ?>
                            </td>
                            <td class="px-6 py-4">
                                <?= htmlspecialchars($user['ip']) ?>
                            </td>
                            <td class="px-6 py-4">
                                <?= htmlspecialchars($user['uptime']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr class="bg-white">
                        <td colspan="3" class="px-6 py-4 text-center text-gray-500">
                            No active VPN users
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    function toggleEditForm(id) {
        const form = document.getElementById(`edit-form-${id}`);
        form.classList.toggle('hidden');
    }
</script>
</body>
</html>
