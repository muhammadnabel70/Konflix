<?php
session_start();
include 'includes/db.php'; // Pastikan di sini sudah ada inisialisasi $hasher
$current_page = 'home'; 

// --- QUERY DATA ---

// 1. BANNER SLIDESHOW (Ambil 5 Konten Acak)
$banner_limit = 5;
$q_hero = mysqli_query($conn, "SELECT * FROM contents ORDER BY RAND() LIMIT $banner_limit");
$hero_items = [];
while($row = mysqli_fetch_assoc($q_hero)) {
    $hero_items[] = $row;
}

// 2. Kategori Baris (Ambil 10 item terbaru per kategori)
$q_movies = mysqli_query($conn, "SELECT * FROM contents WHERE type='movie' ORDER BY id DESC LIMIT 10");
$q_series = mysqli_query($conn, "SELECT * FROM contents WHERE type='series' ORDER BY id DESC LIMIT 10");
$q_sports = mysqli_query($conn, "SELECT * FROM contents WHERE type='sport' ORDER BY id DESC LIMIT 10");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>SecureStream - Home</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    
    <style>
        /* Container Banner */
        .hero-slider {
            position: relative;
            height: 85vh; /* Tinggi Banner */
            width: 100%;
            overflow: hidden;
            background: #000;
        }

        /* Slide Item */
        .hero-slide {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background-size: cover;
            background-position: center top;
            opacity: 0; /* Default sembunyi */
            transition: opacity 1s ease-in-out; /* Efek Fade */
            z-index: 1;
        }

        /* Slide Aktif */
        .hero-slide.active {
            opacity: 1;
            z-index: 2;
        }

        /* Overlay Gradasi (Supaya teks terbaca) */
        .hero-overlay {
            position: absolute; top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(to right, #1a1d29 10%, transparent 60%),
                        linear-gradient(to top, #1a1d29 5%, transparent 30%);
        }
        
        /* Konten Teks di Banner */
        .hero-content {
            position: relative;
            z-index: 10;
            max-width: 600px;
            padding-left: 60px; 
            display: flex;
            flex-direction: column;
            justify-content: center;
            height: 100%;
        }

        .hero-title {
            font-size: 3.5rem;
            margin-bottom: 10px;
            line-height: 1.1;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }

        .hero-meta {
            color: #ccc; font-weight: bold; margin-bottom: 20px; font-size: 14px;
            display: flex; align-items: center; gap: 10px;
        }

        .hero-desc {
            font-size: 1.1rem; line-height: 1.5; color: #ddd; margin-bottom: 30px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.8);
        }

        /* Indikator Titik (Dots) */
        .slider-dots {
            position: absolute;
            bottom: 30px; right: 60px;
            z-index: 20;
            display: flex; gap: 10px;
        }
        .dot {
            width: 8px; height: 8px;
            background: rgba(255,255,255,0.5);
            border-radius: 50%;
            cursor: pointer;
            transition: 0.3s;
        }
        .dot.active { background: #fff; transform: scale(1.3); }

        /* Penyesuaian Jarak Row pertama agar naik sedikit */
        #first-row { margin-top: -40px; position: relative; z-index: 10; }
    </style>
</head>
<body>

<div class="app-container">

    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">

        <div class="hero-slider" id="heroSlider">
            
            <?php foreach($hero_items as $index => $item): ?>
                <?php 
                    // Tentukan class active untuk slide pertama
                    $activeClass = ($index == 0) ? 'active' : ''; 
                    
                    // --- SECURITY: ENCODE ID UNTUK TOMBOL PUTAR ---
                    $encoded_id = $hasher->encode($item['id']);

                    // Tentukan link (Series ke Details, Lainnya ke Watch)
                    // Perhatikan parameter sekarang menggunakan 'v='
                    $playLink = ($item['type'] == 'series') 
                                ? "details.php?v=" . $encoded_id 
                                : "watch.php?v=" . $encoded_id;
                ?>
                
                <div class="hero-slide <?php echo $activeClass; ?>" style="background-image: url('<?php echo $item['poster_landscape']; ?>');">
                    <div class="hero-overlay"></div>
                    <div class="hero-content">
                        <h1 class="hero-title"><?php echo $item['title']; ?></h1>
                        
                        <div class="hero-meta">
                            <span><?php echo substr($item['release_date'], 0, 4); ?></span>
                            <span>•</span>
                            <span><?php echo ucfirst($item['type']); ?></span>
                            <span>•</span>
                            <span style="color:#ffd700;"><i class="fa-solid fa-star"></i> <?php echo $item['rating']; ?></span>
                        </div>
                        
                        <p class="hero-desc">
                            <?php echo substr($item['description'], 0, 150); ?>...
                        </p>
                        
                        <div class="btn-group">
                            <a href="<?php echo $playLink; ?>" class="btn-hero btn-play">
                                <i class="fa-solid fa-play"></i> PUTAR
                            </a>
                            <button class="btn-hero btn-add"><i class="fa-solid fa-plus"></i></button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="slider-dots">
                <?php foreach($hero_items as $index => $item): ?>
                    <div class="dot <?php echo ($index == 0) ? 'active' : ''; ?>" onclick="goToSlide(<?php echo $index; ?>)"></div>
                <?php endforeach; ?>
            </div>

        </div>
        <div id="first-row" class="row-container">
            <h3 class="row-title">Film Terbaru</h3>
            <div class="row-posters">
                <?php while($row = mysqli_fetch_assoc($q_movies)): ?>
                    <a href="watch.php?v=<?php echo $hasher->encode($row['id']); ?>" class="poster">
                        <img src="<?php echo $row['poster_landscape']; ?>" alt="<?php echo $row['title']; ?>">
                    </a>
                <?php endwhile; ?>
            </div>
        </div>

        <div class="row-container">
            <h3 class="row-title">Serial Trending</h3>
            <div class="row-posters">
                <?php while($row = mysqli_fetch_assoc($q_series)): ?>
                    <a href="details.php?v=<?php echo $hasher->encode($row['id']); ?>" class="poster">
                        <img src="<?php echo $row['poster_landscape']; ?>" alt="<?php echo $row['title']; ?>">
                    </a>
                <?php endwhile; ?>
            </div>
        </div>

        <div class="row-container">
            <h3 class="row-title">Olahraga</h3>
            <div class="row-posters">
                <?php while($row = mysqli_fetch_assoc($q_sports)): ?>
                    <a href="watch.php?v=<?php echo $hasher->encode($row['id']); ?>" class="poster">
                        <img src="<?php echo $row['poster_landscape']; ?>" alt="<?php echo $row['title']; ?>">
                    </a>
                <?php endwhile; ?>
            </div>
        </div>

    </div> 
</div>

<script>
    let currentSlide = 0;
    const slides = document.querySelectorAll('.hero-slide');
    const dots = document.querySelectorAll('.dot');
    const totalSlides = slides.length;
    let slideInterval;

    // Fungsi Ganti Slide
    function showSlide(index) {
        // Reset class active
        slides.forEach(slide => slide.classList.remove('active'));
        dots.forEach(dot => dot.classList.remove('active'));

        // Set active baru
        slides[index].classList.add('active');
        dots[index].classList.add('active');
        
        currentSlide = index;
    }

    function nextSlide() {
        let next = (currentSlide + 1) % totalSlides; 
        showSlide(next);
    }

    // Klik Dot Manual
    function goToSlide(index) {
        clearInterval(slideInterval); // Reset timer
        showSlide(index);
        startAutoSlide(); // Mulai timer lagi
    }

    // Mulai Otomatis (5 Detik)
    function startAutoSlide() {
        slideInterval = setInterval(nextSlide, 5000); 
    }

    // Jalankan saat load jika ada lebih dari 1 slide
    if(totalSlides > 1) {
        startAutoSlide();
    }
</script>

</body>
</html>