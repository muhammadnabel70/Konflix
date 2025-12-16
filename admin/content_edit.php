<?php
include '../includes/db.php';

// Cek ID
if(!isset($_GET['id'])) { die("Pilih konten yang ingin diubah!"); }
$content_id = (int)$_GET['id'];

// --- 1. AMBIL DATA LAMA ---
$q_content = mysqli_query($conn, "SELECT * FROM contents WHERE id = $content_id");
$content = mysqli_fetch_assoc($q_content);

if(!$content) { die("Konten tidak ditemukan."); }

// Ambil Genres
$q_genres_all = mysqli_query($conn, "SELECT id, name FROM genres ORDER BY name ASC");
$q_genres_selected = mysqli_query($conn, "SELECT genre_id FROM content_genres WHERE content_id = $content_id");
$selected_genres = [];
while($row = mysqli_fetch_assoc($q_genres_selected)) { $selected_genres[] = $row['genre_id']; }


// --- 2. PROSES UPDATE ---
if(isset($_POST['submit'])) {
    
    // Ambil Data Teks
    $title      = mysqli_real_escape_string($conn, $_POST['title']);
    $desc       = mysqli_real_escape_string($conn, $_POST['description']);
    $type       = $_POST['type'];
    $rating     = $_POST['rating'];
    $director   = mysqli_real_escape_string($conn, $_POST['director']);
    $actors     = mysqli_real_escape_string($conn, $_POST['actors']);
    $release_date = $_POST['release_date'];
    $new_genres = $_POST['genres'] ?? [];

    // --- LOGIKA GANTI POSTER ---
    $poster_sql_part = ""; // Default: Tidak mengubah poster
    
    // Cek apakah user memilih file baru?
    if(!empty($_FILES['poster']['name'])) {
        $poster_name = $_FILES['poster']['name'];
        $poster_tmp  = $_FILES['poster']['tmp_name'];
        
        $target_dir = "../assets/img/";
        $target_file = $target_dir . basename($poster_name);
        $db_poster_path = "assets/img/" . basename($poster_name);
        
        // Coba Upload
        if(move_uploaded_file($poster_tmp, $target_file)) {
            // Jika sukses, tambahkan syntax SQL untuk update kolom poster
            $poster_sql_part = ", poster_landscape = '$db_poster_path'";
        } else {
            echo "<script>alert('Gagal mengupload poster baru. Perubahan lain tetap disimpan.');</script>";
        }
    }

    // QUERY UPDATE UTAMA
    // Perhatikan variabel $poster_sql_part disisipkan di tengah query
    $query_update = "UPDATE contents SET 
                     title = '$title',
                     description = '$desc',
                     type = '$type',
                     rating = '$rating',
                     director = '$director',
                     actors = '$actors',
                     release_date = '$release_date'
                     $poster_sql_part 
                     WHERE id = $content_id";
    
    if(mysqli_query($conn, $query_update)) {

        // UPDATE GENRE (Reset & Insert)
        mysqli_query($conn, "DELETE FROM content_genres WHERE content_id = $content_id");
        if (!empty($new_genres)) {
            $sql_genre_values = [];
            foreach ($new_genres as $genre_id) {
                $safe_genre_id = (int)$genre_id; 
                $sql_genre_values[] = "('$content_id', '$safe_genre_id')";
            }
            $query_genres = "INSERT INTO content_genres (content_id, genre_id) VALUES " . implode(', ', $sql_genre_values);
            mysqli_query($conn, $query_genres);
        }
        
        echo "<script>alert('Konten Berhasil Diperbarui!'); window.location='content_manage.php';</script>";
    } else {
        echo "Error DB: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Konten</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="admin-content">
        <div class="page-header">
            <h2>Edit: <?php echo htmlspecialchars($content['title']); ?></h2>
        </div>

        <form method="POST" enctype="multipart/form-data">
            
            <div class="form-group">
                <label>Judul</label>
                <input type="text" name="title" required value="<?php echo htmlspecialchars($content['title']); ?>">
            </div>

            <div class="form-group">
                <label>Tipe</label>
                <select name="type">
                    <option value="movie" <?php echo ($content['type'] == 'movie') ? 'selected' : ''; ?>>Movie</option>
                    <option value="series" <?php echo ($content['type'] == 'series') ? 'selected' : ''; ?>>TV Series</option>
                    <option value="sport" <?php echo ($content['type'] == 'sport') ? 'selected' : ''; ?>>Sports</option>
                </select>
            </div>
            
            <div style="display:flex; gap: 30px;">
                <div style="flex:1;">
                    <div class="form-group">
                        <label>Sutradara</label>
                        <input type="text" name="director" value="<?php echo htmlspecialchars($content['director'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Pemeran (Pisahkan koma)</label>
                        <textarea name="actors" rows="3"><?php echo htmlspecialchars($content['actors'] ?? ''); ?></textarea>
                    </div>
                </div>
                <div style="flex:1;">
                    <div class="form-group">
                        <label>Rilis</label>
                        <input type="date" name="release_date" value="<?php echo $content['release_date']; ?>">
                    </div>
                    <div class="form-group">
                        <label>Rating</label>
                        <input type="number" step="0.1" name="rating" value="<?php echo $content['rating']; ?>">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>Genre</label>
                <div style="display:flex; flex-wrap:wrap; gap: 15px; background:#222; padding:15px; border-radius:4px;">
                    <?php 
                    mysqli_data_seek($q_genres_all, 0); 
                    while($genre = mysqli_fetch_assoc($q_genres_all)): 
                        $checked = in_array($genre['id'], $selected_genres) ? 'checked' : '';
                    ?>
                        <div style="display:flex; align-items:center;">
                            <input type="checkbox" id="genre_<?php echo $genre['id']; ?>" name="genres[]" value="<?php echo $genre['id']; ?>" style="width:auto; margin-right:5px;" <?php echo $checked; ?>>
                            <label for="genre_<?php echo $genre['id']; ?>" style="margin-bottom:0; color:white; font-weight:normal;">
                                <?php echo $genre['name']; ?>
                            </label>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <div class="form-group">
                <label>Poster Saat Ini:</label>
                <img src="../<?php echo $content['poster_landscape']; ?>" style="max-width: 200px; display:block; margin-bottom: 10px; border: 1px solid #555;">
                
                <label style="color: #E50914;">Ganti Poster (Biarkan kosong jika tidak ingin mengubah)</label>
                <input type="file" name="poster"> 
            </div>

            <div class="form-group">
                <label>Sinopsis</label>
                <textarea name="description" rows="5"><?php echo htmlspecialchars($content['description']); ?></textarea>
            </div>

            <button type="submit" name="submit" class="btn-save">PERBARUI KONTEN</button>
        </form>
    </div>
</body>
</html>