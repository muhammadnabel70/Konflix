<?php
session_start();
include 'includes/db.php';
$current_page = 'profile';

if(!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

// Ambil data user terbaru dari DB
$uid = $_SESSION['user_id'];
$q = mysqli_query($conn, "SELECT * FROM users WHERE id = $uid");
$user = mysqli_fetch_assoc($q);

// Cek status langganan
$is_premium = ($user['is_subscribed'] == 1 && strtotime($user['subscription_end']) > time());
$status_label = $is_premium ? '<span class="badge-premium">PREMIUM AKTIF</span>' : '<span class="badge-free">FREE PLAN</span>';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Profil Saya - KONFLIX</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .profile-box {
            background: #222; padding: 40px; border-radius: 10px;
            max-width: 600px; margin: 50px auto; text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        .avatar-circle {
            width: 100px; height: 100px; background: #E50914; color: white;
            font-size: 40px; line-height: 100px; border-radius: 50%; margin: 0 auto 20px;
        }
        .info-row { margin-bottom: 15px; font-size: 18px; color: #ccc; }
        .info-label { font-weight: bold; color: #fff; margin-right: 10px; }
        
        .badge-premium { background: gold; color: black; padding: 5px 10px; border-radius: 4px; font-weight: bold; font-size: 12px; }
        .badge-free { background: #555; color: white; padding: 5px 10px; border-radius: 4px; font-size: 12px; }
        
        .btn-action { 
            display: inline-block; padding: 10px 20px; margin: 10px; 
            border-radius: 5px; text-decoration: none; font-weight: bold; 
        }
        .btn-logout { background: #333; color: #E50914; border: 1px solid #E50914; }
        .btn-logout:hover { background: #E50914; color: white; }
        .btn-upgrade { background: #E50914; color: white; animation: pulse 2s infinite; }
        
        @keyframes pulse { 0% { transform: scale(1); } 50% { transform: scale(1.05); } 100% { transform: scale(1); } }
    </style>
</head>
<body>
<div class="app-container">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="profile-box">
            <div class="avatar-circle">
                <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
            </div>
            
            <h2>Halo, <?php echo htmlspecialchars($user['username']); ?></h2>
            <div style="margin-bottom: 30px;"><?php echo $status_label; ?></div>
            
            <div class="info-row">
                <span class="info-label">Email:</span> <?php echo htmlspecialchars($user['email']); ?>
            </div>
            
            <?php if($is_premium): ?>
                <div class="info-row">
                    <span class="info-label">Berakhir pada:</span> 
                    <?php echo date('d M Y', strtotime($user['subscription_end'])); ?>
                </div>
            <?php else: ?>
                <p style="color:#aaa; margin-bottom:20px;">Nikmati ribuan film tanpa batas dengan Premium.</p>
                <a href="subscribe.php" class="btn-action btn-upgrade">BERLANGGANAN SEKARANG</a>
            <?php endif; ?>
            
            <hr style="border: 0; border-top: 1px solid #444; margin: 30px 0;">
            
            <a href="logout.php" class="btn-action btn-logout" onclick="return confirm('Yakin ingin keluar?');">
                <i class="fa-solid fa-right-from-bracket"></i> Keluar
            </a>
        </div>
    </div>
</div>
</body>
</html>