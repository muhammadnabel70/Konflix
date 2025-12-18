<?php 
include 'includes/db.php'; 
session_start();

// --- 1. CEK LOGIN ---
if(!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
$uid = $_SESSION['user_id'];

// --- 2. AMBIL STATUS USER (PREMIUM CHECK) ---
$q_user = mysqli_query($conn, "SELECT is_subscribed, subscription_end, status, ban_reason, username, email FROM users WHERE id = $uid");
$u_data = mysqli_fetch_assoc($q_user);

if($u_data['status'] == 'banned') {
    $reason_text = htmlspecialchars($u_data['ban_reason'] ?? 'Pelanggaran Syarat & Ketentuan.');
    die("<!DOCTYPE html><html><head><title>Banned</title></head><body style='background:#000;color:red;text-align:center;padding:50px;'><h1>AKUN DIBEKUKAN</h1><p>$reason_text</p><a href='logout.php' style='color:white;'>Keluar</a></body></html>");
}

// Cek masa aktif premium
$is_premium = ($u_data['is_subscribed'] == 1 && strtotime($u_data['subscription_end']) > time());
$block_access = !$is_premium; 


// --- 3. LOGIKA PENERIMA ID / HASH (FINAL FIX) ---
$vid_id = 0;      // ID Video Spesifik (untuk Episode / play=...)
$content_id = 0;  // ID Konten Utama (untuk Film / v=...)

// A. Cek Parameter 'play' (Hash Video ID - Biasanya dari Episode List)
// INI YANG SEBELUMNYA HILANG
if (isset($_GET['play'])) {
    $decoded = $hasher->decode($_GET['play']);
    if (count($decoded) > 0) {
        $vid_id = (int)$decoded[0];
    } else {
        die("Link Episode Rusak.");
    }
} 
// B. Cek Parameter 'v' (Hash Content ID - Biasanya dari Home/Movies)
elseif (isset($_GET['v'])) {
    $decoded = $hasher->decode($_GET['v']);
    if (count($decoded) > 0) {
        $content_id = (int)$decoded[0];
    } else {
        die("Link Konten Rusak.");
    }
}
// C. Legacy Check (Untuk kompatibilitas link lama)
elseif (isset($_GET['v_id'])) { $vid_id = (int)$_GET['v_id']; }
elseif (isset($_GET['c_id'])) { $content_id = (int)$_GET['c_id']; }

// Tentukan Kondisi SQL berdasarkan ID yang didapat
if ($vid_id > 0) {
    // Jika ada ID Video spesifik (Episode), ambil video itu
    $id_condition = "v.id = $vid_id";
} elseif ($content_id > 0) {
    // Jika cuma ada ID Konten (Film), ambil video pertama
    $id_condition = "c.id = $content_id";
} else {
    // Jika tidak ada ID sama sekali, lempar ke Home
    header("Location: index.php"); 
    exit; 
}

// --- 4. QUERY DATABASE ---
// Saya tambahkan ORDER BY agar jika yang dipilih FILM/SERIES (tanpa episode spesifik), 
// yang muncul adalah episode 1
$sql = "SELECT c.*, v.file_path_m3u8, v.subtitle_path, v.duration as vid_duration, v.episode_title, 
        GROUP_CONCAT(g.name SEPARATOR ', ') as genre_list
        FROM videos v 
        JOIN contents c ON v.content_id = c.id 
        LEFT JOIN content_genres cg ON c.id = cg.content_id
        LEFT JOIN genres g ON cg.genre_id = g.id
        WHERE $id_condition
        GROUP BY c.id 
        ORDER BY v.episode_number ASC 
        LIMIT 1"; 

$q = mysqli_query($conn, $sql);
if (!$q) die("Error Query: " . mysqli_error($conn));
$data = mysqli_fetch_assoc($q);

if($data) {
    $file_path = $data['file_path_m3u8'];
    $full_title = $data['title'];
    if(!empty($data['episode_title'])) $full_title .= " - " . $data['episode_title'];
} else {
    die("<div style='color:white;text-align:center;margin-top:50px;'>Video tidak ditemukan.<br><a href='index.php' style='color:#E50914;'>Kembali</a></div>");
}

// --- 5. WATERMARK & ASSETS ---
$user_watermark = "ID: $uid | User: " . $u_data['username'];
$bg_image = $data['poster_landscape'] ?? $data['poster_portrait'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Watching: <?php echo htmlspecialchars($full_title); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* CSS SAMA SEPERTI SEBELUMNYA - TIDAK ADA PERUBAHAN TAMPILAN */
        body { margin: 0; background: #0f0f0f; color: #e0e0e0; font-family: 'Segoe UI', sans-serif; overflow-x: hidden; min-height: 100vh; }
        a { text-decoration: none; color: inherit; transition:0.3s;}
        .page-bg { position: fixed; top: -20px; left: -20px; right: -20px; bottom: -20px; background-size: cover; background-position: center; filter: blur(30px) brightness(0.3); z-index: -1; transform: scale(1.1); }
        .watch-container { max-width: 1200px; margin: 0 auto; padding: 20px; position: relative; z-index: 10; }
        .back-btn { display: inline-flex; align-items: center; gap: 10px; color: #ccc; margin-bottom: 20px; font-weight: bold; text-shadow: 0 2px 4px rgba(0,0,0,0.8); }
        .back-btn:hover { color: white; }
        #player-wrapper { position: relative; width: 100%; background: #000; border-radius: 12px; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.8); aspect-ratio: 16/9; }
        video { position: absolute; opacity: 0; z-index: 1; width: 100%; height: 100%; pointer-events: none; }
        canvas { display: block; width: 100%; height: 100%; cursor: pointer; position: relative; z-index: 5; }
        #subtitleDisplay { position: absolute; bottom: 80px; left: 0; right: 0; text-align: center; pointer-events: none; z-index: 15; color: #ffffffff; font-size: 24px; font-weight: bold; font-family: Arial, sans-serif; text-shadow: 2px 2px 0 #000, -1px -1px 0 #000, 1px -1px 0 #000, -1px 1px 0 #000; padding: 0 20px; display: none; }
        #playOverlay { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); display: flex; align-items: center; justify-content: center; z-index: 25; cursor: pointer; transition: opacity 0.3s; }
        #playOverlay button { width: 80px; height: 80px; background: rgba(255, 255, 255, 0.2); color: white; border-radius: 50%; border: 2px solid white; font-size: 36px; cursor: pointer; backdrop-filter: blur(5px); }
        #playOverlay button:hover { background: rgba(255, 255, 255, 0.3); transform: scale(1.1); }
        .controls-overlay { position: absolute; bottom: 0; left: 0; right: 0; background: linear-gradient(to top, rgba(0,0,0,0.9), transparent); padding: 20px; display: flex; flex-direction: column; gap: 10px; opacity: 0; transition: opacity 0.3s; z-index: 30; }
        #player-wrapper:hover .controls-overlay { opacity: 1; }
        .progress-container { width: 100%; height: 5px; background: rgba(255,255,255,0.3); cursor: pointer; border-radius: 5px; position: relative; }
        .progress-filled { height: 100%; background: #E50914; width: 0%; border-radius: 5px; position: relative; }
        .controls-row { display: flex; align-items: center; justify-content: space-between; }
        .left-controls, .right-controls { display: flex; align-items: center; gap: 20px; }
        .c-btn { background: none; border: none; color: white; font-size: 18px; cursor: pointer; opacity: 0.9; padding: 0; filter: drop-shadow(0 2px 2px rgba(0,0,0,0.8)); }
        .volume-wrapper { display: flex; align-items: center; gap: 10px; }
        .volume-container { width: 80px; display: flex; align-items: center; }
        input[type=range] { width: 100%; cursor: pointer; height: 3px; accent-color: white; }
        .quality-menu { position: absolute; bottom: 40px; right: -10px; background: rgba(20, 20, 20, 0.95); border: 1px solid #333; border-radius: 5px; padding: 5px 0; display: none; flex-direction: column; min-width: 140px; z-index: 100; }
        .quality-menu.show { display: flex; }
        .q-item { padding: 10px 15px; color: #ccc; cursor: pointer; font-size: 13px; display: flex; justify-content: space-between; }
        .q-item.active { color: #E50914; font-weight: bold; }
        .q-item .check-icon { display: none; }
        .q-item.active .check-icon { display: block; }
        .modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.85); z-index: 9999; display: none; align-items: center; justify-content: center; backdrop-filter: blur(5px); }
        .modal-box { background: #1a1d29; padding: 40px; border-radius: 12px; text-align: center; border: 1px solid #333; max-width: 400px; box-shadow: 0 0 30px rgba(229, 9, 20, 0.3); animation: popup 0.3s ease-out; }
        @keyframes popup { from { transform: scale(0.8); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        .modal-icon { font-size: 50px; color: #E50914; margin-bottom: 20px; }
        .modal-title { font-size: 24px; margin-bottom: 10px; color: white; }
        .modal-desc { color: #ccc; margin-bottom: 30px; line-height: 1.5; }
        .btn-modal { background: #E50914; color: white; padding: 12px 30px; border-radius: 5px; text-decoration: none; font-weight: bold; display: inline-block; transition: 0.3s; }
        .btn-modal:hover { background: #b20710; transform: scale(1.05); }
        .info-section { margin-top: 30px; display: flex; gap: 40px; }
        .poster-area img { width: 200px; border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        .meta-area { flex: 1; text-shadow: 0 2px 4px rgba(0,0,0,0.8); }
        .vid-title { font-size: 2.5rem; margin: 0 0 10px; color: white; }
        .vid-meta-tags { display: flex; gap: 15px; color: #ddd; font-size: 14px; margin-bottom: 20px; align-items: center; }
        .badge { border: 1px solid #ddd; padding: 2px 6px; border-radius: 4px; font-size: 12px; }
        .rating-star { color: #E50914; font-weight: bold; }
        .cast-info { display: grid; grid-template-columns: 100px auto; gap: 10px; font-size: 14px; color: #ccc; }
        .label { color: #aaa; font-weight: bold; }
    </style>
</head>
<body>

<div class="modal-overlay" id="premiumModal">
    <div class="modal-box">
        <div class="modal-icon"><i class="fa-solid fa-crown"></i></div>
        <h2 class="modal-title">Konten Premium</h2>
        <p class="modal-desc">Maaf, video ini hanya tersedia untuk member Premium. Aktifkan langganan Anda untuk menonton tanpa batas.</p>
        <a href="subscribe.php" class="btn-modal">BERLANGGANAN SEKARANG</a>
        <br><br>
        <a href="index.php" style="color:#777; font-size:12px;">Kembali ke Beranda</a>
    </div>
</div>

<div class="page-bg" style="background-image: url('<?php echo $bg_image; ?>');"></div>

<div class="watch-container">
    <a href="index.php" class="back-btn"><i class="fa-solid fa-arrow-left"></i> Kembali ke Browse</a>

    <div id="player-wrapper">
        <video id="sourceVideo" preload="auto" crossorigin="anonymous">
            <?php if(!empty($data['subtitle_path'])): ?>
                <track label="Indonesia" kind="subtitles" srclang="id" src="<?php echo $data['subtitle_path']; ?>" default>
            <?php endif; ?>
        </video>
        
        <canvas id="canvasPlayer"></canvas>
        <div id="subtitleDisplay"></div>

        <div id="playOverlay">
            <button id="mainPlayBtn"><i class="fa-solid fa-play"></i></button>
        </div>

        <div class="controls-overlay">
            <div class="progress-container" id="progressBar">
                <div class="progress-filled" id="progressFilled"></div>
            </div>

            <div class="controls-row">
                <div class="left-controls">
                    <button class="c-btn" id="playPauseBtn"><i class="fa-solid fa-play"></i></button>
                    
                    <div class="volume-wrapper">
                        <button class="c-btn" id="muteBtn"><i class="fa-solid fa-volume-high"></i></button>
                        <div class="volume-container">
                            <input type="range" id="volumeSlider" min="0" max="1" step="0.1" value="1">
                        </div>
                    </div>

                    <div style="font-size: 13px; font-family: monospace; color:#ddd;">
                        <span id="currentTime">00:00</span> / <span id="duration">00:00</span>
                    </div>
                </div>

                <div class="right-controls">
                    <button class="c-btn" id="subtitleBtn" title="Subtitle" style="display:none;">
                        <i class="fa-solid fa-closed-captioning"></i>
                    </button>

                    <div class="settings-wrapper">
                        <button class="c-btn" id="qualityBtn"><i class="fa-solid fa-gear"></i></button>
                        <div class="quality-menu" id="qualityMenu">
                            <div class="q-item active" onclick="changeQuality(1.0, this)"><span>1080p HD</span><i class="fa-solid fa-check check-icon"></i></div>
                            <div class="q-item" onclick="changeQuality(0.66, this)"><span>720p</span><i class="fa-solid fa-check check-icon"></i></div>
                            <div class="q-item" onclick="changeQuality(0.44, this)"><span>480p</span><i class="fa-solid fa-check check-icon"></i></div>
                            <div class="q-item" onclick="changeQuality(0.1, this)"><span>144p</span><i class="fa-solid fa-check check-icon"></i></div>
                        </div>
                    </div>
                    
                    <button class="c-btn" id="fsBtn"><i class="fa-solid fa-expand"></i></button>
                </div>
            </div>
        </div>
        
        <input type="hidden" id="wm_data" value="<?php echo $user_watermark; ?>">
    </div>

    <div class="info-section">
        <div class="poster-area">
            <img src="<?php echo $data['poster_portrait'] ?? $data['poster_landscape']; ?>" alt="Poster">
        </div>
        <div class="meta-area">
            <h1 class="vid-title"><?php echo htmlspecialchars($full_title); ?></h1>
            <div class="vid-meta-tags">
                <span class="rating-star"><i class="fa-solid fa-star"></i> <?php echo $data['rating'] ?? '-'; ?></span>
                <span><?php echo substr($data['release_date'] ?? 'TBA', 0, 4); ?></span>
                <span class="badge"><?php echo ucfirst($data['type'] ?? '-'); ?></span>
                <span><?php echo $data['vid_duration'] ?? '-'; ?></span>
            </div>
            <p style="line-height:1.6; color:#eee;"><?php echo htmlspecialchars($data['description'] ?? 'Sinopsis tidak tersedia.'); ?></p>
            <div class="cast-info">
                <div class="label">Genre</div><div><?php echo $data['genre_list'] ?? '-'; ?></div>
                <div class="label">Sutradara</div><div><?php echo htmlspecialchars($data['director'] ?? '-'); ?></div>
                <div class="label">Pemeran</div><div><?php echo htmlspecialchars($data['actors'] ?? '-'); ?></div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js"></script> 
<script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script> 
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script> 

<script>
    const videoSrc = "<?php echo $file_path; ?>";
    const video = document.getElementById('sourceVideo');
    const canvas = document.getElementById('canvasPlayer');
    const ctx = canvas.getContext('2d', { alpha: false, willReadFrequently: true });
    
    // UI Elements
    const playOverlay = document.getElementById('playOverlay');
    const premiumModal = document.getElementById('premiumModal'); // Modal Baru
    const mainPlayBtn = document.getElementById('mainPlayBtn');
    const playBtn = document.getElementById('playPauseBtn');
    const volumeSlider = document.getElementById('volumeSlider');
    const muteBtn = document.getElementById('muteBtn');
    const progressBar = document.getElementById('progressBar');
    const progressFilled = document.getElementById('progressFilled');
    const qualityMenu = document.getElementById('qualityMenu');
    const qualityBtn = document.getElementById('qualityBtn');
    const subtitleBtn = document.getElementById('subtitleBtn');
    const subtitleDisplay = document.getElementById('subtitleDisplay');
    const playerWrapper = document.getElementById('player-wrapper');
    const wmText = document.getElementById('wm_data').value;
    
    let wm = { x: 50, y: 50, vx: 0.6, vy: 0.4 };
    let dpr = window.devicePixelRatio || 1;
    let raf = null;
    let currentScale = 1.0; 
    let tempCanvas = document.createElement('canvas'); 
    let tempCtx = tempCanvas.getContext('2d');
    let isSubOn = true; 

    // 1. SETUP HLS
    if (Hls.isSupported()) {
        var hls = new Hls();
        hls.loadSource(videoSrc);
        hls.attachMedia(video);
        hls.on(Hls.Events.MANIFEST_PARSED, function() {
            playOverlay.style.opacity = 1;
        });
    } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
        video.src = videoSrc;
    }

    // 2. RESIZE
    function resizeCanvasResolution() {
        const rect = playerWrapper.getBoundingClientRect();
        canvas.width = rect.width * dpr;
        canvas.height = rect.height * dpr;
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    }
    window.addEventListener('resize', resizeCanvasResolution);
    resizeCanvasResolution();

    // 3. QUALITY
    function changeQuality(scale, element) {
        currentScale = scale;
        document.querySelectorAll('.q-item').forEach(el => el.classList.remove('active'));
        element.classList.add('active');
        qualityMenu.classList.remove('show');
    }
    qualityBtn.addEventListener('click', (e) => { e.stopPropagation(); qualityMenu.classList.toggle('show'); });
    document.addEventListener('click', (e) => {
        if (!qualityBtn.contains(e.target) && !qualityMenu.contains(e.target)) qualityMenu.classList.remove('show');
    });

    // 4. SUBTITLE POLLING
    let subCheck = setInterval(() => {
        if (video.textTracks.length > 0) {
            const track = video.textTracks[0];
            if (track.mode !== 'hidden') track.mode = 'hidden';
            
            subtitleBtn.style.display = 'block'; 
            subtitleBtn.style.color = '#E50914';
            isSubOn = true;
            
            track.oncuechange = () => {
                if (isSubOn && track.activeCues.length > 0) {
                    let text = track.activeCues[0].text.replace(/<[^>]*>/g, '');
                    subtitleDisplay.innerText = text;
                    subtitleDisplay.style.display = 'block';
                } else {
                    subtitleDisplay.style.display = 'none';
                }
            };
            clearInterval(subCheck);
        }
    }, 500);

    subtitleBtn.addEventListener('click', () => {
        isSubOn = !isSubOn;
        subtitleBtn.style.color = isSubOn ? '#E50914' : '#777';
        subtitleDisplay.style.display = isSubOn ? 'block' : 'none';
    });

    // 5. DRAW LOOP
    function drawLoop() {
        if (video.paused || video.ended) { cancelAnimationFrame(raf); raf = null; return; }
        const rect = playerWrapper.getBoundingClientRect();
        if (canvas.width !== rect.width * dpr) resizeCanvasResolution();

        const vidW = video.videoWidth || 1280;
        const vidH = video.videoHeight || 720;
        const canvasW = canvas.width / dpr;
        const canvasH = canvas.height / dpr;
        const scale = Math.min(canvasW / vidW, canvasH / vidH);
        const drawW = vidW * scale;
        const drawH = vidH * scale;
        const offsetX = (canvasW - drawW) / 2;
        const offsetY = (canvasH - drawH) / 2;

        ctx.fillStyle = "black";
        ctx.fillRect(0, 0, canvasW, canvasH);

        if (video.videoWidth > 0) {
            ctx.imageSmoothingEnabled = true; ctx.imageSmoothingQuality = 'low';
            if (currentScale === 1.0) {
                ctx.drawImage(video, offsetX, offsetY, drawW, drawH);
            } else {
                tempCanvas.width = vidW * currentScale; tempCanvas.height = vidH * currentScale;
                tempCtx.imageSmoothingEnabled = true;
                tempCtx.drawImage(video, 0, 0, tempCanvas.width, tempCanvas.height);
                ctx.drawImage(tempCanvas, offsetX, offsetY, drawW, drawH);
            }
        }

        wm.x += wm.vx; wm.y += wm.vy;
        if(wm.x < 0 || wm.x > canvasW - 300) wm.vx *= -1; 
        if(wm.y < 30 || wm.y > canvasH) wm.vy *= -1;
        ctx.font = "bold 24px Arial"; ctx.fillStyle = "rgba(255, 255, 255, 0.04)"; 
        ctx.fillText(wmText, wm.x, wm.y);

        const pct = (video.currentTime / video.duration) * 100;
        progressFilled.style.width = pct + "%";
        document.getElementById('currentTime').innerText = formatTime(video.currentTime);
        if(!isNaN(video.duration)) document.getElementById('duration').innerText = formatTime(video.duration);

        raf = requestAnimationFrame(drawLoop);
    }

    // 6. CONTROLS
    function togglePlay() {
        // --- PROTEKSI PREMIUM DI SINI ---
        <?php if($block_access): ?>
            premiumModal.style.display = 'flex';
            return;
        <?php endif; ?>

        if (video.paused) {
            video.muted = false; 
            video.play().then(() => {
                playOverlay.style.display = 'none';
                if(!raf) raf = requestAnimationFrame(drawLoop);
                playBtn.innerHTML = '<i class="fa-solid fa-pause"></i>';
            });
        } else {
            video.pause();
            playOverlay.style.display = 'flex';
            playBtn.innerHTML = '<i class="fa-solid fa-play"></i>';
        }
    }

    mainPlayBtn.addEventListener('click', togglePlay);
    playBtn.addEventListener('click', togglePlay);
    canvas.addEventListener('click', togglePlay);

    volumeSlider.addEventListener('input', (e) => {
        video.volume = e.target.value;
        video.muted = false;
        updateVolIcon(e.target.value);
    });
    muteBtn.addEventListener('click', () => {
        video.muted = !video.muted;
        updateVolIcon(video.muted ? 0 : video.volume);
    });
    function updateVolIcon(val) {
        if(val == 0 || video.muted) muteBtn.innerHTML = '<i class="fa-solid fa-volume-xmark"></i>';
        else if(val < 0.5) muteBtn.innerHTML = '<i class="fa-solid fa-volume-low"></i>';
        else muteBtn.innerHTML = '<i class="fa-solid fa-volume-high"></i>';
    }

    progressBar.addEventListener('click', (e) => {
        const rect = progressBar.getBoundingClientRect();
        video.currentTime = ((e.clientX - rect.left) / rect.width) * video.duration;
    });

    document.getElementById('fsBtn').addEventListener('click', () => {
        if (!document.fullscreenElement) playerWrapper.requestFullscreen();
        else document.exitFullscreen();
    });

    function formatTime(s) {
        if(isNaN(s)) return "00:00";
        let m = Math.floor(s / 60); s = Math.floor(s % 60);
        return m + ":" + (s < 10 ? "0" : "") + s;
    }
    
   
</script>
</body>
</html>