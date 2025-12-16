<?php
include '../includes/db.php'; 

// --- LOGIKA HAPUS (DELETE) ---
if(isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
    
    // Pastikan ID valid
    if ($del_id > 0) {
        // Karena tabel videos dan content_genres terhubung dengan ON DELETE CASCADE,
        // menghapus konten utama akan otomatis menghapus semua video/genre terkait.
        $query_delete = "DELETE FROM contents WHERE id = $del_id";
        
        if(mysqli_query($conn, $query_delete)) {
            // Redirect setelah sukses menghapus
            header("Location: content_manage.php?status=deleted");
            exit;
        } else {
            echo "<script>alert('Gagal menghapus konten: " . mysqli_error($conn) . "');</script>";
        }
    }
}
// --- AKHIR LOGIKA HAPUS ---

// Definisikan halaman aktif untuk sidebar
$current_page = 'manage_content'; 

// Ambil semua konten dari database
$q_contents = mysqli_query($conn, "SELECT id, title, type, rating, director, actors, poster_landscape FROM contents ORDER BY id DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Kelola Konten</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>

    <?php include 'includes/sidebar.php'; ?>

    <div class="admin-content">
        <div class="page-header">
            <h2>Kelola Semua Konten (Film, Series, Sport)</h2>
            <a href="content_add.php" class="btn-save">+ Tambah Baru</a>
        </div>
        
        <?php if (isset($_GET['status']) && $_GET['status'] == 'deleted'): ?>
            <div style="background-color:#4CAF50; color:white; padding:10px; margin-bottom:15px; border-radius:5px;">
                Konten berhasil dihapus!
            </div>
        <?php endif; ?>

        <table class="content-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Poster</th>
                    <th>Judul</th>
                    <th>Tipe</th>
                    <th>Rating</th>
                    <th>Sutradara</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while($content = mysqli_fetch_assoc($q_contents)): ?>
                    <tr>
                        <td><?php echo $content['id']; ?></td>
                        <td>
                            <img src="../<?php echo $content['poster_landscape']; ?>" style="width: 50px; height: 30px; object-fit: cover; border-radius: 3px;">
                        </td>
                        <td><?php echo htmlspecialchars($content['title']); ?></td>
                        <td><?php echo strtoupper($content['type']); ?></td>
                        <td><?php echo $content['rating']; ?></td>
                        <td><?php echo htmlspecialchars($content['director'] ?? '-'); ?></td>
                        <td>
                            <a href="content_edit.php?id=<?php echo $content['id']; ?>" class="action-btn edit">Edit Detail</a>
                            <a href="video_add.php?c_id=<?php echo $content['id']; ?>" class="action-btn video">Kelola Video</a>
                            
                            <a href="?delete_id=<?php echo $content['id']; ?>" class="action-btn" style="background:#cc0000;" 
                               onclick="return confirm('Yakin ingin menghapus konten ini? Semua video dan data terkait akan ikut terhapus!');">
                                Hapus
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
        <?php if(mysqli_num_rows($q_contents) == 0): ?>
            <div style="text-align: center; margin-top: 30px; color: #777;">Belum ada konten yang ditambahkan.</div>
        <?php endif; ?>
    </div>

</body>
</html>