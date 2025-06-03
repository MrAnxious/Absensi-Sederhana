<?php
require_once 'config.php';
checkSession();
checkKaryawan();

// Ambil pengaturan UMKM
$query_pengaturan = "SELECT * FROM pengaturan LIMIT 1";
$result_pengaturan = $conn->query($query_pengaturan);
$pengaturan = $result_pengaturan->fetch_assoc();

// Cek absensi hari ini
$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');

$query_absensi = "SELECT * FROM absensi WHERE user_id = $user_id AND tanggal = '$today'";
$result_absensi = $conn->query($query_absensi);
$absensi_hari_ini = $result_absensi->fetch_assoc();

// Cek waktu saat ini
$current_time = date('H:i:s');
$jam_masuk = $pengaturan['jam_masuk'];
$jam_pulang = $pengaturan['jam_pulang'];

// Toleransi waktu
$toleransi_masuk = 15; // 15 menit setelah jam masuk
$toleransi_pulang = 20; // 20 menit sebelum jam pulang

// Tentukan waktu batas absensi
$batas_awal_masuk = strtotime($jam_masuk);
$batas_akhir_masuk = strtotime($jam_masuk) + ($toleransi_masuk * 60);
$batas_awal_pulang = strtotime($jam_pulang) - ($toleransi_pulang * 60);
$batas_akhir_pulang = strtotime($jam_pulang) + (2 * 3600); // 2 jam setelah jam pulang (opsional, bisa diubah)

$current_timestamp = strtotime($current_time);

// Validasi waktu absensi masuk - hanya bisa absen pada atau setelah jam masuk hingga 15 menit setelahnya
$is_jam_masuk = ($current_timestamp >= $batas_awal_masuk && $current_timestamp <= $batas_akhir_masuk);

// Validasi waktu absensi pulang - hanya bisa absen mulai 20 menit sebelum jam pulang hingga batas tertentu
$is_jam_pulang = ($current_timestamp >= $batas_awal_pulang && $current_timestamp <= $batas_akhir_pulang);

// Proses absensi masuk
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['absen_masuk'])) {
    if (!$is_jam_masuk) {
        if ($current_timestamp < $batas_awal_masuk) {
            $waktu_tunggu = date('H:i', $batas_awal_masuk);
            $error = "Absensi masuk belum bisa dilakukan! Silakan tunggu hingga jam $waktu_tunggu";
        } else {
            $error = "Waktu absensi masuk sudah berakhir! Batas waktu hingga " . date('H:i', $batas_akhir_masuk);
        }
    } else {
        $latitude = $_POST['latitude'];
        $longitude = $_POST['longitude'];
        
        // Hitung jarak dari lokasi UMKM
        $jarak = calculateDistance($latitude, $longitude, $pengaturan['latitude'], $pengaturan['longitude']);
        
        if ($jarak <= $pengaturan['radius_absen']) {
            $jam_masuk_actual = date('H:i:s');
            $status = (strtotime($jam_masuk_actual) > strtotime($pengaturan['jam_masuk'])) ? 'terlambat' : 'tepat_waktu';
            
            $query_insert = "INSERT INTO absensi (user_id, tanggal, jam_masuk, latitude_masuk, longitude_masuk, status_masuk) 
                            VALUES ($user_id, '$today', '$jam_masuk_actual', '$latitude', '$longitude', '$status')";
            
            if ($conn->query($query_insert)) {
                $success = "Absensi masuk berhasil!";
                header("Location: dashboard_karyawan.php?success=1");
                exit();
            } else {
                $error = "Gagal melakukan absensi!";
            }
        } else {
            $error = "Anda berada di luar radius absensi! Jarak Anda: " . round($jarak) . " meter";
        }
    }
}

