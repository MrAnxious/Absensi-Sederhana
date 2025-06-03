<?php
require_once 'config.php';
checkSession();
checkAdmin();

// Ambil pengaturan saat ini
$query = "SELECT * FROM pengaturan LIMIT 1";
$result = $conn->query($query);
$pengaturan = $result->fetch_assoc();

// Proses update pengaturan
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_umkm = mysqli_real_escape_string($conn, $_POST['nama_umkm']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];
    $radius_absen = $_POST['radius_absen'];
    $jam_masuk = $_POST['jam_masuk'];
    $jam_pulang = $_POST['jam_pulang'];
    
    $query_update = "UPDATE pengaturan SET 
                     nama_umkm = '$nama_umkm',
                     alamat = '$alamat',
                     latitude = '$latitude',
                     longitude = '$longitude',
                     radius_absen = '$radius_absen',
                     jam_masuk = '$jam_masuk',
                     jam_pulang = '$jam_pulang'
                     WHERE id = 1";
    
    if ($conn->query($query_update)) {
        $success = "Pengaturan berhasil diperbarui!";
        $result = $conn->query("SELECT * FROM pengaturan LIMIT 1");
        $pengaturan = $result->fetch_assoc();
    } else {
        $error = "Gagal memperbarui pengaturan!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan UMKM - Sistem Absensi UMKM</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background-color: #f5f7fa;
            color: #333;
        }
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            overflow-y: auto;
            transition: all 0.3s ease;
            z-index: 1000;
        }
        .sidebar-header {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            margin-bottom: 20px;
        }
        .sidebar-header h2 { font-size: 20px; margin-bottom: 5px; }
        .sidebar-header p { font-size: 14px; opacity: 0.8; }
        .nav-menu { list-style: none; }
        .nav-menu li { margin-bottom: 5px; }
        .nav-menu a {
            display: block;
            padding: 12px 15px;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-size: 15px;
        }
        .nav-menu a:hover, .nav-menu a.active { background: rgba(255, 255, 255, 0.2); }
        .main-content {
            margin-left: 250px;
            padding: 20px;
            min-height: 100vh;
            transition: all 0.3s ease;
        }
        .header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }
        .header h1 { font-size: 24px; color: #333; }
        .form-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #555; font-weight: 500; }
        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid #e1e4e8;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary {
            background: #667eea;
            color: white;
        }
        .btn-primary:hover {
            background: #5a67d8;
            transform: translateY(-2px);
        }
        .map-container { margin-bottom: 20px; }
        #map {
            height: 400px;
            width: 100%;
            border-radius: 8px;
            border: 2px solid #e1e4e8;
        }
        .info-text { font-size: 14px; color: #666; margin-top: 5px; }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-success { background: #c6f6d5; color: #276749; border: 1px solid #9ae6b4; }
        .alert-danger { background: #fed7d7; color: #9b2c2c; border: 1px solid #feb2b2; }
        .menu-toggle {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1001;
            background: #667eea;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 8px;
            cursor: pointer;
        }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .menu-toggle { display: block; }
            .form-grid { grid-template-columns: 1fr; }
        }
        .location-btn {
            background: #48bb78;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            margin-top: 10px;
            transition: all 0.3s ease;
        }
        .location-btn:hover { background: #38a169; }
        textarea.form-control { resize: vertical; min-height: 80px; }
    </style>
</head>
<body>
    <button class="menu-toggle" onclick="toggleSidebar()">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="3" y1="12" x2="21" y2="12"></line>
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <line x1="3" y1="18" x2="21" y2="18"></line>
        </svg>
    </button>
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2>Admin Panel</h2>
            <p><?php echo $_SESSION['nama_lengkap']; ?></p>
        </div>
        <ul class="nav-menu">
            <li><a href="dashboard_admin.php">Dashboard</a></li>
            <li><a href="kelola_karyawan.php">Kelola Karyawan</a></li>
            <li><a href="laporan_absensi.php">Laporan Absensi</a></li>
            <li><a href="pengaturan.php" class="active">Pengaturan UMKM</a></li>
            <li><a href="ganti_password.php">Ganti Password</a></li>
            <li><a href="logout.php" onclick="return confirm('Yakin ingin keluar?')">Logout</a></li>
        </ul>
    </div>
    <div class="main-content">
        <div class="header">
            <h1>Pengaturan UMKM</h1>
        </div>
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <div class="form-container">
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Nama UMKM <span style="color: red;">*</span></label>
                        <input type="text" name="nama_umkm" class="form-control" value="<?php echo htmlspecialchars($pengaturan['nama_umkm']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Radius Absensi (meter) <span style="color: red;">*</span></label>
                        <input type="number" name="radius_absen" class="form-control" value="<?php echo $pengaturan['radius_absen']; ?>" min="10" max="1000" required>
                        <p class="info-text">Jarak maksimal karyawan dari lokasi UMKM untuk dapat melakukan absensi</p>
                    </div>
                </div>
                <div class="form-group">
                    <label>Alamat UMKM</label>
                    <textarea name="alamat" class="form-control"><?php echo htmlspecialchars($pengaturan['alamat']); ?></textarea>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Jam Masuk <span style="color: red;">*</span></label>
                        <input type="time" name="jam_masuk" class="form-control" value="<?php echo date('H:i', strtotime($pengaturan['jam_masuk'])); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Jam Pulang <span style="color: red;">*</span></label>
                        <input type="time" name="jam_pulang" class="form-control" value="<?php echo date('H:i', strtotime($pengaturan['jam_pulang'])); ?>" required>
                    </div>
                </div>
                <div class="map-container">
                    <label>Lokasi UMKM <span style="color: red;">*</span></label>
                    <div id="map"></div>
                    <button type="button" class="location-btn" onclick="getCurrentLocation()">📍 Gunakan Lokasi Saat Ini</button>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Latitude <span style="color: red;">*</span></label>
                        <input type="text" name="latitude" id="latitude" class="form-control" value="<?php echo $pengaturan['latitude']; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Longitude <span style="color: red;">*</span></label>
                        <input type="text" name="longitude" id="longitude" class="form-control" value="<?php echo $pengaturan['longitude']; ?>" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Simpan Pengaturan</button>
            </form>
        </div>
    </div>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        let map, marker, circle;
        let radius = <?php echo $pengaturan['radius_absen']; ?>;

        function initMap() {
            let lat = parseFloat(document.getElementById('latitude').value) || -6.1754;
            let lng = parseFloat(document.getElementById('longitude').value) || 106.8272;
            if (isNaN(lat) || isNaN(lng)) {
                lat = -6.1754;
                lng = 106.8272;
            }

            map = L.map('map').setView([lat, lng], 17);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);

            marker = L.marker([lat, lng], { draggable: true }).addTo(map);
            circle = L.circle([lat, lng], {
                color: '#667eea',
                fillColor: '#667eea',
                fillOpacity: 0.2,
                radius: radius
            }).addTo(map);

            marker.on('dragend', function(e) {
                updateLocation(e.target.getLatLng().lat, e.target.getLatLng().lng);
            });

            map.on('click', function(e) {
                updateLocation(e.latlng.lat, e.latlng.lng);
            });

            document.getElementById('latitude').addEventListener('change', updateMapFromInputs);
            document.getElementById('longitude').addEventListener('change', updateMapFromInputs);

            document.querySelector('input[name="radius_absen"]').addEventListener('input', function(e) {
                const newRadius = parseInt(e.target.value);
                if (circle && newRadius >= 10 && newRadius <= 1000) {
                    circle.setRadius(newRadius);
                }
            });
        }

        function updateLocation(lat, lng) {
            document.getElementById('latitude').value = lat.toFixed(8);
            document.getElementById('longitude').value = lng.toFixed(8);
            marker.setLatLng([lat, lng]);
            circle.setLatLng([lat, lng]);
            map.setView([lat, lng], 17); // Peta mengikuti koordinat baru
        }

        function updateMapFromInputs() {
            let lat = parseFloat(document.getElementById('latitude').value);
            let lng = parseFloat(document.getElementById('longitude').value);
            if (!isNaN(lat) && !isNaN(lng)) {
                updateLocation(lat, lng);
            }
        }

        function getCurrentLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    updateLocation(position.coords.latitude, position.coords.longitude);
                }, function(error) {
                    alert('Gagal mendapatkan lokasi. Pastikan GPS aktif.');
                });
            } else {
                alert('Browser tidak mendukung geolokasi.');
            }
        }

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('active');
        }

        window.onload = initMap;
    </script>
</body>
</html>
