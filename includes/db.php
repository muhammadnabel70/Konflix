<?php
// --- SECURITY: Matikan Error Reporting (Pencegahan Information Disclosure) ---
// Sesuai panduan keamanan poin 4: "Matikan error handling" agar hacker tidak melihat path file.
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1); // Error tetap dicatat di file log server (tidak di layar)

$servername = "localhost";
$username = "root";
$password = "123";
$dbname = "securestream";

$conn = mysqli_connect($servername, $username, $password, $dbname);

if (!$conn) {
    // Pesan generik agar tidak membocorkan detail teknis
    die("Koneksi ke sistem gagal. Silakan hubungi admin.");
}
// ... kode koneksi database di atas ...

// --- SECURITY: ID OBFUSCATION ---
require_once 'Hashids.php';

// Kunci Rahasia (Ganti dengan string acak panjang)
$my_secret_salt = "K0nFl1x_S3cur3_Str34m_Pr0j3ct_2025"; 

// Inisialisasi Objek
$hasher = new Hashids($my_secret_salt, 10);

/* Cara Pakai:
   Enkripsi: $code = $hasher->encode(10);  -> Output: "MzB8Sz..."
   Dekripsi: $ids = $hasher->decode("MzB8Sz..."); -> Output: [10]
*/
?>

