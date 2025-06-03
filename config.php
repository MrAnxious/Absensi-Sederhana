<?php
// config.php - Konfigurasi Database
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'sistem_b';

// Koneksi Database
$conn = new mysqli($host, $username, $password, $database);

// Cek Koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Set timezone
date_default_timezone_set('Asia/Jakarta');

// Fungsi untuk cek session
function checkSession() {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}

// Fungsi untuk cek role admin
function checkAdmin() {
    if ($_SESSION['role'] != 'admin') {
        header("Location: dashboard_karyawan.php");
        exit();
    }
}

// Fungsi untuk cek role karyawan
function checkKaryawan() {
    if ($_SESSION['role'] != 'karyawan') {
        header("Location: dashboard_admin.php");
        exit();
    }
}

// Fungsi untuk menghitung jarak antara dua koordinat
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371000; // dalam meter
    
    $lat1Rad = deg2rad($lat1);
    $lat2Rad = deg2rad($lat2);
    $deltaLatRad = deg2rad($lat2 - $lat1);
    $deltaLonRad = deg2rad($lon2 - $lon1);
    
    $a = sin($deltaLatRad / 2) * sin($deltaLatRad / 2) +
         cos($lat1Rad) * cos($lat2Rad) *
         sin($deltaLonRad / 2) * sin($deltaLonRad / 2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    
    return $earthRadius * $c;
}

// Fungsi untuk format tanggal Indonesia
function formatTanggalIndonesia($tanggal) {
    $bulan = array(
        1 => 'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
    );
    
    $pecahkan = explode('-', $tanggal);
    return $pecahkan[2] . ' ' . $bulan[(int)$pecahkan[1]] . ' ' . $pecahkan[0];
}
?>