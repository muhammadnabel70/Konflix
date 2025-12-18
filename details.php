<?php
session_start();
include 'includes/db.php';

// --- 1. LOGIKA DECODE HASH (Security) ---
$id = 0;

// Cek apakah ada parameter 'v' (Hash ID)
if (isset($_GET['v'])) {
    $decoded = $hasher->decode($_GET['v']);
    if (count($decoded) > 0) {
        $id = (int)$decoded[0]; // ID Asli ditemukan
    } else {
        header("Location: index.php"); exit; // Hash tidak valid
    }
} 
// Cek parameter legacy 'id' (jika ada link lama)
elseif (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
} else {
    header("Location: index.php"); exit;
}

// --- 2. QUERY KONTEN UTAMA ---
$q_content = mysqli_query($conn, "SELECT * FROM contents WHERE id = $id");
$content = mysqli_fetch_assoc($q_content);

if(!$content) { echo "Konten tidak ditemukan."; exit; }

// --- 3. AMBIL EPISODE / VIDEO ---
$is_series = ($content['type'] == 'series');
$q_videos = mysqli_query($conn, "SELECT * FROM videos WHERE content_id = $id ORDER BY episode_number ASC");

// Jika Movie, kita tidak perlu $first_video_id lagi secara eksplisit untuk link,
// karena kita akan mengarahkan ke watch.php?v=[ContentHash] yang otomatis putar video pertama.
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title><?php echo htmlspecialchars($content['title']); ?> - SecureStream</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="app-container">

    <?php 
    $current_page = ''; 
    include 'includes/sidebar.php'; 
    ?>

    <div class="main-content" style="padding:0;">
        
        <div class="detail-hero" style="background-image: url('<?php echo $content['poster_landscape']; ?>');">
            <div class="hero-overlay"></div>
        </div>

        <div class="detail-content">
            <h1 class="detail-title"><?php echo htmlspecialchars($content['title']); ?></h1>
            <div class="detail-meta">
                2025 • Rating <?php echo $content['rating']; ?> • <?php echo ucfirst($content['type']); ?>
            </div>
            
            <p class="detail-desc"><?php echo htmlspecialchars($content['description']); ?></p>
            
            <div class="btn-group" style="margin-bottom: 40px;">
                <?php if(!$is_series): ?>
                    <a href="watch.php?v=<?php echo $hasher->encode($content['id']); ?>" class="btn-hero btn-play">
                        <i class="fa-solid fa-play"></i> PUTAR FILM
                    </a>
                <?php else: ?>
                    <a href="#episodes" class="btn-hero btn-play">
                        <i class="fa-solid fa-play"></i> LIHAT EPISODE
                    </a>
                <?php endif; ?>
                <button class="btn-hero btn-add"><i class="fa-solid fa-plus"></i></button>
            </div>

            <?php if($is_series): ?>
                <div class="detail-tabs">
                    <div class="tab-item active">Episodes</div>
                </div>

                <div id="episodes" class="episode-list">
                    <?php 
                    // Reset pointer query
                    mysqli_data_seek($q_videos, 0); 
                    
                    while($ep = mysqli_fetch_assoc($q_videos)): 
                        // SECURITY: Encode Video ID menjadi Hash parameter 'play'
                        $ep_hash = $hasher->encode($ep['id']);
                    ?>
                        <a href="watch.php?play=<?php echo $ep_hash; ?>" class="episode-card">
                            <div class="ep-number"><?php echo $ep['episode_number']; ?></div>
                            <div class="ep-thumb">
                                <img src="<?php echo $content['poster_landscape']; ?>">
                                <div class="play-icon-overlay"><i class="fa-solid fa-play"></i></div>
                            </div>
                            <div class="ep-info">
                                <div class="ep-title"><?php echo htmlspecialchars($ep['episode_title']); ?></div>
                                <div class="ep-duration"><?php echo $ep['duration']; ?></div>
                                <div style="font-size:12px; color:#888; margin-top:5px;">
                                
                                </div>
                            </div>
                        </a>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

</body>
</html>