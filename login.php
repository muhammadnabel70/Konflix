<?php
session_start();
include 'includes/db.php';

// Jika user sudah login, arahkan ke halaman utama
if(isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

if(isset($_POST['login'])) {
    $email_or_username = mysqli_real_escape_string($conn, $_POST['email_or_username']);
    $password = $_POST['password'];

    // Cari user berdasarkan email atau username
    $q = mysqli_query($conn, "SELECT * FROM users WHERE email = '$email_or_username' OR username = '$email_or_username'");
    $user = mysqli_fetch_assoc($q);

    if($user && password_verify($password, $user['password'])) {
        // Password Benar
        
        // Set Session (Penting: Menyimpan ID dan Username untuk Watermark)
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['email'] = $user['email']; // Menyimpan Email untuk Watermark Forensik

        // Redirect berdasarkan role
        if($user['role'] == 'admin') {
            header("Location: admin/index.php");
        } else {
            header("Location: index.php");
        }
        exit;
    } else {
        $error = "Email/Username atau Password salah!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Masuk - KONFLIX</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .auth-container { 
            height: 100vh; display: flex; align-items: center; justify-content: center; 
            /* Ganti dengan URL gambar poster Anda untuk background yang lebih baik */
            background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.8)), url('assets/img/f1_thumb.jpg'); 
            background-size: cover;
        }
        .auth-box { 
            background: rgba(0,0,0,0.75); padding: 60px; border-radius: 10px; width: 450px; 
            box-shadow: 0 0 20px rgba(0,0,0,0.5);
        }
        .auth-title { 
            font-size: 32px; font-weight: bold; margin-bottom: 30px; 
            color: #E50914; /* Warna Merah KONFLIX */
        }
        .form-control { 
            width: 100%; padding: 15px; margin-bottom: 20px; background: #333; border: none; 
            border-radius: 4px; color: white; font-size: 16px;
        }
        .btn-submit { 
            width: 100%; padding: 15px; background: #E50914; color: white; font-weight: bold; 
            border: none; border-radius: 4px; cursor: pointer; font-size: 16px; margin-top: 20px;
        }
        .btn-submit:hover { background: #b20710; }
        .auth-link { color: #aaa; margin-top: 20px; display: block; font-size: 14px; }
        .auth-link a { color: white; }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <h1 class="auth-title">KONFLIX - MASUK</h1>
            <?php if(isset($error)) echo "<p style='color:red; margin-bottom:20px;'>$error</p>"; ?>
            
            <form method="POST">
                <input type="text" name="email_or_username" class="form-control" placeholder="Email atau Username" required>
                <input type="password" name="password" class="form-control" placeholder="Password" required>
                <button type="submit" name="login" class="btn-submit">Masuk</button>
            </form>
            
            <span class="auth-link">Baru di KONFLIX? <a href="register.php">Daftar sekarang.</a></span>
        </div>
    </div>
</body>
</html>