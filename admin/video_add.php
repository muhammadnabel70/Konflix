<?php
include '../includes/db.php';

// Ambil list konten
$contents = mysqli_query($conn, "SELECT id, title, type FROM contents ORDER BY id DESC");

if(isset($_POST['submit'])) {
    $content_id = (int)$_POST['content_id'];
    $ep_title   = mysqli_real_escape_string($conn, $_POST['episode_title']);
    $ep_num     = (int)$_POST['episode_number'];
    $season_num = (int)$_POST['season_number'];
    $duration   = mysqli_real_escape_string($conn, $_POST['duration']);
    $path       = mysqli_real_escape_string($conn, $_POST['file_path']);
    
    // --- LOGIKA UPLOAD SUBTITLE (PERBAIKAN NAMA FILE) ---
    $sub_db_path = NULL; 
    
    if(!empty($_FILES['subtitle']['name'])) {
        $sub_name = $_FILES['subtitle']['name'];
        $sub_tmp  = $_FILES['subtitle']['tmp_name'];
        $ext      = strtolower(pathinfo($sub_name, PATHINFO_EXTENSION));

        if($ext != "vtt") {
            echo "<script>alert('Gagal: Subtitle harus format .vtt!'); window.history.back();</script>";
            exit;
        }

        // PERBAIKAN: Bersihkan nama file dari karakter terlarang Windows (:, ?, *, dll)
        $clean_name = preg_replace('/[^A-Za-z0-9.\-_]/', '', basename($sub_name));
        
        // Simpan File
        $target_dir = "../assets/subs/";
        
        // Cek folder, jika belum ada, coba buat (opsional, better manual)
        if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }

        $new_name   = "sub_" . time() . "_" . $clean_name; 
        $target_file = $target_dir . $new_name;

        if(move_uploaded_file($sub_tmp, $target_file)) {
            $sub_db_path = "assets/subs/" . $new_name; // Path untuk database
        } else {
            echo "<script>alert('Gagal upload file subtitle. Pastikan folder assets/subs ada dan nama file aman.');</script>";
            // Jangan exit, biarkan video tersimpan tanpa subtitle jika gagal
        }
    }
    // -------------------------------------

    if (empty($content_id) || empty($path)) {
        echo "<script>alert('Gagal: ID Konten dan Path Video wajib diisi!');</script>";
    } else {
        // Query INSERT
        $query = "INSERT INTO videos (content_id, episode_title, episode_number, season_number, duration, file_path_m3u8, subtitle_path) 
                  VALUES ('$content_id', '$ep_title', '$ep_num', '$season_num', '$duration', '$path', '$sub_db_path')";
        
        // Gunakan Try-Catch untuk menangkap error Duplicate Entry
        try {
            if(mysqli_query($conn, $query)) {
                echo "<script>alert('Video Berhasil Disimpan!'); window.location='content_manage.php';</script>";
                exit;
            }
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) { // Kode Error Duplicate Entry
                 echo "<script>alert('Gagal: Video untuk Season/Episode ini sudah ada di database! Hapus dulu jika ingin upload ulang.'); window.history.back();</script>";
            } else {
                 echo "Error DB: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Link Video & Subtitle</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <?php $current_page = 'video_add'; include 'includes/sidebar.php'; ?>

    <div class="admin-content">
        <div class="page-header">
            <h2>Link Video & Subtitle</h2>
        </div>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Pilih Judul Konten</label>
                <select name="content_id" required>
                    <option value="">-- Pilih Judul --</option>
                    <?php mysqli_data_seek($contents, 0); while($row = mysqli_fetch_assoc($contents)): ?>
                        <option value="<?php echo $row['id']; ?>">
                            [<?php echo strtoupper($row['type']); ?>] <?php echo htmlspecialchars($row['title']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div style="display:flex; gap:20px;">
                <div class="form-group" style="flex:1;">
                    <label>Season</label>
                    <input type="number" name="season_number" value="1">
                </div>
                <div class="form-group" style="flex:1;">
                    <label>Episode</label>
                    <input type="number" name="episode_number" value="1" required>
                </div>
            </div>
            
            <div class="form-group">
                <label>Judul Episode (Opsional)</label>
                <input type="text" name="episode_title">
            </div>
            
            <div class="form-group">
                <label>Durasi</label>
                <input type="text" name="duration" placeholder="1h 45m">
            </div>

            <div class="form-group">
                <label>Path Playlist (M3U8)</label>
                <input type="text" name="file_path" placeholder="video/judul/playlist.m3u8" required>
            </div>

            <div class="form-group" style="background:#333; padding:15px; border-radius:5px;">
                <label style="color:#E50914;">Upload Subtitle (.vtt)</label>
                <input type="file" name="subtitle" accept=".vtt">
                <small style="color:#aaa;">*Otomatis rename untuk menghapus karakter simbol.</small>
            </div>

            <button type="submit" name="submit" class="btn-save">SIMPAN VIDEO</button>
        </form>
    </div>
</body>
</html>