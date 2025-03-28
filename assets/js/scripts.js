// Fungsi untuk memuat berita dari API
async function loadBerita() {
    try {
        const response = await fetch('api/berita.php');
        const data = await response.json();
        
        if (data.success) {
            const beritaContainer = document.getElementById('berita-container');
            if (beritaContainer) {
                beritaContainer.innerHTML = data.data.map(berita => `
                    <div class="news-card bg-white rounded-lg overflow-hidden shadow-md">
                        <div class="h-48 overflow-hidden">
                            <img src="assets/images/${berita.gambar || 'default-news.jpg'}" 
                                 alt="${berita.judul}" class="w-full h-full object-cover">
                        </div>
                        <div class="p-4">
                            <h3 class="text-lg font-semibold mb-2">${berita.judul}</h3>
                            <p class="text-gray-600 text-sm mb-3">${berita.konten.substring(0, 100)}...</p>
                            <a href="berita-detail.html?id=${berita.id}" 
                               class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                Baca Selengkapnya
                            </a>
                        </div>
                    </div>
                `).join('');
            }
        }
    } catch (error) {
        console.error('Error loading news:', error);
    }
}

// Fungsi untuk memuat galeri dari API
async function loadGaleri() {
    try {
        const response = await fetch('api/gallery.php');
        const data = await response.json();
        
        if (data.success) {
            const galeriContainer = document.getElementById('gallery-container');
            if (galeriContainer) {
                galeriContainer.innerHTML = data.data.map(item => `
                    <div class="gallery-item bg-white rounded-lg overflow-hidden shadow-md">
                        <div class="h-48 overflow-hidden">
                            <img src="assets/images/${item.gambar}" 
                                 alt="${item.judul}" class="w-full h-full object-cover">
                        </div>
                        <div class="p-4">
                            <h3 class="text-lg font-semibold mb-2">${item.judul}</h3>
                            <p class="text-gray-600 text-sm">${item.deskripsi || ''}</p>
                        </div>
                    </div>
                `).join('');
            }
        }
    } catch (error) {
        console.error('Error loading gallery:', error);
    }
}

// Fungsi untuk menampilkan notifikasi
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg text-white ${
        type === 'success' ? 'bg-green-500' : 'bg-red-500'
    }`;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Event listener untuk form kontak
document.addEventListener('DOMContentLoaded', function() {
    // Load data saat halaman dimuat
    loadBerita();
    loadGaleri();
    
    // Handle form kontak
    const contactForm = document.getElementById('contactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = {
                name: this.name.value,
                email: this.email.value,
                subject: this.subject.value,
                message: this.message.value
            };
            
            try {
                const response = await fetch('api/contact.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(formData)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotification('Pesan berhasil dikirim!');
                    this.reset();
                } else {
                    showNotification(result.message || 'Gagal mengirim pesan', 'error');
                }
            } catch (error) {
                showNotification('Terjadi kesalahan: ' + error.message, 'error');
            }
        });
    }
});