// Proses absensi pulang
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['absen_pulang'])) {
    if (!$is_jam_pulang) {
        if ($current_timestamp < $batas_awal_pulang) {
            $waktu_tunggu = date('H:i', $batas_awal_pulang);
            $error = "Absensi pulang belum bisa dilakukan! Silakan tunggu hingga jam $waktu_tunggu";
        } else {
            $error = "Waktu absensi pulang sudah berakhir! Batas waktu hingga " . date('H:i', $batas_akhir_pulang);
        }
    } else {
        $latitude = $_POST['latitude'];
        $longitude = $_POST['longitude'];
        
        $jarak = calculateDistance($latitude, $longitude, $pengaturan['latitude'], $pengaturan['longitude']);
        
        if ($jarak <= $pengaturan['radius_absen']) {
            $jam_pulang_actual = date('H:i:s');
            
            $query_update = "UPDATE absensi SET jam_pulang = '$jam_pulang_actual', latitude_pulang = '$latitude', longitude_pulang = '$longitude' 
                            WHERE user_id = $user_id AND tanggal = '$today'";
            
            if ($conn->query($query_update)) {
                $success = "Absensi pulang berhasil!";
                header("Location: dashboard_karyawan.php?success=2");
                exit();
            } else {
                $error = "Gagal melakukan absensi pulang!";
            }
        } else {
            $error = "Anda berada di luar radius absensi! Jarak Anda: " . round($jarak) . " meter";
        }
    }
}

