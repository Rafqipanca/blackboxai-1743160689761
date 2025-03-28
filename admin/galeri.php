<?php
session_start();
require_once '../config/database.php';

// Cek login dan role admin
if(!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Handle form submission
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = $_POST['id'] ?? 0;
    $judul = trim($_POST['judul'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    
    // Validasi input
    if (empty($judul)) $errors[] = 'Judul galeri harus diisi';
    
    if (empty($errors)) {
        try {
            if ($action === 'tambah') {
                // Handle file upload
                if (!isset($_FILES['gambar']) || $_FILES['gambar']['error'] !== UPLOAD_ERR_OK) {
                    $errors[] = 'Gambar harus diupload';
                } else {
                    $ext = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
                    $gambar = 'galeri_' . time() . '.' . $ext;
                    move_uploaded_file($_FILES['gambar']['tmp_name'], '../assets/images/' . $gambar);
                    
                    // Insert new galeri
                    $stmt = $conn->prepare("INSERT INTO galeri (judul, deskripsi, gambar) VALUES (?, ?, ?)");
                    $stmt->execute([$judul, $deskripsi, $gambar]);
                    
                    // Catat aktivitas
                    $deskripsiAktivitas = "Galeri baru ditambahkan: {$judul}";
                    $conn->prepare("INSERT INTO aktivitas (deskripsi, icon) VALUES (?, 'images')")
                         ->execute([$deskripsiAktivitas]);
                    
                    $success = 'Galeri berhasil ditambahkan';
                }
            } 
            elseif ($action === 'edit') {
                // Update existing galeri
                if (!empty($_FILES['gambar']['name'])) {
                    $ext = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
                    $gambar = 'galeri_' . time() . '.' . $ext;
                    move_uploaded_file($_FILES['gambar']['tmp_name'], '../assets/images/' . $gambar);
                    
                    // Hapus gambar lama
                    $stmt = $conn->prepare("SELECT gambar FROM galeri WHERE id = ?");
                    $stmt->execute([$id]);
                    $oldImage = $stmt->fetchColumn();
                    if ($oldImage && file_exists("../assets/images/{$oldImage}")) {
                        unlink("../assets/images/{$oldImage}");
                    }
                    
                    $stmt = $conn->prepare("UPDATE galeri SET judul = ?, deskripsi = ?, gambar = ? WHERE id = ?");
                    $stmt->execute([$judul, $deskripsi, $gambar, $id]);
                } else {
                    $stmt = $conn->prepare("UPDATE galeri SET judul = ?, deskripsi = ? WHERE id = ?");
                    $stmt->execute([$judul, $deskripsi, $id]);
                }
                
                $success = 'Galeri berhasil diperbarui';
            }
            elseif ($action === 'hapus') {
                // Hapus gambar terkait
                $stmt = $conn->prepare("SELECT gambar FROM galeri WHERE id = ?");
                $stmt->execute([$id]);
                $image = $stmt->fetchColumn();
                if ($image && file_exists("../assets/images/{$image}")) {
                    unlink("../assets/images/{$image}");
                }
                
                // Hapus galeri
                $conn->prepare("DELETE FROM galeri WHERE id = ?")->execute([$id]);
                $success = 'Galeri berhasil dihapus';
            }
        } catch (PDOException $e) {
            $errors[] = 'Terjadi kesalahan database: ' . $e->getMessage();
        }
    }
}

// Ambil data galeri
$galeri = [];
$search = $_GET['search'] ?? '';
$query = "SELECT * FROM galeri";

if (!empty($search)) {
    $query .= " WHERE judul LIKE :search OR deskripsi LIKE :search";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':search', "%{$search}%");
} else {
    $stmt = $conn->prepare($query);
}

$stmt->execute();
$galeri = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Galeri</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <?php include 'sidebar.php'; ?>
        
        <div class="flex-1 p-8 overflow-y-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold">Kelola Galeri</h1>
                <button onclick="toggleModal('tambahModal')" 
                        class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-plus mr-2"></i>Tambah Galeri
                </button>
            </div>
            
            <?php if (!empty($errors)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php foreach ($errors as $error): ?>
                <p><?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?= htmlspecialchars($success) ?>
            </div>
            <?php endif; ?>
            
            <!-- Search Form -->
            <form method="GET" class="mb-6">
                <div class="relative">
                    <input type="text" name="search" placeholder="Cari galeri..." 
                           value="<?= htmlspecialchars($search) ?>"
                           class="w-full px-4 py-2 border rounded-lg pl-10 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                </div>
            </form>
            
            <!-- Galeri Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                <?php foreach ($galeri as $item): ?>
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="h-48 overflow-hidden">
                        <img src="../assets/images/<?= htmlspecialchars($item['gambar']) ?>" 
                             alt="<?= htmlspecialchars($item['judul']) ?>" 
                             class="w-full h-full object-cover">
                    </div>
                    <div class="p-4">
                        <h3 class="font-bold text-lg mb-1"><?= htmlspecialchars($item['judul']) ?></h3>
                        <p class="text-gray-600 text-sm mb-3"><?= htmlspecialchars($item['deskripsi']) ?></p>
                        <div class="flex justify-end space-x-2">
                            <button onclick="openEditModal(<?= htmlspecialchars(json_encode($item)) ?>)" 
                                    class="text-blue-600 hover:text-blue-900">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="confirmDelete(<?= $item['id'] ?>)" 
                                    class="text-red-600 hover:text-red-900">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- Tambah Galeri Modal -->
    <div id="tambahModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
        <div class="bg-white rounded-lg p-6 w-full max-w-2xl">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold">Tambah Galeri Baru</h2>
                <button onclick="toggleModal('tambahModal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="tambah">
                
                <div class="mb-4">
                    <label for="judul" class="block text-gray-700 mb-2">Judul Galeri</label>
                    <input type="text" id="judul" name="judul" required
                           class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="mb-4">
                    <label for="deskripsi" class="block text-gray-700 mb-2">Deskripsi (Opsional)</label>
                    <textarea id="deskripsi" name="deskripsi" rows="3"
                              class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                
                <div class="mb-4">
                    <label for="gambar" class="block text-gray-700 mb-2">Gambar</label>
                    <input type="file" id="gambar" name="gambar" accept="image/*" required
                           class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="toggleModal('tambahModal')"
                            class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-100 transition">
                        Batal
                    </button>
                    <button type="submit"
                            class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Galeri Modal -->
    <div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
        <div class="bg-white rounded-lg p-6 w-full max-w-2xl">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold">Edit Galeri</h2>
                <button onclick="toggleModal('editModal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="edit_id" name="id" value="">
                
                <div class="mb-4">
                    <label for="edit_judul" class="block text-gray-700 mb-2">Judul Galeri</label>
                    <input type="text" id="edit_judul" name="judul" required
                           class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="mb-4">
                    <label for="edit_deskripsi" class="block text-gray-700 mb-2">Deskripsi (Opsional)</label>
                    <textarea id="edit_deskripsi" name="deskripsi" rows="3"
                              class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Gambar Saat Ini</label>
                    <img id="current_image" src="" alt="Current Image" class="max-h-40 mb-2">
                    <label for="edit_gambar" class="block text-gray-700 mb-2">Ganti Gambar (Opsional)</label>
                    <input type="file" id="edit_gambar" name="gambar" accept="image/*"
                           class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="toggleModal('editModal')"
                            class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-100 transition">
                        Batal
                    </button>
                    <button type="submit"
                            class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
        <div class="bg-white rounded-lg p-6 w-full max-w-md">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold">Konfirmasi Hapus</h2>
                <button onclick="toggleModal('deleteModal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <p class="mb-6">Anda yakin ingin menghapus galeri ini?</p>
            
            <form method="POST" id="deleteForm">
                <input type="hidden" name="action" value="hapus">
                <input type="hidden" id="delete_id" name="id" value="">
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="toggleModal('deleteModal')"
                            class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-100 transition">
                        Batal
                    </button>
                    <button type="submit"
                            class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition">
                        Hapus
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Modal functions
        function toggleModal(modalId) {
            document.getElementById(modalId).classList.toggle('hidden');
        }
        
        function openEditModal(galeri) {
            document.getElementById('edit_id').value = galeri.id;
            document.getElementById('edit_judul').value = galeri.judul;
            document.getElementById('edit_deskripsi').value = galeri.deskripsi;
            
            const imgElement = document.getElementById('current_image');
            imgElement.src = '../assets/images/' + galeri.gambar;
                
            toggleModal('editModal');
        }
        
        function confirmDelete(id) {
            document.getElementById('delete_id').value = id;
            toggleModal('deleteModal');
        }
    </script>
</body>
</html>