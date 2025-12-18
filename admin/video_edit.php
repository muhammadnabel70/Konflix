<?php
session_start();
include '../includes/db.php';


// --- 2. VALIDASI ID VIDEO ---
if(!isset($_GET['id'])) {
    header("Location: video_manage.php");
    exit;
}

$id = (int)$_GET['id'];
$error = "";
$success = "";

// --- 3. PROSES SIMPAN DATA (UPDATE) ---
if(isset($_POST['update_video'])) {
    $content_id = (int)$_POST['content_id'];
    $ep_num     = (int)$_POST['episode_number'];
    $ep_title   = mysqli_real_escape_string($conn, $_POST['episode_title']);
    $duration   = mysqli_real_escape_string($conn, $_POST['duration']);
    $path       = mysqli_real_escape_string($conn, $_POST['file_path_m3u8']);
    $sub        = mysqli_real_escape_string($conn, $_POST['subtitle_path']);

    if(empty($path) || empty($content_id)) {
        $error = "Judul Induk dan Path File wajib diisi!";
    } else {
        $sql_update = "UPDATE videos SET 
                       content_id = '$content_id',
                       episode_number = '$ep_num',
                       episode_title = '$ep_title',
                       duration = '$duration',
                       file_path_m3u8 = '$path',
                       subtitle_path = '$sub'
                       WHERE id = $id";
                       
        if(mysqli_query($conn, $sql_update)) {
            $success = "Video berhasil diperbarui!";
            // Refresh data agar yang tampil adalah data terbaru
        } else {
            $error = "Gagal update: " . mysqli_error($conn);
        }
    }
}

// --- 4. AMBIL DATA VIDEO LAMA ---
$q_video = mysqli_query($conn, "SELECT * FROM videos WHERE id = $id");
$data = mysqli_fetch_assoc($q_video);

if(!$data) {
    echo "Video tidak ditemukan.";
    exit;
}

// --- 5. AMBIL LIST KONTEN (Untuk Dropdown) ---
$q_contents = mysqli_query($conn, "SELECT id, title, type FROM contents ORDER BY title ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Video - KONFLIX Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* CSS Admin Style (Sama dengan video_manage.php) */
        body { background: #141414; color: white; font-family: 'Segoe UI', sans-serif; display: flex; margin: 0; }
        
        .admin-sidebar { 
            width: 240px; background: #000; height: 100vh; position: fixed; 
            border-right: 1px solid #333; padding-top: 20px;
        }
        .brand { 
            padding: 20px; font-weight: bold; font-size: 20px; color: #E50914; 
            text-align: center; border-bottom: 1px solid #333; margin-bottom: 20px;
        }
        .menu-item { 
            display: block; padding: 15px 25px; color: #b3b3b3; text-decoration: none; transition: 0.3s;
        }
        .menu-item:hover { background: #1f1f1f; color: white; }
        .menu-item.active { border-left: 4px solid #E50914; color: white; background: #1f1f1f; }

        .main-content { margin-left: 240px; padding: 40px; width: 100%; max-width: 900px; }
        
        /* Form Styling */
        .form-container { background: #1f1f1f; padding: 30px; border-radius: 8px; border: 1px solid #333; }
        h2 { border-bottom: 1px solid #333; padding-bottom: 15px; margin-top: 0; margin-bottom: 25px; }
        
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: bold; color: #ccc; }
        
        input[type="text"], input[type="number"], select {
            width: 100%; padding: 12px; background: #141414; 
            border: 1px solid #444; color: white; border-radius: 4px; box-sizing: border-box;
        }
        input:focus, select:focus { border-color: #E50914; outline: none; }
        
        .row { display: flex; gap: 20px; }
        .col { flex: 1; }

        .btn { padding: 12px 25px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; color: white; font-size: 16px; text-decoration: none; display: inline-block; }
        .btn-save { background: #E50914; }
        .btn-save:hover { background: #c11119; }
        .btn-cancel { background: #444; margin-left: 10px; }
        .btn-cancel:hover { background: #555; }

        .alert { padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .alert-success { background: #28a745; color: white; }
        .alert-error { background: #dc3545; color: white; }
        
        small { color: #777; font-size: 12px; display: block; margin-top: 5px; }
    </style>
</head>
<body>

    <div class="admin-sidebar">
        <div class="brand">ADMIN PANEL</div>
        <a href="index.php" class="menu-item">Dashboard</a>
        <a href="content_add.php" class="menu-item">+ Tambah Konten</a>
        <a href="video_add.php" class="menu-item">+ Upload Video/Eps</a>
        <a href="content_manage.php" class="menu-item">Kelola Konten</a>
        <a href="video_manage.php" class="menu-item active">Kelola Video</a>
    </div>

    <div class="main-content">
        
        <?php if($success): ?>
            <div class="alert alert-success">
                <i class="fa-solid fa-check-circle"></i> <?php echo $success; ?>
                <a href="video_manage.php" style="color:white; font-weight:bold; margin-left:10px;">Kembali ke List</a>
            </div>
        <?php endif; ?>

        <?php if($error): ?>
            <div class="alert alert-error">
                <i class="fa-solid fa-triangle-exclamation"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <h2><i class="fa-solid fa-pen-to-square"></i> Edit Video</h2>

            <form method="POST">
                
                <div class="form-group">
                    <label>Termasuk dalam Film / Serial:</label>
                    <select name="content_id" required>
                        <option value="">-- Pilih Judul --</option>
                        <?php while($c = mysqli_fetch_assoc($q_contents)): ?>
                            <option value="<?php echo $c['id']; ?>" 
                                <?php echo ($data['content_id'] == $c['id']) ? 'selected' : ''; ?>>
                                [<?php echo strtoupper($c['type']); ?>] <?php echo $c['title']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="row">
                    <div class="col">
                        <div class="form-group">
                            <label>Nomor Episode (Angka):</label>
                            <input type="number" name="episode_number" value="<?php echo $data['episode_number']; ?>" required>
                            <small>Isi <b>1</b> jika ini adalah Film (Movie).</small>
                        </div>
                    </div>
                    
                    <div class="col">
                        <div class="form-group">
                            <label>Durasi:</label>
                            <input type="text" name="duration" value="<?php echo $data['duration']; ?>" placeholder="Contoh: 1h 45m">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Judul Episode (Opsional untuk Movie):</label>
                    <input type="text" name="episode_title" value="<?php echo $data['episode_title']; ?>" placeholder="Misal: Pilot, The End Game...">
                </div>

                <div class="form-group">
                    <label>Lokasi File Video (M3U8):</label>
                    <input type="text" name="file_path_m3u8" value="<?php echo $data['file_path_m3u8']; ?>" required>
                    <small>Contoh: <code>video/film/avengers/playlist.m3u8</code></small>
                </div>

                <div class="form-group">
                    <label>Lokasi Subtitle (VTT) - Opsional:</label>
                    <input type="text" name="subtitle_path" value="<?php echo $data['subtitle_path']; ?>">
                    <small>Contoh: <code>assets/subs/sub_avengers.vtt</code></small>
                </div>

                <div style="margin-top: 30px;">
                    <button type="submit" name="update_video" class="btn btn-save">
                        <i class="fa-solid fa-save"></i> Simpan Perubahan
                    </button>
                    <a href="video_manage.php" class="btn btn-cancel">Batal</a>
                </div>

            </form>
        </div>
    </div>

</body>
</html>