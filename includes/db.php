<?php
$host = "localhost";
$user = "root";
$pass = "123";
$db   = "securestream"; // Ganti dengan nama database Anda

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi Gagal: " . mysqli_connect_error());
}
?>