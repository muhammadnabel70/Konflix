// --- [ BAGIAN WATERMARK (Disesuaikan) ] ---
const USER_ID = "101_UJI"; 
const USER_IP = "127.0.0.1"; 

const watermark = document.getElementById('watermark');
watermark.innerHTML = `ID: ${USER_ID} <br> IP: ${USER_IP}`;
const container = document.getElementById('video-container');

function moveWatermark() {
    const videoWidth = container.clientWidth;
    const videoHeight = container.clientHeight;
    
    // Perhatikan: Mendapatkan lebar/tinggi watermark saat ini
    const watermarkWidth = watermark.offsetWidth; 
    const watermarkHeight = watermark.offsetHeight;

    // Hitung posisi acak di dalam batas container video
    // Pastikan watermark tidak keluar batas kanan/bawah
    const randomTop = Math.floor(Math.random() * (videoHeight - watermarkHeight - 10)); // -10 untuk sedikit padding
    const randomLeft = Math.floor(Math.random() * (videoWidth - watermarkWidth - 10));  // -10 untuk sedikit padding
    
    watermark.style.top = randomTop + 'px';
    watermark.style.left = randomLeft + 'px';
}

// Interval perpindahan (misal 5 detik)
setInterval(moveWatermark, 5000);
// Panggil sekali saat dimuat
moveWatermark();
// --- [ END WATERMARK ] ---

// --- [ BAGIAN INISIALISASI VIDEO (TETAP SAMA) ] ---
// ... (kode inisialisasi HLS.js Anda yang sudah berfungsi) ...
const video = document.getElementById('my-video');
const videoSrc = 'video/playlist.m3u8'; 

if (Hls.isSupported()) {
    var hls = new Hls();
    hls.loadSource(videoSrc);
    hls.attachMedia(video);
    hls.on(Hls.Events.MANIFEST_PARSED, function() {
        video.play();
    });
    hls.on(Hls.Events.ERROR, function(event, data) {
        console.error('HLS Error Ditemukan:', data.details);
    });
} else if (video.canPlayType('application/vnd.apple.mpegurl')) {
    video.src = videoSrc;
    video.addEventListener('loadedmetadata', function() {
        video.play();
    });
}
// --- [ END VIDEO ] ---