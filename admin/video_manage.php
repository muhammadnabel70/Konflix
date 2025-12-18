<?php
include '../includes/db.php'; 
session_start();

// --- LOGIKA HAPUS (DELETE) ---
if(isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
    
    if ($del_id > 0) {
        $query_delete = "DELETE FROM videos WHERE id = $del_id";
        
        if(mysqli_query($conn, $query_delete)) {
            header("Location: video_manage.php?status=deleted");
            exit;
        } else {
            echo "<script>alert('Gagal menghapus video: " . mysqli_error($conn) . "');</script>";
        }
    }
}
// --- AKHIR LOGIKA HAPUS ---

// Definisikan halaman aktif untuk sidebar
$current_page = 'video_manage'; 

// Ambil semua data video bergabung dengan judul konten utamanya
$sql_videos = "SELECT v.*, c.title as content_title, c.type as content_type 
               FROM videos v 
               JOIN contents c ON v.content_id = c.id 
               ORDER BY v.id DESC";
$q_videos = mysqli_query($conn, $sql_videos);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Kelola Video</title>
    <link rel="stylesheet" href="css/admin.css">
    <style>
        /* Tambahan style khusus agar konsisten dengan content_manage */
        .action-btn.video { background: #007bff; }
        .action-btn.edit { background: #f0ad4e; }
        .badge { 
            padding: 2px 5px; 
            border-radius: 3px; 
            font-size: 10px; 
            font-weight: bold; 
            margin-left: 5px;
        }
        .badge-movie { background: #333; color: #fff; }
        .badge-series { background: #E50914; color: #fff; }
    </style>
</head>
<body>

    <?php include 'includes/sidebar.php'; ?>

    <div class="admin-content">
        <div class="page-header">
            <h2>Kelola Semua Video & Episode</h2>
            <a href="video_add.php" class="btn-save">+ Tambah Video Baru</a>
        </div>
        
        <?php if (isset($_GET['status']) && $_GET['status'] == 'deleted'): ?>
            <div style="background-color:#4CAF50; color:white; padding:10px; margin-bottom:15px; border-radius:5px;">
                Video berhasil dihapus dari database!
            </div>
        <?php endif; ?>

        <table class="content-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Judul Utama (Konten)</th>
                    <th>Eps</th>
                    <th>Judul Episode</th>
                    <th>Durasi</th>
                    <th>Path File</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while($video = mysqli_fetch_assoc($q_videos)): ?>
                    <tr>
                        <td><?php echo $video['id']; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($video['content_title']); ?></strong>
                            <?php if($video['content_type'] == 'movie'): ?>
                                <span class="badge badge-movie">MOVIE</span>
                            <?php else: ?>
                                <span class="badge badge-series">SERIES</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:center;">
                            <?php echo ($video['content_type'] == 'series') ? $video['episode_number'] : '-'; ?>
                        </td>
                        <td><?php echo htmlspecialchars($video['episode_title'] ?? 'Film Utama'); ?></td>
                        <td><?php echo $video['duration']; ?></td>
                        <td style="font-size: 11px; color: #777; font-family: monospace;">
                            <?php echo substr($video['file_path_m3u8'], 0, 30); ?>...
                        </td>
                        <td>
                            <a href="video_edit.php?id=<?php echo $video['id']; ?>" class="action-btn edit">Edit</a>
                            
                            <a href="?delete_id=<?php echo $video['id']; ?>" class="action-btn" style="background:#cc0000;" 
                               onclick="return confirm('Yakin ingin menghapus video/episode ini?');">
                                 Hapus
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
        <?php if(mysqli_num_rows($q_videos) == 0): ?>
            <div style="text-align: center; margin-top: 30px; color: #777;">Belum ada video yang diupload.</div>
        <?php endif; ?>
    </div>

</body>
</html>