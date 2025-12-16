<?php
session_start();
include 'includes/db.php';

if(isset($_POST['register'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $email    = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    
    // Hash Password (Wajib untuk keamanan)
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Cek duplikat
    $check = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username' OR email = '$email'");
    if(mysqli_num_rows($check) > 0) {
        $error = "Username atau Email sudah terdaftar!";
    } else {
        $query = "INSERT INTO users (username, email, password) VALUES ('$username', '$email', '$hashed_password')";
        if(mysqli_query($conn, $query)) {
            echo "<script>alert('Registrasi Berhasil! Silakan Login.'); window.location='login.php';</script>";
        } else {
            $error = "Gagal mendaftar.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Daftar - KONFLIX</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .auth-container { 
            height: 100vh; display: flex; align-items: center; justify-content: center; 
            background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.8)), url('assets/img/hero-bg.jpg');
            background-size: cover;
        }
        .auth-box { 
            background: rgba(0,0,0,0.75); padding: 60px; border-radius: 10px; width: 450px; 
            box-shadow: 0 0 20px rgba(0,0,0,0.5);
        }
        .auth-title { font-size: 32px; font-weight: bold; margin-bottom: 30px; }
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
            <h1 class="auth-title">Daftar</h1>
            <?php if(isset($error)) echo "<p style='color:red; margin-bottom:20px;'>$error</p>"; ?>
            
            <form method="POST">
                <input type="text" name="username" class="form-control" placeholder="Username" required>
                <input type="email" name="email" class="form-control" placeholder="Email" required>
                <input type="password" name="password" class="form-control" placeholder="Password" required>
                <button type="submit" name="register" class="btn-submit">Daftar</button>
            </form>
            
            <span class="auth-link">Sudah punya akun? <a href="login.php">Masuk sekarang.</a></span>
        </div>
    </div>
</body>
</html>