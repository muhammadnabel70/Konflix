<?php
include '../includes/db.php';

// --- LOGIKA POST (BAN USER DENGAN ALASAN) ---
if(isset($_POST['confirm_ban'])) {
    $uid = (int)$_POST['ban_uid'];
    $reason = mysqli_real_escape_string($conn, $_POST['ban_reason']);
    
    // Update status jadi banned dan isi alasannya
    mysqli_query($conn, "UPDATE users SET status='banned', ban_reason='$reason' WHERE id=$uid");
    
    echo "<script>alert('User berhasil dibanned!'); window.location='users.php';</script>";
    exit;
}

// --- LOGIKA GET (UNBAN / UNSUB) ---
if(isset($_GET['action']) && isset($_GET['uid'])) {
    $uid = (int)$_GET['uid'];
    $act = $_GET['action'];
    
    if($act == 'unban') {
        // Hapus status banned dan kosongkan alasan
        mysqli_query($conn, "UPDATE users SET status='active', ban_reason=NULL WHERE id=$uid");
    } elseif($act == 'unsub') {
        mysqli_query($conn, "UPDATE users SET is_subscribed=0 WHERE id=$uid");
    }
    header("Location: users.php"); exit;
}

// Ambil Data User
$q_users = mysqli_query($conn, "SELECT * FROM users WHERE role != 'admin' ORDER BY id DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Kelola Pengguna</title>
    <link rel="stylesheet" href="css/admin.css">
    <style>
        /* Modal Styles untuk Admin */
        .ban-modal {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.8); z-index: 2000; justify-content: center; align-items: center;
        }
        .ban-box {
            background: #222; padding: 30px; border-radius: 8px; width: 400px;
            border: 1px solid #E50914;
        }
        .ban-box h3 { margin-top: 0; color: #E50914; }
        .ban-box textarea {
            width: 100%; height: 100px; margin: 15px 0; background: #111; 
            border: 1px solid #444; color: white; padding: 10px;
        }
        .btn-confirm { background: #E50914; color: white; padding: 10px 20px; border: none; cursor: pointer; width: 100%; font-weight: bold; }
        .btn-cancel { background: #444; color: white; padding: 10px 20px; border: none; cursor: pointer; width: 100%; margin-top: 10px; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="admin-content">
        <div class="page-header">
            <h2>Manajemen Pengguna</h2>
        </div>

        <table class="content-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Status Langganan</th>
                    <th>Status Akun</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while($u = mysqli_fetch_assoc($q_users)): ?>
                    <?php 
                        $is_sub = ($u['is_subscribed'] == 1 && strtotime($u['subscription_end']) > time());
                    ?>
                    <tr>
                        <td><?php echo $u['id']; ?></td>
                        <td>
                            <?php echo $u['username']; ?><br>
                            <small style="color:#777;"><?php echo $u['email']; ?></small>
                        </td>
                        <td>
                            <?php if($is_sub): ?>
                                <span style="color:#0f0; font-weight:bold;">Premium</span>
                            <?php else: ?>
                                <span style="color:#777;">Free</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($u['status'] == 'banned'): ?>
                                <span style="color:red; font-weight:bold;">BANNED</span>
                                <br><small style="color:#aaa;">Reason: <?php echo $u['ban_reason']; ?></small>
                            <?php else: ?>
                                <span style="color:#0f0;">Active</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($is_sub): ?>
                                <a href="?action=unsub&uid=<?php echo $u['id']; ?>" class="action-btn" style="background:orange;">Matikan Premium</a>
                            <?php endif; ?>
                            
                            <?php if($u['status'] == 'active'): ?>
                                <button class="action-btn" style="background:red; border:none; cursor:pointer;" 
                                        onclick="openBanModal(<?php echo $u['id']; ?>, '<?php echo $u['username']; ?>')">
                                    BAN USER
                                </button>
                            <?php else: ?>
                                <a href="?action=unban&uid=<?php echo $u['id']; ?>" class="action-btn" style="background:green;">UN-BAN</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <div class="ban-modal" id="banModal">
        <div class="ban-box">
            <h3>Ban User: <span id="banUsername"></span></h3>
            <form method="POST">
                <input type="hidden" name="ban_uid" id="banUid">
                <label>Alasan Banned:</label>
                <textarea name="ban_reason" required placeholder="Contoh: Melanggar aturan komunitas / Pembayaran ilegal"></textarea>
                <button type="submit" name="confirm_ban" class="btn-confirm">KONFIRMASI BAN</button>
                <button type="button" class="btn-cancel" onclick="closeBanModal()">Batal</button>
            </form>
        </div>
    </div>

    <script>
        function openBanModal(id, name) {
            document.getElementById('banUid').value = id;
            document.getElementById('banUsername').innerText = name;
            document.getElementById('banModal').style.display = 'flex';
        }
        function closeBanModal() {
            document.getElementById('banModal').style.display = 'none';
        }
    </script>
</body>
</html>