// Ambil riwayat absensi
$query_riwayat = "SELECT * FROM absensi WHERE user_id = $user_id ORDER BY tanggal DESC LIMIT 10";
$result_riwayat = $conn->query($query_riwayat);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Karyawan - Sistem Absensi UMKM</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
        }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: clamp(12px, 3vw, 20px);
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
            flex-wrap: wrap;
            gap: 10px;
        }
        .navbar h1 { font-size: clamp(16px, 4vw, 24px); font-weight: 600; }
        .navbar .user-info { display: flex; align-items: center; gap: clamp(8px, 2vw, 15px); flex-wrap: wrap; }
        .user-info span { font-size: clamp(12px, 2.5vw, 14px); }
        .btn-logout {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: clamp(6px, 1.5vw, 8px) clamp(12px, 3vw, 16px);
            border-radius: 6px;
            text-decoration: none;
            font-size: clamp(12px, 2.5vw, 14px);
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.3);
            white-space: nowrap;
        }
        .btn-logout:hover { background: rgba(255, 255, 255, 0.3); transform: translateY(-1px); }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: clamp(10px, 3vw, 30px);
            width: 100%;
        }
        .grid { display: grid; gap: clamp(15px, 3vw, 30px); grid-template-columns: 1fr; }
        .main-content { display: flex; flex-direction: column; gap: clamp(15px, 3vw, 25px); }
        .sidebar { display: flex; flex-direction: column; gap: clamp(15px, 3vw, 25px); }
        .card {
            background: white;
            padding: clamp(15px, 4vw, 25px);
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid #e2e8f0;
            width: 100%;
            transition: all 0.3s ease;
        }
        .welcome-card { text-align: center; }
        .welcome-card h2 { color: #2d3748; margin-bottom: 8px; font-size: clamp(18px, 5vw, 28px); font-weight: 600; }
        .welcome-card p { color: #718096; font-size: clamp(14px, 3vw, 16px); }
        .time-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: clamp(12px, 3vw, 15px);
            border-radius: 8px;
            margin-top: 15px;
            font-weight: 500;
            font-size: clamp(14px, 3vw, 16px);
        }
        .absensi-card h3 { margin-bottom: 20px; color: #2d3748; font-size: clamp(18px, 4vw, 22px); font-weight: 600; }
        .location-info {
            background: #f7fafc;
            padding: clamp(12px, 3vw, 20px);
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: clamp(12px, 2.5vw, 14px);
            color: #4a5568;
            border-left: 4px solid #667eea;
            line-height: 1.6;
        }
        .status-info {
            background: #e6fffa;
            border: 1px solid #81e6d9;
            color: #234e52;
            padding: clamp(12px, 3vw, 15px);
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: clamp(12px, 2.5vw, 14px);
        }
        .time-restriction {
            background: #fff5f5;
            border: 1px solid #feb2b2;
            color: #9b2c2c;
            padding: clamp(12px, 3vw, 15px);
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: clamp(12px, 2.5vw, 14px);
            text-align: center;
        }
        .countdown {
            font-weight: bold;
            font-size: clamp(16px, 3.5vw, 18px);
            color: #e53e3e;
            margin-top: 10px;
        }
        .btn-absen {
            width: 100%;
            padding: clamp(14px, 3vw, 18px);
            border: none;
            border-radius: 10px;
            font-size: clamp(14px, 3vw, 16px);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 12px;
            position: relative;
            overflow: hidden;
            min-height: 50px;
        }
        .btn-masuk {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(72, 187, 120, 0.3);
        }
        .btn-masuk:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(72, 187, 120, 0.4); }
        .btn-pulang {
            background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(237, 137, 54, 0.3);
        }
        .btn-pulang:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(237, 137, 54, 0.4); }
        .btn-absen:disabled { opacity: 0.5; cursor: not-allowed; transform: none !important; box-shadow: none !important; }
        .alert {
            padding: clamp(12px, 3vw, 16px);
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 500;
            font-size: clamp(12px, 2.5vw, 14px);
            line-height: 1.5;
        }
        .alert-success { background: #c6f6d5; color: #276749; border: 1px solid #9ae6b4; }
        .alert-danger { background: #fed7d7; color: #9b2c2c; border: 1px solid #feb2b2; }
        .alert-warning { background: #fefcbf; color: #744210; border: 1px solid #f6e05e; }
        .table-container { overflow-x: auto; -webkit-overflow-scrolling: touch; border-radius: 8px; border: 1px solid #e2e8f0; }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
            min-width: 400px;
            background: white;
        }
        .table th, .table td { padding: clamp(8px, 2vw, 12px); text-align: left; border-bottom: 1px solid #e2e8f0; font-size: clamp(11px, 2.5vw, 14px); }
        .table th { background: #f7fafc; font-weight: 600; color: #4a5568; position: sticky; top: 0; z-index: 10; }
        .table td { color: #2d3748; }
        .badge {
            padding: clamp(3px, 1vw, 6px) clamp(6px, 2vw, 12px);
            border-radius: 12px;
            font-size: clamp(10px, 2vw, 12px);
            font-weight: 600;
            display: inline-block;
            white-space: nowrap;
        }
        .badge-success { background: #c6f6d5; color: #276749; }
        .badge-danger { background: #fed7d7; color: #9b2c2c; }
        .loading { display: none; text-align: center; margin: 15px 0; color: #667eea; font-size: clamp(12px, 2.5vw, 14px); }
        .loading.active { display: block; }
        .loading-spinner {
            width: 20px;
            height: 20px;
            border: 2px solid #e2e8f0;
            border-top: 2px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            display: inline-block;
            margin-right: 10px;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .completed-message {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            color: white;
            padding: clamp(15px, 4vw, 20px);
            border-radius: 10px;
            text-align: center;
            font-size: clamp(14px, 3vw, 16px);
            font-weight: 500;
        }
        @media (min-width: 768px) and (max-width: 1024px) {
            .grid { grid-template-columns: 1fr; gap: 25px; }
            .container { padding: 25px; }
            .navbar { flex-direction: row; }
        }
        @media (min-width: 1025px) {
            .grid { grid-template-columns: 2fr 1fr; gap: 30px; }
            .container { padding: 30px; }
            .navbar { padding: 20px 30px; }
            .card:hover { transform: translateY(-2px); box-shadow: 0 6px 25px rgba(0, 0, 0, 0.12); }
        }
        @media (max-width: 767px) {
            .navbar { flex-direction: column; text-align: center; gap: 8px; }
            .user-info { justify-content: center; }
            .container { padding: 10px; }
            .grid { gap: 15px; }
            .table { min-width: 350px; }
            .btn-absen:hover { transform: none; }
            .card:hover { transform: none; }
        }
        @media (max-width: 480px) {
            .container { padding: 8px; }
            .table { min-width: 320px; }
        }
        @media (max-width: 767px) and (orientation: landscape) {
            .navbar { flex-direction: row; padding: 8px 15px; }
        }
        @media (prefers-reduced-motion: reduce) {
            * { animation-duration: 0.01ms !important; animation-iteration-count: 1 !important; transition-duration: 0.01ms !important; }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>Sistem Absensi UMKM</h1>
        <div class="user-info">
            <span><?php echo $_SESSION['nama_lengkap']; ?></span>
            <a href="logout.php" class="btn-logout" onclick="return confirm('Yakin ingin keluar?')">Logout</a>
        </div>
    </nav>
    
    <div class="container">
        <div class="grid">
            <div class="main-content">
                <div class="welcome-card card">
                    <h2>Selamat Datang, <?php echo $_SESSION['nama_lengkap']; ?></h2>
                    <p><?php echo formatTanggalIndonesia(date('Y-m-d')); ?></p>
                    <div class="time-info">
                        <div id="current-time"><?php echo date('H:i:s'); ?> WIB</div>
                    </div>
                </div>
                
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success">
                        <?php 
                        if ($_GET['success'] == 1) echo "✓ Absensi masuk berhasil dicatat!";
                        else if ($_GET['success'] == 2) echo "✓ Absensi pulang berhasil dicatat!";
                        ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">⚠️ <?php echo $error; ?></div>
                <?php endif; ?>
                
                <div class="absensi-card card">
                    <h3>Absensi Hari Ini</h3>
                    
                    <div class="location-info">
                        <strong>📍 Lokasi UMKM:</strong> <?php echo $pengaturan['nama_umkm']; ?><br>
                        <strong>📏 Radius Absensi:</strong> <?php echo $pengaturan['radius_absen']; ?> meter<br>
                        <strong>🕐 Jam Kerja:</strong> <?php echo date('H:i', strtotime($pengaturan['jam_masuk'])); ?> - <?php echo date('H:i', strtotime($pengaturan['jam_pulang'])); ?><br>
                        <strong>⏱️ Toleransi:</strong> <?php echo $toleransi_masuk; ?> menit setelah jam masuk, <?php echo $toleransi_pulang; ?> menit sebelum jam pulang
                    </div>
                    
                    <?php if (!$is_jam_masuk && !$absensi_hari_ini): ?>
                        <div class="time-restriction">
                            <?php if ($current_timestamp < $batas_awal_masuk): ?>
                                🕐 <strong>Belum Waktunya Absen Masuk</strong><br>
                                Absensi masuk dimulai pada: <strong><?php echo date('H:i', $batas_awal_masuk); ?></strong><br>
                                <div class="countdown" id="countdown-masuk"></div>
                            <?php else: ?>
                                ⏰ <strong>Waktu Absen Masuk Sudah Berakhir</strong><br>
                                Batas waktu absen masuk: <?php echo date('H:i', $batas_akhir_masuk); ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!$is_jam_pulang && $absensi_hari_ini && !$absensi_hari_ini['jam_pulang']): ?>
                        <div class="time-restriction">
                            <?php if ($current_timestamp < $batas_awal_pulang): ?>
                                🕐 <strong>Belum Waktunya Absen Pulang</strong><br>
                                Absensi pulang dimulai pada: <strong><?php echo date('H:i', $batas_awal_pulang); ?></strong><br>
                                <div class="countdown" id="countdown-pulang"></div>
                            <?php else: ?>
                                ⏰ <strong>Waktu Absen Pulang Sudah Berakhir</strong><br>
                                Batas waktu absen pulang: <?php echo date('H:i', $batas_akhir_pulang); ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($is_jam_masuk && !$absensi_hari_ini): ?>
                        <div class="status-info">
                            ✅ <strong>Waktu Absen Masuk</strong> - Anda dapat melakukan absensi masuk sekarang
                        </div>
                    <?php elseif ($is_jam_pulang && $absensi_hari_ini && !$absensi_hari_ini['jam_pulang']): ?>
                        <div class="status-info">
                            ✅ <strong>Waktu Absen Pulang</strong> - Anda dapat melakukan absensi pulang sekarang
                        </div>
                    <?php endif; ?>
                    
                    <div id="location-status" class="alert alert-danger" style="display: none;">
                        📍 Mohon aktifkan lokasi GPS Anda untuk melakukan absensi
                    </div>
                    
                    <div class="loading" id="loading">
                        <div class="loading-spinner"></div>
                        <span>Mendapatkan lokasi Anda...</span>
                    </div>
                    
                    <form method="POST" id="form-absensi">
                        <input type="hidden" name="latitude" id="latitude">
                        <input type="hidden" name="longitude" id="longitude">
                        
                        <?php if (!$absensi_hari_ini): ?>
                            <?php if ($is_jam_masuk): ?>
                                <button type="submit" name="absen_masuk" class="btn-absen btn-masuk" id="btn-masuk" disabled>
                                    🏢 Absen Masuk
                                </button>
                            <?php else: ?>
                                <button type="button" class="btn-absen btn-masuk" disabled>
                                    🏢 Absen Masuk (Belum Waktunya)
                                </button>
                            <?php endif; ?>
                        <?php elseif ($absensi_hari_ini && !$absensi_hari_ini['jam_pulang']): ?>
                            <div class="alert alert-success" style="margin-bottom: 15px;">
                                ✅ <strong>Absen masuk berhasil</strong><br>
                                Waktu: <?php echo date('H:i', strtotime($absensi_hari_ini['jam_masuk'])); ?> WIB
                                <?php if ($absensi_hari_ini['status_masuk'] == 'terlambat'): ?>
                                    <span class="badge badge-danger">Terlambat</span>
                                <?php else: ?>
                                    <span class="badge badge-success">Tepat Waktu</span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($is_jam_pulang): ?>
                                <button type="submit" name="absen_pulang" class="btn-absen btn-pulang" id="btn-pulang" disabled>
                                    🏠 Absen Pulang
                                </button>
                            <?php else: ?>
                                <button type="button" class="btn-absen btn-pulang" disabled>
                                    🏠 Absen Pulang (Belum Waktunya)
                                </button>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="completed-message">
                                ✅ <strong>Absensi Lengkap</strong><br>
                                Masuk: <?php echo date('H:i', strtotime($absensi_hari_ini['jam_masuk'])); ?> | 
                                Pulang: <?php echo date('H:i', strtotime($absensi_hari_ini['jam_pulang'])); ?>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            
            <div class="sidebar">
                <div class="riwayat-section card">
                    <h3>Riwayat Absensi</h3>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Masuk</th>
                                    <th>Pulang</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result_riwayat->num_rows > 0): ?>
                                    <?php while($row = $result_riwayat->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo date('d/m', strtotime($row['tanggal'])); ?></td>
                                        <td><?php echo $row['jam_masuk'] ? date('H:i', strtotime($row['jam_masuk'])) : '-'; ?></td>
                                        <td><?php echo $row['jam_pulang'] ? date('H:i', strtotime($row['jam_pulang'])) : '-'; ?></td>
                                        <td>
                                            <?php if ($row['status_masuk'] == 'terlambat'): ?>
                                                <span class="badge badge-danger">Terlambat</span>
                                            <?php else: ?>
                                                <span class="badge badge-success">Tepat Waktu</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" style="text-align: center; padding: 30px; color: #718096;">
                                            📋 Belum ada riwayat absensi
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Update waktu real-time
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('id-ID') + ' WIB';
            document.getElementById('current-time').textContent = timeString;
        }
        
        setInterval(updateTime, 1000);
        
        // Countdown timer
        function updateCountdown() {
            const now = new Date();
            const currentTime = now.getHours() * 3600 + now.getMinutes() * 60 + now.getSeconds();
            
            // Countdown untuk absen masuk
            const countdownMasuk = document.getElementById('countdown-masuk');
            if (countdownMasuk) {
                const jamMasuk = <?php echo strtotime($jam_masuk) - strtotime('00:00:00'); ?>;
                const selisih = jamMasuk - currentTime;
                
                if (selisih > 0) {
                    const jam = Math.floor(selisih / 3600);
                    const menit = Math.floor((selisih % 3600) / 60);
                    const detik = selisih % 60;
                    countdownMasuk.textContent = `${jam.toString().padStart(2, '0')}:${menit.toString().padStart(2, '0')}:${detik.toString().padStart(2, '0')}`;
                } else {
                    location.reload();
                }
            }
            
            // Countdown untuk absen pulang
            const countdownPulang = document.getElementById('countdown-pulang');
            if (countdownPulang) {
                const jamPulang = <?php echo strtotime($jam_pulang) - strtotime('00:00:00') - ($toleransi_pulang * 60); ?>;
                const selisih = jamPulang - currentTime;
                
                if (selisih > 0) {
                    const jam = Math.floor(selisih / 3600);
                    const menit = Math.floor((selisih % 3600) / 60);
                    const detik = selisih % 60;
                    countdownPulang.textContent = `${jam.toString().padStart(2, '0')}:${menit.toString().padStart(2, '0')}:${detik.toString().padStart(2, '0')}`;
                } else {
                    location.reload();
                }
            }
        }
        
        setInterval(updateCountdown, 1000);
        
        // Fungsi untuk mendapatkan lokasi
        function getLocation() {
            const loading = document.getElementById('loading');
            const locationStatus = document.getElementById('location-status');
            const btnMasuk = document.getElementById('btn-masuk');
            const btnPulang = document.getElementById('btn-pulang');
            
            loading.classList.add('active');
            
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        document.getElementById('latitude').value = position.coords.latitude;
                        document.getElementById('longitude').value = position.coords.longitude;
                        
                        if (btnMasuk) btnMasuk.disabled = false;
                        if (btnPulang) btnPulang.disabled = false;
                        
                        loading.classList.remove('active');
                        locationStatus.style.display = 'none';
                    },
                    function(error) {
                        loading.classList.remove('active');
                        locationStatus.style.display = 'block';
                        
                        switch(error.code) {
                            case error.PERMISSION_DENIED:
                                locationStatus.innerHTML = "📍 Akses lokasi ditolak. Mohon izinkan akses lokasi untuk melakukan absensi.";
                                break;
                            case error.POSITION_UNAVAILABLE:
                                locationStatus.innerHTML = "📍 Informasi lokasi tidak tersedia.";
                                break;
                            case error.TIMEOUT:
                                locationStatus.innerHTML = "📍 Waktu permintaan lokasi habis.";
                                break;
                            default:
                                locationStatus.innerHTML = "📍 Error tidak diketahui saat mendapatkan lokasi.";
                        }
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 60000
                    }
                );
            } else {
                loading.classList.remove('active');
                locationStatus.style.display = 'block';
                locationStatus.innerHTML = "📍 Browser Anda tidak mendukung geolokasi.";
            }
        }
        
        window.onload = function() {
            getLocation();
            updateTime();
            updateCountdown();
        };
        
        // Validasi form sebelum submit
        document.getElementById('form-absensi').onsubmit = function(e) {
            const latitude = document.getElementById('latitude').value;
            const longitude = document.getElementById('longitude').value;
            
            if (!latitude || !longitude) {
                e.preventDefault();
                alert('Lokasi belum terdeteksi. Mohon tunggu beberapa saat.');
                getLocation();
                return false;
            }
            
            const submitBtn = e.submitter;
            const action = submitBtn.name === 'absen_masuk' ? 'masuk' : 'pulang';
            
            if (!confirm(`Yakin ingin melakukan absensi ${action}?`)) {
                e.preventDefault();
                return false;
            }
        };
    </script>
</body>
</html>
