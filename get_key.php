<?php
session_start();


if (true) { 
    
    $key_file = 'video/enc.key'; 
    
    // Header Wajib untuk HLS
    header('Cache-Control: no-cache, no-store, must-revalidate'); 
    header('Pragma: no-cache');
    header('Expires: 0');
    header('Content-Type: application/octet-stream');
    header('Access-Control-Allow-Origin: *'); 
    header('Content-Length: ' . filesize($key_file));

    readfile($key_file);
    
} else {
    // Baris ini seharusnya tidak akan pernah dieksekusi selama testing
    http_response_code(403); 
    die("Akses kunci ditolak.");
}
?>