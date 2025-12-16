<?php
session_start();
include 'includes/db.php';

// Validasi ID
if(!isset($_GET['id'])) { header("Location: index.php"); exit; }
$id = (int)$_GET['id'];

// 1. Ambil Info Konten Utama
$q_content = mysqli_query($conn, "SELECT * FROM contents WHERE id = $id");
$content = mysqli_fetch_assoc($q_content);

if(!$content) { echo "Konten tidak ditemukan."; exit; }

// 2. Ambil Episode (Jika Series) atau Video Tunggal (Jika Movie)
$is_series = ($content['type'] == 'series');
$q_videos = mysqli_query($conn, "SELECT * FROM videos WHERE content_id = $id ORDER BY episode_number ASC");

// Jika Movie, ambil video pertama untuk tombol "Play" langsung
$first_video_id = 0;
if(!$is_series) {
    $vid = mysqli_fetch_assoc($q_videos);
    $first_video_id = $vid['id'] ?? 0;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title><?php echo $content['title']; ?> - SecureStream</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="app-container">

        <?php 
        $current_page = ''; // Kosongkan agar tidak ada menu yang aktif (karena ini halaman detail)
        include 'includes/sidebar.php'; 
        ?>

    <div class="main-content" style="padding:0;">
        
        <div class="detail-hero" style="background-image: url('<?php echo $content['poster_landscape']; ?>');">
            <div class="hero-overlay"></div>
        </div>

        <div class="detail-content">
            <h1 class="detail-title"><?php echo $content['title']; ?></h1>
            <div class="detail-meta">
                2025 • Rating <?php echo $content['rating']; ?> • <?php echo ucfirst($content['type']); ?>
            </div>
            
            <p class="detail-desc"><?php echo $content['description']; ?></p>
            
            <div class="btn-group" style="margin-bottom: 40px;">
                <?php if(!$is_series && $first_video_id > 0): ?>
                    <a href="watch.php?v_id=<?php echo $first_video_id; ?>" class="btn-hero btn-play">
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
                    // Reset pointer query karena tadi sudah dipakai cek movie
                    mysqli_data_seek($q_videos, 0); 
                    
                    while($ep = mysqli_fetch_assoc($q_videos)): 
                    ?>
                        <a href="watch.php?v_id=<?php echo $ep['id']; ?>" class="episode-card">
                            <div class="ep-number"><?php echo $ep['episode_number']; ?></div>
                            <div class="ep-thumb">
                                <img src="<?php echo $content['poster_landscape']; ?>">
                                <div class="play-icon-overlay"><i class="fa-solid fa-play"></i></div>
                            </div>
                            <div class="ep-info">
                                <div class="ep-title"><?php echo $ep['episode_title']; ?></div>
                                <div class="ep-duration"><?php echo $ep['duration']; ?></div>
                                <div style="font-size:12px; color:#888; margin-top:5px;">
                                    Sinopsis singkat episode ini...
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