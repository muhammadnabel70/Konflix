<?php
include '../includes/db.php';

// Ambil semua genre dari database untuk ditampilkan di form
$q_genres = mysqli_query($conn, "SELECT id, name FROM genres ORDER BY name ASC");

if(isset($_POST['submit'])) {
    // Ambil data baru
    $title      = mysqli_real_escape_string($conn, $_POST['title']);
    $desc       = mysqli_real_escape_string($conn, $_POST['description']);
    $type       = $_POST['type'];
    $rating     = $_POST['rating'];
    $director   = mysqli_real_escape_string($conn, $_POST['director']); // NEW
    $actors     = mysqli_real_escape_string($conn, $_POST['actors']);   // NEW
    $genres     = $_POST['genres'] ?? []; // NEW: Array of selected genre IDs
    $release_date = $_POST['release_date'] ?? date('Y-m-d'); // NEW: Tanggal rilis

    // --- LOGIKA UPLOAD GAMBAR ---
    $poster_name = $_FILES['poster']['name'];
    $poster_tmp  = $_FILES['poster']['tmp_name'];
    
    // Simpan ke folder assets/img/ di root
    $target_dir = "../assets/img/";
    $target_file = $target_dir . basename($poster_name);
    $db_poster_path = "assets/img/" . basename($poster_name); // Path untuk database
    
    if(move_uploaded_file($poster_tmp, $target_file)) {
        
        // 1. INSERT DATA UTAMA ke tabel contents
        $query_content = "INSERT INTO contents (title, description, type, rating, release_date, director, actors, poster_landscape) 
                          VALUES ('$title', '$desc', '$type', '$rating', '$release_date', '$director', '$actors', '$db_poster_path')";
        
        if(mysqli_query($conn, $query_content)) {
            $new_content_id = mysqli_insert_id($conn); // Ambil ID Konten yang baru dibuat

            // 2. INSERT RELASI GENRE ke tabel content_genres
            if (!empty($genres)) {
                $sql_genre_values = [];
                foreach ($genres as $genre_id) {
                    $sql_genre_values[] = "('$new_content_id', '$genre_id')";
                }
                $query_genres = "INSERT INTO content_genres (content_id, genre_id) VALUES " . implode(', ', $sql_genre_values);
                mysqli_query($conn, $query_genres);
            }
            
            echo "<script>alert('Konten dan Genre Berhasil Ditambah!'); window.location='index.php';</script>";
        } else {
            echo "Error DB: " . mysqli_error($conn);
        }
    } else {
        echo "Gagal Upload Gambar!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Tambah Konten</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="admin-content">
        <div class="page-header">
            <h2>Tambah Judul Baru</h2>
        </div>

        <form method="POST" enctype="multipart/form-data">
            
            <div class="form-group">
                <label>Judul Film / Series / Event</label>
                <input type="text" name="title" required placeholder="Contoh: Avengers Endgame">
            </div>

            <div class="form-group">
                <label>Tipe Konten</label>
                <select name="type">
                    <option value="movie">Movie</option>
                    <option value="series">TV Series</option>
                    <option value="sport">Sports</option>
                </select>
            </div>
            
            <div style="display:flex; gap: 30px;">
                <div style="flex:1;">
                    <div class="form-group">
                        <label>Sutradara</label>
                        <input type="text" name="director" placeholder="Contoh: Christopher Nolan">
                    </div>
                    <div class="form-group">
                        <label>Pemeran Utama (Pisahkan dengan koma)</label>
                        <textarea name="actors" rows="3" placeholder="Contoh: Robert Downey Jr, Chris Evans"></textarea>
                    </div>
                </div>
                <div style="flex:1;">
                    <div class="form-group">
                        <label>Tanggal Rilis</label>
                        <input type="date" name="release_date" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label>Rating (0 - 10)</label>
                        <input type="number" step="0.1" name="rating" value="8.0">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>Genre</label>
                <div style="display:flex; flex-wrap:wrap; gap: 15px; background:#222; padding:15px; border-radius:4px;">
                    <?php while($genre = mysqli_fetch_assoc($q_genres)): ?>
                        <div style="display:flex; align-items:center;">
                            <input type="checkbox" id="genre_<?php echo $genre['id']; ?>" name="genres[]" value="<?php echo $genre['id']; ?>" style="width:auto; margin-right:5px;">
                            <label for="genre_<?php echo $genre['id']; ?>" style="margin-bottom:0; color:white; font-weight:normal;">
                                <?php echo $genre['name']; ?>
                            </label>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <div class="form-group">
                <label>Upload Poster Landscape (Thumbnail)</label>
                <input type="file" name="poster" required>
            </div>

            <div class="form-group">
                <label>Sinopsis / Deskripsi</label>
                <textarea name="description" rows="5"></textarea>
            </div>

            <button type="submit" name="submit" class="btn-save">SIMPAN KONTEN</button>
        </form>
    </div>
</body>
</html>