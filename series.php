<?php
session_start();
include 'includes/db.php';
$current_page = 'series';

// 1. BANNER SLIDESHOW (Ambil 5 SERIES Acak)
$q_hero = mysqli_query($conn, "SELECT * FROM contents WHERE type='series' ORDER BY RAND() LIMIT 5");
$hero_items = [];
while($row = mysqli_fetch_assoc($q_hero)) { $hero_items[] = $row; }

// 2. GRID KONTEN
$q_series = mysqli_query($conn, "SELECT * FROM contents WHERE type='series' ORDER BY id DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Series - SecureStream</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-container">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">

        <div class="hero-slider" id="heroSlider">
            <?php foreach($hero_items as $index => $item): ?>
                <div class="hero-slide <?php echo ($index == 0) ? 'active' : ''; ?>" style="background-image: url('<?php echo $item['poster_landscape']; ?>');">
                    <div class="hero-overlay"></div>
                    <div class="hero-content">
                        <h1 class="hero-title"><?php echo $item['title']; ?></h1>
                        <div class="hero-meta">
                            <?php echo substr($item['release_date'], 0, 4); ?> • Season 1 •
                            <i class="fa-solid fa-star" style="color:gold;"></i> <?php echo $item['rating']; ?>
                        </div>
                        <p class="hero-desc"><?php echo substr($item['description'], 0, 180); ?>...</p>
                        <div class="btn-group">
                            <a href="details.php?id=<?php echo $item['id']; ?>" class="btn-hero btn-play"><i class="fa-solid fa-circle-info"></i> EPISODE</a>
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

        <h1 class="page-title">TV Series</h1>
        <div class="grid-container">
            <?php while($row = mysqli_fetch_assoc($q_series)): ?>
                <a href="details.php?id=<?php echo $row['id']; ?>" class="grid-item">
                    <img src="<?php echo $row['poster_landscape']; ?>">
                </a>
            <?php endwhile; ?>
        </div>

    </div>
</div>

<script>
    let currentSlide = 0;
    const slides = document.querySelectorAll('.hero-slide');
    const dots = document.querySelectorAll('.dot');
    const totalSlides = slides.length;
    let slideInterval;

    function showSlide(index) {
        slides.forEach(slide => slide.classList.remove('active'));
        dots.forEach(dot => dot.classList.remove('active'));
        slides[index].classList.add('active');
        dots[index].classList.add('active');
        currentSlide = index;
    }
    function nextSlide() { let next = (currentSlide + 1) % totalSlides; showSlide(next); }
    function goToSlide(index) { clearInterval(slideInterval); showSlide(index); startAutoSlide(); }
    function startAutoSlide() { slideInterval = setInterval(nextSlide, 5000); }
    if(totalSlides > 1) startAutoSlide();
</script>
</body>
</html>