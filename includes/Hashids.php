<?php
/*
    Hashids (Advanced AES Implementation)
    Mengubah ID menjadi string Hexadecimal acak menggunakan AES-256-CBC.
    Hasilnya akan terlihat acak total dan unik setiap ID.
*/

class Hashids {
    private $key;
    private $method = 'AES-256-CBC';
    private $iv;

    public function __construct($salt = '') {
        // Kita ubah Salt Anda menjadi Key 32-byte yang valid untuk AES-256
        $this->key = hash('sha256', $salt, true);
        
        // Kita buat IV (Initialization Vector) statis dari salt
        // Agar Link Video TETAP SAMA setiap kali direfresh (Deterministik)
        $this->iv = substr(hash('sha256', 'iv_vector_' . $salt), 0, 16);
    }

    public function encode($id) {
        // Enkripsi ID
        $encrypted = openssl_encrypt($id, $this->method, $this->key, 0, $this->iv);
        // Ubah jadi Hex agar aman di URL (URL Safe)
        return bin2hex(base64_decode($encrypted));
    }

    public function decode($hash) {
        // Balikkan proses: Hex -> Binary -> Decrypt
        if (!ctype_xdigit($hash)) return []; // Cek validitas hex
        
        $encrypted_data = base64_encode(hex2bin($hash));
        $decrypted = openssl_decrypt($encrypted_data, $this->method, $this->key, 0, $this->iv);
        
        if ($decrypted === false) return [];
        return [(int)$decrypted];
    }
}
?>