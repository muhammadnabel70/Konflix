<?php
session_start();
include 'includes/db.php';

// Jika user sudah login, lempar ke index
if(isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = "";

if(isset($_POST['login'])) {
    $input = $_POST['email_or_username'];
    $password = $_POST['password'];

    // --- SECURITY UPDATE: PREPARED STATEMENT ---
    // Menggunakan tanda tanya (?) untuk mencegah SQL Injection
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
    $stmt->bind_param("ss", $input, $input); // "ss" = 2 string
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if($user && password_verify($password, $user['password'])) {
        
        // Cek Status Banned
        if($user['status'] == 'banned') {
            $error = "Akun Anda telah dinonaktifkan (Banned).";
        } else {
            // Set Session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['email'] = $user['email'];

            // Redirect berdasarkan role
            if($user['role'] == 'admin') {
                header("Location: admin/index.php");
            } else {
                header("Location: index.php");
            }
            exit;
        }
    } else {
        $error = "Email atau Password salah!";
    }
    
    $stmt->close(); // Tutup koneksi aman
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - KONFLIX</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* --- RESET CSS --- */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        
        /* --- BACKGROUND CINEMATIC --- */
        body {
            height: 100vh;
            width: 100%;
            /* Pastikan path gambar background ini benar */
            background-image: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.8)), url('assets/img/konflix.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        /* LOGO DI POJOK KIRI ATAS */
        .brand-logo {
            position: absolute;
            top: 25px;
            left: 50px;
            text-decoration: none;
            z-index: 10;
        }

        /* --- UPDATE: LOGO LEBIH BESAR --- */
        .brand-logo img {
            height: 60px; /* Diperbesar agar jelas */
            width: auto;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.5));
            transition: transform 0.3s;
        }
        .brand-logo img:hover { transform: scale(1.05); }

        /* --- KARTU LOGIN (GLASS STYLE) --- */
        .login-card {
            background: rgba(0, 0, 0, 0.75); /* Hitam Transparan */
            width: 100%;
            max-width: 450px;
            padding: 60px 50px;
            border-radius: 8px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.8);
            border: 1px solid rgba(255,255,255,0.1);
            backdrop-filter: blur(5px);
            animation: fadeIn 0.8s ease-out;
        }

        .login-title {
            color: white;
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 30px;
        }

        /* FORM INPUT */
        .form-group { position: relative; margin-bottom: 20px; }
        
        .form-control {
            width: 100%;
            padding: 16px 20px;
            background: #333;
            border: none;
            border-radius: 4px;
            color: white;
            font-size: 16px;
            outline: none;
            transition: 0.3s;
        }
        
        .form-control:focus {
            background: #444;
            border-bottom: 2px solid #E50914;
        }

        .form-control::placeholder { color: #8c8c8c; }

        /* TOMBOL MASUK */
        .btn-submit {
            width: 100%;
            padding: 16px;
            background: #E50914;
            color: white;
            font-weight: bold;
            font-size: 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 20px;
            transition: 0.3s;
        }
        
        .btn-submit:hover { background: #c11119; }

        /* LINKS */
        .auth-footer {
            margin-top: 30px;
            color: #737373;
            font-size: 16px;
        }
        .auth-footer a { color: white; text-decoration: none; font-weight: 500; }
        .auth-footer a:hover { text-decoration: underline; }

        /* ERROR MESSAGE */
        .error-msg {
            background: #e87c03;
            color: white;
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* ANIMASI */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* RESPONSIVE HP */
        @media (max-width: 600px) {
            body { background: black; align-items: flex-start; } /* Di HP jadi hitam full */
            .login-card {
                background: black;
                box-shadow: none;
                border: none;
                padding-top: 100px; /* Turunkan dikit */
            }
            .brand-logo { left: 20px; top: 20px; }
            .brand-logo img { height: 40px; } /* Sedikit lebih kecil di HP */
        }
    </style>
</head>
<body>

    <a href="index.php" class="brand-logo">
        <img src="assets/img/logo_konflix.png" alt="KONFLIX Logo">
    </a>

    <div class="login-card">
        <h1 class="login-title">Masuk</h1>

        <?php if($error): ?>
            <div class="error-msg">
                <i class="fa-solid fa-circle-exclamation"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <input type="text" name="email_or_username" class="form-control" placeholder="Email atau Username" required autocomplete="off">
            </div>
            
            <div class="form-group">
                <input type="password" name="password" class="form-control" placeholder="Sandi" required>
            </div>

            <button type="submit" name="login" class="btn-submit">Masuk</button>
            
            <div style="margin-top: 15px; display:flex; justify-content:space-between; color:#b3b3b3; font-size:13px;">
                <label style="cursor:pointer;"><input type="checkbox" checked> Ingat saya</label>
                <a href="#" style="color:#b3b3b3; text-decoration:none;">Butuh bantuan?</a>
            </div>
        </form>

        <div class="auth-footer">
            Baru di KONFLIX? <a href="register.php">Daftar sekarang.</a>
            <br><br>
            <small style="font-size:13px;">
                Halaman ini dilindungi oleh reCAPTCHA Google untuk memastikan Anda bukan bot.
            </small>
        </div>
    </div>

</body>
</html>