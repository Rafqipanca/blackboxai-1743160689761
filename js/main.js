// JavaScript untuk fungsi dasar website
document.addEventListener('DOMContentLoaded', function() {
    // Animasi scroll halus untuk semua link
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if(target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Fungsi untuk galeri
    const initGallery = () => {
        const galleryItems = document.querySelectorAll('.gallery-item');
        if(galleryItems.length > 0) {
            galleryItems.forEach(item => {
                item.addEventListener('click', function() {
                    const imgSrc = this.querySelector('img').src;
                    openModal(imgSrc);
                });
            });
        }
    };

    // Fungsi modal untuk gambar galeri
    const openModal = (imgSrc) => {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 p-4';
        modal.innerHTML = `
            <div class="relative max-w-4xl w-full">
                <img src="${imgSrc}" alt="Gallery Image" class="w-full h-auto max-h-[80vh] object-contain">
                <button class="absolute top-4 right-4 text-white text-3xl hover:text-gray-300">&times;</button>
            </div>
        `;
        modal.querySelector('button').addEventListener('click', () => {
            modal.remove();
        });
        document.body.appendChild(modal);
    };

    // Fungsi untuk contact form
    const initContactForm = () => {
        const contactForm = document.getElementById('contactForm');
        if(contactForm) {
            contactForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                
                try {
                    // Simulasi pengiriman data ke backend
                    await new Promise(resolve => setTimeout(resolve, 1000));
                    
                    // Tampilkan notifikasi
                    showNotification('Pesan berhasil dikirim!', 'success');
                    this.reset();
                } catch (error) {
                    showNotification('Terjadi kesalahan saat mengirim pesan', 'error');
                }
            });
        }
    };

    // Fungsi untuk menampilkan notifikasi
    const showNotification = (message, type = 'info') => {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg text-white ${
            type === 'success' ? 'bg-green-500' : 
            type === 'error' ? 'bg-red-500' : 'bg-blue-500'
        }`;
        notification.textContent = message;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 3000);
    };

    // Inisialisasi semua fungsi
    initGallery();
    initContactForm();
});