<?php
include '../includes/db.php'; // Mundur satu folder untuk cari db.php

// Hitung Data
$total_movies = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM contents WHERE type='movie'"));
$total_series = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM contents WHERE type='series'"));
$total_videos = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM videos"));
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Admin</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>

    <?php include 'includes/sidebar.php'; ?>

    <div class="admin-content">
        <div class="page-header">
            <h2>Dashboard Overview</h2>
        </div>

        <div class="stats-grid">
            <div class="card">
                <h3>Total Movies</h3>
                <h1><?php echo $total_movies; ?></h1>
            </div>
            <div class="card">
                <h3>Total Series</h3>
                <h1><?php echo $total_series; ?></h1>
            </div>
            <div class="card">
                <h3>Total Videos/Eps</h3>
                <h1><?php echo $total_videos; ?></h1>
            </div>
        </div>
    </div>

</body>
</html>