<?php
session_start();
include 'includes/db.php';
$current_page = 'profile';

if(!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$uid = $_SESSION['user_id'];
$msg = "";
$msg_type = "";

// --- LOGIKA GANTI PASSWORD ---
if(isset($_POST['change_pass'])) {
    $old_pass = $_POST['old_pass'];
    $new_pass = $_POST['new_pass'];
    $confirm_pass = $_POST['confirm_pass'];

    // Ambil password lama dari DB
    $q = mysqli_query($conn, "SELECT password FROM users WHERE id = $uid");
    $d = mysqli_fetch_assoc($q);

    if(password_verify($old_pass, $d['password'])) {
        if($new_pass === $confirm_pass) {
            if(strlen($new_pass) >= 6) {
                $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
                mysqli_query($conn, "UPDATE users SET password = '$hashed' WHERE id = $uid");
                $msg = "Password berhasil diubah!";
                $msg_type = "success";
            } else {
                $msg = "Password baru minimal 6 karakter.";
                $msg_type = "error";
            }
        } else {
            $msg = "Konfirmasi password tidak cocok.";
            $msg_type = "error";
        }
    } else {
        $msg = "Password lama salah.";
        $msg_type = "error";
    }
}

// --- AMBIL DATA USER TERBARU ---
$q_user = mysqli_query($conn, "SELECT * FROM users WHERE id = $uid");
$user = mysqli_fetch_assoc($q_user);

// Cek Status Premium
$is_premium = ($user['is_subscribed'] == 1 && strtotime($user['subscription_end']) > time());
$days_left = $is_premium ? ceil((strtotime($user['subscription_end']) - time()) / 86400) : 0;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Profil Saya - KONFLIX</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* LAYOUT GRID */
        .profile-container {
            padding: 40px;
            max-width: 1000px;
            margin: 0 auto;
        }

        .profile-header {
            display: flex; align-items: center; gap: 30px;
            background: #1f222e; padding: 40px; border-radius: 12px;
            border: 1px solid #333; margin-bottom: 30px;
            position: relative; overflow: hidden;
        }
        
        /* Background abstract decoration */
        .profile-header::after {
            content: ''; position: absolute; right: -50px; top: -50px;
            width: 200px; height: 200px; background: #E50914;
            opacity: 0.1; border-radius: 50%; blur(50px); filter: blur(40px);
        }

        .avatar-large {
            width: 120px; height: 120px; background: #E50914; 
            color: white; font-size: 50px; font-weight: bold;
            display: flex; align-items: center; justify-content: center;
            border-radius: 50%; border: 4px solid #141414;
            box-shadow: 0 5px 20px rgba(229, 9, 20, 0.4);
        }

        .user-meta h1 { margin: 0 0 10px 0; font-size: 2.5rem; }
        .user-meta p { color: #aaa; margin: 0; font-size: 1.1rem; }
        .badge-role { 
            display: inline-block; padding: 5px 12px; border-radius: 4px; 
            font-size: 12px; font-weight: bold; margin-top: 10px;
            background: #333; color: #ccc; letter-spacing: 1px;
        }

        /* GRID CONTENT */
        .dashboard-grid {
            display: grid; grid-template-columns: 1fr 1fr; gap: 30px;
        }
        
        .dash-card {
            background: #16181f; border: 1px solid #333; 
            padding: 30px; border-radius: 12px;
        }
        .card-title { font-size: 1.2rem; font-weight: bold; margin-bottom: 20px; border-bottom: 1px solid #333; padding-bottom: 15px; color:white;}

        /* Subscription Box */
        .sub-status { text-align: center; padding: 20px 0; }
        .status-text { font-size: 24px; font-weight: bold; margin-bottom: 10px; }
        .text-premium { color: #0f0; text-shadow: 0 0 10px rgba(0,255,0,0.3); }
        .text-free { color: #aaa; }
        .date-info { font-size: 14px; color: #777; margin-bottom: 20px; }
        .btn-action {
            display: block; width: 100%; padding: 12px; text-align: center;
            border-radius: 6px; font-weight: bold; text-decoration: none; transition: 0.3s;
        }
        .btn-renew { background: #E50914; color: white; }
        .btn-renew:hover { background: #b20710; }
        .btn-upgrade { background: transparent; border: 1px solid #E50914; color: #E50914; }
        .btn-upgrade:hover { background: #E50914; color: white; }

        /* Form Styles */
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; color: #ccc; margin-bottom: 8px; font-size: 14px; }
        .form-input { 
            width: 100%; padding: 12px; background: #0f0f0f; border: 1px solid #333; 
            color: white; border-radius: 4px; box-sizing: border-box;
        }
        .form-input:focus { border-color: #E50914; outline: none; }
        .btn-save { 
            background: #333; color: white; border: none; padding: 12px 20px; 
            border-radius: 4px; cursor: pointer; width: 100%; font-weight:bold;
        }
        .btn-save:hover { background: #444; }

        /* Alert */
        .alert { padding: 15px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; }
        .alert-success { background: rgba(0,255,0,0.1); border: 1px solid #0f0; color: #0f0; }
        .alert-error { background: rgba(255,0,0,0.1); border: 1px solid #f00; color: #f00; }

        @media (max-width: 768px) {
            .dashboard-grid { grid-template-columns: 1fr; }
            .profile-header { flex-direction: column; text-align: center; }
        }
    </style>
</head>
<body>

<div class="app-container">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="profile-container">
            
            <?php if($msg): ?>
                <div class="alert alert-<?php echo $msg_type; ?>">
                    <?php echo $msg; ?>
                </div>
            <?php endif; ?>

            <div class="profile-header">
                <div class="avatar-large">
                    <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                </div>
                <div class="user-meta">
                    <h1><?php echo htmlspecialchars($user['username']); ?></h1>
                    <p><?php echo htmlspecialchars($user['email']); ?></p>
                    
                </div>
                <div style="margin-left: auto;">
                    <a href="logout.php" onclick="return confirm('Keluar dari akun?')" class="btn-action" style="background:#222; border:1px solid #444; color:#aaa; width:auto; padding: 10px 20px;">
                        <i class="fa-solid fa-right-from-bracket"></i> Keluar
                    </a>
                </div>
            </div>

            <div class="dashboard-grid">
                
                <div class="dash-card">
                    <div class="card-title"><i class="fa-solid fa-crown"></i> Status Langganan</div>
                    
                    <div class="sub-status">
                        <?php if($is_premium): ?>
                            <div class="status-text text-premium">PREMIUM AKTIF</div>
                            <p class="date-info">
                                Berakhir pada: <br>
                                <b style="color:white; font-size:16px;"><?php echo date('d F Y', strtotime($user['subscription_end'])); ?></b>
                                <br>(<?php echo $days_left; ?> hari lagi)
                            </p>
                            <a href="subscribe.php" class="btn-action btn-renew">Perpanjang Paket</a>
                        <?php else: ?>
                            <div class="status-text text-free">FREE PLAN</div>
                            <p class="date-info">
                                Anda menggunakan akun gratis.<br>Akses terbatas pada konten tertentu.
                            </p>
                            <a href="subscribe.php" class="btn-action btn-upgrade">Upgrade ke Premium</a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="dash-card">
                    <div class="card-title"><i class="fa-solid fa-lock"></i> Ganti Password</div>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label>Password Lama</label>
                            <input type="password" name="old_pass" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label>Password Baru</label>
                            <input type="password" name="new_pass" class="form-input" required minlength="6">
                        </div>
                        <div class="form-group">
                            <label>Konfirmasi Password Baru</label>
                            <input type="password" name="confirm_pass" class="form-input" required>
                        </div>
                        <button type="submit" name="change_pass" class="btn-save">Simpan Perubahan</button>
                    </form>
                </div>

            </div>

        </div>
    </div>
</div>

</body>
</html>