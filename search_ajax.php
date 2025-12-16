<?php
include 'includes/db.php';

if(isset($_POST['query']) || isset($_POST['genre'])) {
    
    $sql = "SELECT * FROM contents WHERE 1=1";
    
    // Filter Keyword
    if(!empty($_POST['query'])) {
        $q = mysqli_real_escape_string($conn, $_POST['query']);
        $sql .= " AND (title LIKE '%$q%' OR description LIKE '%$q%')";
    }
    
    // Filter Genre
    if(!empty($_POST['genre'])) {
        $g = (int)$_POST['genre'];
        // Join ke tabel content_genres untuk filter
        $sql .= " AND id IN (SELECT content_id FROM content_genres WHERE genre_id = $g)";
    }
    
    $sql .= " ORDER BY id DESC LIMIT 20";
    $result = mysqli_query($conn, $sql);

    if(mysqli_num_rows($result) > 0) {
        echo '<div class="grid-container">';
        while($row = mysqli_fetch_assoc($result)) {
            $link = ($row['type'] == 'series') ? "details.php?id=".$row['id'] : "watch.php?c_id=".$row['id'];
            echo '
            <a href="'.$link.'" class="grid-item animate-in">
                <img src="'.$row['poster_landscape'].'">
                <div class="item-overlay">
                    <div class="play-icon"><i class="fa-solid fa-play"></i></div>
                    <div class="item-title">'.$row['title'].'</div>
                </div>
            </a>';
        }
        echo '</div>';
    } else {
        echo '<div class="no-result">
                <i class="fa-regular fa-face-frown-open"></i>
                <h3>Tidak ditemukan</h3>
                <p>Coba kata kunci atau genre lain.</p>
              </div>';
    }
}
?>