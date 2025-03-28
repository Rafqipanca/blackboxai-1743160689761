<?php
session_start();

// Cek apakah user sudah login
if(!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

require_once '../config/database.php';

$pageTitle = "Dashboard Admin";
$db = new Database();
$conn = $db->getConnection();

// Ambil statistik untuk dashboard
$stats = [
    'berita' => $conn->query("SELECT COUNT(*) FROM berita")->fetchColumn(),
    'galeri' => $conn->query("SELECT COUNT(*) FROM galeri")->fetchColumn(),
    'pengguna' => $conn->query("SELECT COUNT(*) FROM pengguna")->fetchColumn(),
    'pesan' => $conn->query("SELECT COUNT(*) FROM kontak")->fetchColumn()
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="bg-blue-800 text-white w-64 p-4">
            <h2 class="text-xl font-bold mb-6">Admin Panel</h2>
            <nav>
                <ul class="space-y-2">
                    <li>
                        <a href="dashboard.php" class="flex items-center space-x-2 px-4 py-2 bg-blue-700 rounded-lg">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="berita.php" class="flex items-center space-x-2 px-4 py-2 hover:bg-blue-700 rounded-lg transition">
                            <i class="fas fa-newspaper"></i>
                            <span>Kelola Berita</span>
                        </a>
                    </li>
                    <li>
                        <a href="galeri.php" class="flex items-center space-x-2 px-4 py-2 hover:bg-blue-700 rounded-lg transition">
                            <i class="fas fa-images"></i>
                            <span>Kelola Galeri</span>
                        </a>
                    </li>
                    <li>
                        <a href="pengguna.php" class="flex items-center space-x-2 px-4 py-2 hover:bg-blue-700 rounded-lg transition">
                            <i class="fas fa-users"></i>
                            <span>Kelola Pengguna</span>
                        </a>
                    </li>
                    <li>
                        <a href="logout.php" class="flex items-center space-x-2 px-4 py-2 hover:bg-red-600 rounded-lg transition">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 p-8 overflow-y-auto">
            <h1 class="text-2xl font-bold mb-6">Dashboard</h1>
            
            <!-- Statistik -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500">Total Berita</p>
                            <h3 class="text-2xl font-bold"><?= $stats['berita'] ?></h3>
                        </div>
                        <div class="bg-blue-100 p-3 rounded-full">
                            <i class="fas fa-newspaper text-blue-600 text-xl"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500">Total Galeri</p>
                            <h3 class="text-2xl font-bold"><?= $stats['galeri'] ?></h3>
                        </div>
                        <div class="bg-green-100 p-3 rounded-full">
                            <i class="fas fa-images text-green-600 text-xl"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500">Total Pengguna</p>
                            <h3 class="text-2xl font-bold"><?= $stats['pengguna'] ?></h3>
                        </div>
                        <div class="bg-purple-100 p-3 rounded-full">
                            <i class="fas fa-users text-purple-600 text-xl"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500">Pesan Masuk</p>
                            <h3 class="text-2xl font-bold"><?= $stats['pesan'] ?></h3>
                        </div>
                        <div class="bg-yellow-100 p-3 rounded-full">
                            <i class="fas fa-envelope text-yellow-600 text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-xl font-bold mb-4">Aktivitas Terakhir</h2>
                <div class="space-y-4">
                    <?php
                    $query = "SELECT * FROM aktivitas ORDER BY created_at DESC LIMIT 5";
                    $stmt = $conn->query($query);
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                    ?>
                    <div class="flex items-start space-x-4">
                        <div class="bg-gray-100 p-2 rounded-full">
                            <i class="fas fa-<?= $row['icon'] ?> text-gray-600"></i>
                        </div>
                        <div>
                            <p class="font-medium"><?= htmlspecialchars($row['deskripsi']) ?></p>
                            <p class="text-sm text-gray-500"><?= date('d M Y H:i', strtotime($row['created_at'])) ?></p>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>