<?php
session_start();
include 'includes/db.php';

if(!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$current_page = 'subscribe'; 

// --- LOGIKA PROSES PEMBAYARAN (AJAX Handler) ---
// Kita gunakan PHP di file yang sama untuk memproses POST request
if(isset($_POST['process_payment'])) {
    $uid = $_SESSION['user_id'];
    $plan = $_POST['plan_type']; // 'monthly' or 'yearly'
    
    $duration = ($plan == 'monthly') ? '+1 month' : '+1 year';
    $new_end_date = date('Y-m-d H:i:s', strtotime($duration));
    
    $update = mysqli_query($conn, "UPDATE users SET is_subscribed = 1, subscription_end = '$new_end_date' WHERE id = $uid");
    
    if($update) {
        // Return Success signal for JS
        echo "SUCCESS"; 
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Berlangganan - KONFLIX</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css"> 
    <style>
        /* --- PAGE STYLES --- */
        .pricing-header { text-align: center; margin-top: 40px; margin-bottom: 50px; }
        .pricing-header h1 { font-size: 2.5rem; margin-bottom: 10px; color:white; }
        .pricing-header p { color: #aaa; font-size: 1.1rem; }

        .pricing-container { display: flex; justify-content: center; gap: 30px; flex-wrap: wrap; padding-bottom: 50px; }

        .pricing-card {
            background: #16181f; border: 2px solid #333; border-radius: 12px;
            padding: 40px; width: 320px; text-align: center; transition: 0.3s; position: relative;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        .pricing-card:hover { transform: translateY(-10px); border-color: #E50914; box-shadow: 0 20px 40px rgba(229, 9, 20, 0.2); }

        .pricing-title { font-size: 1.5rem; font-weight: bold; margin-bottom: 20px; color: #fff; }
        .price-tag { font-size: 3rem; font-weight: bold; color: white; margin: 20px 0; }
        .price-tag span { font-size: 1rem; color: #aaa; font-weight: normal; }

        .features { list-style: none; padding: 0; text-align: left; margin: 30px 0; color: #ccc; line-height: 2; border-top: 1px solid #333; padding-top: 20px; }
        .features li { display: flex; align-items: center; gap: 10px; }
        .features i { color: #E50914; }

        .btn-select {
            display: block; width: 100%; padding: 15px; background: #E50914; color: white; font-weight: bold; 
            border: none; border-radius: 5px; cursor: pointer; transition: 0.3s; text-decoration:none;
        }
        .btn-select:hover { background: #b20710; }

        .badge-best {
            background: #FFD700; color: black; padding: 5px 15px; border-radius: 20px; 
            font-size: 12px; font-weight: bold; position: absolute; top: -15px; left: 50%; transform: translateX(-50%);
            box-shadow: 0 5px 15px rgba(255, 215, 0, 0.4);
        }

        /* --- MODAL PAYMENT STYLES --- */
        .payment-modal {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.85); z-index: 9999; justify-content: center; align-items: center;
            backdrop-filter: blur(5px);
        }
        .payment-box {
            background: #1a1d29; width: 400px; padding: 30px; border-radius: 12px;
            border: 1px solid #333; box-shadow: 0 0 50px rgba(0,0,0,0.8); text-align: left;
            position: relative; animation: slideUp 0.3s ease;
        }
        @keyframes slideUp { from {transform: translateY(20px); opacity: 0;} to {transform: translateY(0); opacity: 1;} }
        
        .close-btn { position: absolute; top: 15px; right: 20px; color: #aaa; cursor: pointer; font-size: 24px; }
        .close-btn:hover { color: white; }

        .pay-title { font-size: 1.2rem; color: white; margin-bottom: 20px; border-bottom: 1px solid #333; padding-bottom: 10px; }
        .pay-summary { background: #111; padding: 15px; border-radius: 8px; margin-bottom: 20px; color: #ddd; font-size: 14px; }
        .pay-total { font-size: 1.5rem; color: #E50914; font-weight: bold; float: right; }

        /* Metode Pembayaran */
        .payment-methods { display: grid; gap: 10px; margin-bottom: 20px; }
        .method-item {
            background: #252833; padding: 15px; border-radius: 8px; cursor: pointer;
            display: flex; align-items: center; gap: 15px; border: 2px solid transparent; transition: 0.2s;
        }
        .method-item:hover, .method-item.selected { border-color: #E50914; background: #1f222e; }
        .method-icon { font-size: 24px; color: #ccc; width: 30px; text-align: center; }
        .method-name { color: white; font-weight: bold; }

        /* Loading Overlay */
        .loading-overlay {
            position: absolute; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.9);
            display: none; flex-direction: column; justify-content: center; align-items: center; border-radius: 12px; z-index: 10;
        }
        .spinner { border: 4px solid #333; border-top: 4px solid #E50914; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin-bottom: 15px; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

        .success-message { text-align: center; display: none; }
        .success-icon { font-size: 60px; color: #0f0; margin-bottom: 15px; }
    </style>
</head>
<body>

<div class="app-container">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        
        <div class="pricing-header">
            <h1>Pilih Paket Langganan</h1>
            <p>Batalkan kapan saja. Tanpa komitmen.</p>
        </div>
        
        <div class="pricing-container">
            <div class="pricing-card">
                <div class="pricing-title">Bulanan</div>
                <div class="price-tag">Rp 49rb<span>/bln</span></div>
                <ul class="features">
                    <li><i class="fa-solid fa-check"></i> Akses Semua Film & Series</li>
                    <li><i class="fa-solid fa-check"></i> Kualitas HD (720p)</li>
                    <li><i class="fa-solid fa-check"></i> Nonton di HP/Laptop</li>
                </ul>
                <button class="btn-select" onclick="openPayment('monthly', 'Rp 49.000')">Pilih Paket</button>
            </div>

            <div class="pricing-card" style="border-color: #E50914; background: #1f222e;">
                <span class="badge-best">PALING HEMAT</span>
                <div class="pricing-title">Tahunan</div>
                <div class="price-tag">Rp 399rb<span>/thn</span></div>
                <ul class="features">
                    <li><i class="fa-solid fa-check"></i> Akses Semua Film & Series</li>
                    <li><i class="fa-solid fa-check"></i> Kualitas Full HD (1080p)</li>
                    <li><i class="fa-solid fa-check"></i> Bebas Iklan</li>
                    <li><i class="fa-solid fa-check"></i> Hemat 30%</li>
                </ul>
                <button class="btn-select" onclick="openPayment('yearly', 'Rp 399.000')">Pilih Paket</button>
            </div>
        </div>

    </div>
</div>

<div class="payment-modal" id="paymentModal">
    <div class="payment-box">
        <div class="close-btn" onclick="closePayment()">&times;</div>
        
        <div id="paymentForm">
            <div class="pay-title">Konfirmasi Pembayaran</div>
            <div class="pay-summary">
                <span id="planName">Paket Bulanan</span>
                <span id="planPrice" class="pay-total">Rp 49.000</span>
            </div>

            <div class="payment-methods">
                <div class="method-item" onclick="selectMethod(this)">
                    <div class="method-icon"><i class="fa-solid fa-wallet"></i></div>
                    <div class="method-name">DANA / GOPAY / OVO</div>
                </div>
                <div class="method-item" onclick="selectMethod(this)">
                    <div class="method-icon"><i class="fa-solid fa-building-columns"></i></div>
                    <div class="method-name">Transfer Bank (BCA/Mandiri)</div>
                </div>
                <div class="method-item" onclick="selectMethod(this)">
                    <div class="method-icon"><i class="fa-brands fa-cc-visa"></i></div>
                    <div class="method-name">Kartu Kredit</div>
                </div>
            </div>

            <button class="btn-select" id="payButton" disabled style="opacity:0.5;" onclick="processPayment()">BAYAR SEKARANG</button>
        </div>

        <div class="loading-overlay" id="loadingView">
            <div class="spinner"></div>
            <div style="color:white;">Memproses Pembayaran...</div>
        </div>

        <div class="success-message" id="successView">
            <div class="success-icon"><i class="fa-solid fa-circle-check"></i></div>
            <h2 style="color:white; margin-bottom:10px;">Pembayaran Berhasil!</h2>
            <p style="color:#ccc; margin-bottom:20px;">Akun Anda sekarang Premium.</p>
            <a href="index.php" class="btn-select">Mulai Menonton</a>
        </div>

    </div>
</div>

<script>
    let selectedPlan = '';

    function openPayment(plan, price) {
        selectedPlan = plan;
        document.getElementById('planName').innerText = (plan == 'monthly') ? 'Paket Bulanan' : 'Paket Tahunan';
        document.getElementById('planPrice').innerText = price;
        document.getElementById('paymentModal').style.display = 'flex';
        
        // Reset UI
        document.getElementById('paymentForm').style.display = 'block';
        document.getElementById('successView').style.display = 'none';
        document.querySelectorAll('.method-item').forEach(el => el.classList.remove('selected'));
        document.getElementById('payButton').style.opacity = '0.5';
        document.getElementById('payButton').disabled = true;
    }

    function closePayment() {
        document.getElementById('paymentModal').style.display = 'none';
    }

    function selectMethod(element) {
        // Hapus selected lama
        document.querySelectorAll('.method-item').forEach(el => el.classList.remove('selected'));
        // Tambah selected baru
        element.classList.add('selected');
        // Aktifkan tombol bayar
        document.getElementById('payButton').style.opacity = '1';
        document.getElementById('payButton').disabled = false;
    }

    function processPayment() {
        // Tampilkan Loading
        document.getElementById('loadingView').style.display = 'flex';

        // Simulasi Request Server (AJAX)
        const formData = new FormData();
        formData.append('process_payment', true);
        formData.append('plan_type', selectedPlan);

        fetch('subscribe.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            // Simulasi delay agar terasa seperti proses bank
            setTimeout(() => {
                document.getElementById('loadingView').style.display = 'none';
                document.getElementById('paymentForm').style.display = 'none';
                document.getElementById('successView').style.display = 'block';
            }, 2000);
        })
        .catch(error => {
            alert("Terjadi kesalahan koneksi.");
            document.getElementById('loadingView').style.display = 'none';
        });
    }
</script>

</body>
</html>