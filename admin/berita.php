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
    $konten = trim($_POST['konten'] ?? '');
    
    // Validasi input
    if (empty($judul)) $errors[] = 'Judul berita harus diisi';
    if (empty($konten)) $errors[] = 'Konten berita harus diisi';
    
    if (empty($errors)) {
        try {
            if ($action === 'tambah') {
                // Handle file upload
                $gambar = '';
                if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
                    $ext = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
                    $gambar = 'berita_' . time() . '.' . $ext;
                    move_uploaded_file($_FILES['gambar']['tmp_name'], '../assets/images/' . $gambar);
                }
                
                // Insert new berita
                $stmt = $conn->prepare("INSERT INTO berita (judul, konten, gambar, penulis_id) VALUES (?, ?, ?, ?)");
                $stmt->execute([$judul, $konten, $gambar, $_SESSION['user_id']]);
                
                // Catat aktivitas
                $deskripsi = "Berita baru ditambahkan: {$judul}";
                $conn->prepare("INSERT INTO aktivitas (deskripsi, icon) VALUES (?, 'newspaper')")
                     ->execute([$deskripsi]);
                
                $success = 'Berita berhasil ditambahkan';
            } 
            elseif ($action === 'edit') {
                // Update existing berita
                if (!empty($_FILES['gambar']['name'])) {
                    $ext = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
                    $gambar = 'berita_' . time() . '.' . $ext;
                    move_uploaded_file($_FILES['gambar']['tmp_name'], '../assets/images/' . $gambar);
                    
                    // Hapus gambar lama jika ada
                    $stmt = $conn->prepare("SELECT gambar FROM berita WHERE id = ?");
                    $stmt->execute([$id]);
                    $oldImage = $stmt->fetchColumn();
                    if ($oldImage && file_exists("../assets/images/{$oldImage}")) {
                        unlink("../assets/images/{$oldImage}");
                    }
                    
                    $stmt = $conn->prepare("UPDATE berita SET judul = ?, konten = ?, gambar = ? WHERE id = ?");
                    $stmt->execute([$judul, $konten, $gambar, $id]);
                } else {
                    $stmt = $conn->prepare("UPDATE berita SET judul = ?, konten = ? WHERE id = ?");
                    $stmt->execute([$judul, $konten, $id]);
                }
                
                $success = 'Berita berhasil diperbarui';
            }
            elseif ($action === 'hapus') {
                // Hapus gambar terkait
                $stmt = $conn->prepare("SELECT gambar FROM berita WHERE id = ?");
                $stmt->execute([$id]);
                $image = $stmt->fetchColumn();
                if ($image && file_exists("../assets/images/{$image}")) {
                    unlink("../assets/images/{$image}");
                }
                
                // Hapus berita
                $conn->prepare("DELETE FROM berita WHERE id = ?")->execute([$id]);
                $success = 'Berita berhasil dihapus';
            }
        } catch (PDOException $e) {
            $errors[] = 'Terjadi kesalahan database: ' . $e->getMessage();
        }
    }
}

// Ambil data berita
$berita = [];
$search = $_GET['search'] ?? '';
$query = "SELECT b.*, p.nama_lengkap as penulis 
          FROM berita b 
          JOIN pengguna p ON b.penulis_id = p.id";

if (!empty($search)) {
    $query .= " WHERE b.judul LIKE :search OR b.konten LIKE :search";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':search', "%{$search}%");
} else {
    $stmt = $conn->prepare($query);
}

$stmt->execute();
$berita = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Berita</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <?php include 'sidebar.php'; ?>
        
        <div class="flex-1 p-8 overflow-y-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold">Kelola Berita</h1>
                <button onclick="toggleModal('tambahModal')" 
                        class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-plus mr-2"></i>Tambah Berita
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
                    <input type="text" name="search" placeholder="Cari berita..." 
                           value="<?= htmlspecialchars($search) ?>"
                           class="w-full px-4 py-2 border rounded-lg pl-10 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                </div>
            </form>
            
            <!-- Berita Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Judul</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Penulis</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($berita as $item): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-medium text-gray-900"><?= htmlspecialchars($item['judul']) ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-gray-500"><?= htmlspecialchars($item['penulis']) ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-gray-500"><?= date('d M Y', strtotime($item['created_at'])) ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <button onclick="openEditModal(<?= htmlspecialchars(json_encode($item)) ?>)" 
                                        class="text-blue-600 hover:text-blue-900 mr-3">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="confirmDelete(<?= $item['id'] ?>)" 
                                        class="text-red-600 hover:text-red-900">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Tambah Berita Modal -->
    <div id="tambahModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
        <div class="bg-white rounded-lg p-6 w-full max-w-2xl">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold">Tambah Berita Baru</h2>
                <button onclick="toggleModal('tambahModal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="tambah">
                
                <div class="mb-4">
                    <label for="judul" class="block text-gray-700 mb-2">Judul Berita</label>
                    <input type="text" id="judul" name="judul" required
                           class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="mb-4">
                    <label for="konten" class="block text-gray-700 mb-2">Konten Berita</label>
                    <textarea id="konten" name="konten" rows="6" required
                              class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                
                <div class="mb-4">
                    <label for="gambar" class="block text-gray-700 mb-2">Gambar (Opsional)</label>
                    <input type="file" id="gambar" name="gambar" accept="image/*"
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
    
    <!-- Edit Berita Modal -->
    <div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
        <div class="bg-white rounded-lg p-6 w-full max-w-2xl">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold">Edit Berita</h2>
                <button onclick="toggleModal('editModal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="edit_id" name="id" value="">
                
                <div class="mb-4">
                    <label for="edit_judul" class="block text-gray-700 mb-2">Judul Berita</label>
                    <input type="text" id="edit_judul" name="judul" required
                           class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="mb-4">
                    <label for="edit_konten" class="block text-gray-700 mb-2">Konten Berita</label>
                    <textarea id="edit_konten" name="konten" rows="6" required
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
            
            <p class="mb-6">Anda yakin ingin menghapus berita ini?</p>
            
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
        
        function openEditModal(berita) {
            document.getElementById('edit_id').value = berita.id;
            document.getElementById('edit_judul').value = berita.judul;
            document.getElementById('edit_konten').value = berita.konten;
            
            const imgElement = document.getElementById('current_image');
            if (berita.gambar) {
                imgElement.src = '../assets/images/' + berita.gambar;
                imgElement.style.display = 'block';
            } else {
                imgElement.style.display = 'none';
            }
            
            toggleModal('editModal');
        }
        
        function confirmDelete(id) {
            document.getElementById('delete_id').value = id;
            toggleModal('deleteModal');
        }
    </script>
</body>
</html>