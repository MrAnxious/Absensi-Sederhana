<?php
require_once 'config.php';
checkSession();
checkAdmin();

// Set default tanggal
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Query data absensi dengan filter
$query = "SELECT a.*, u.nama_lengkap, u.username, u.email, u.no_telp 
          FROM absensi a 
          JOIN users u ON a.user_id = u.id 
          WHERE a.tanggal BETWEEN '$start_date' AND '$end_date'
          ORDER BY a.tanggal DESC, u.nama_lengkap";
$result = $conn->query($query);

// Hitung statistik
$query_stats = "SELECT 
                COUNT(DISTINCT user_id) as total_karyawan_hadir,
                COUNT(CASE WHEN status_masuk = 'terlambat' THEN 1 END) as total_terlambat,
                COUNT(CASE WHEN status_masuk = 'tepat_waktu' THEN 1 END) as total_tepat_waktu,
                COUNT(DISTINCT tanggal) as total_hari_kerja
                FROM absensi 
                WHERE tanggal BETWEEN '$start_date' AND '$end_date'";
$result_stats = $conn->query($query_stats);
$stats = $result_stats->fetch_assoc();

// Total karyawan
$query_total = "SELECT COUNT(*) as total FROM users WHERE role = 'karyawan'";
$result_total = $conn->query($query_total);
$total_karyawan = $result_total->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Absensi - Sistem Absensi UMKM</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background-color: #f5f7fa;
            color: #333;
        }
        
        /* Sidebar */
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
        
        .sidebar-header h2 {
            font-size: 20px;
            margin-bottom: 5px;
        }
        
        .sidebar-header p {
            font-size: 14px;
            opacity: 0.8;
        }
        
        .nav-menu {
            list-style: none;
        }
        
        .nav-menu li {
            margin-bottom: 5px;
        }
        
        .nav-menu a {
            display: block;
            padding: 12px 15px;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-size: 15px;
        }
        
        .nav-menu a:hover, .nav-menu a.active {
            background: rgba(255, 255, 255, 0.2);
        }
        
        /* Main Content */
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
        
        .header h1 {
            font-size: 24px;
            color: #333;
            margin-bottom: 10px;
        }
        
        /* Filter Form */
        .filter-form {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }
        
        .filter-form form {
            display: flex;
            gap: 15px;
            align-items: end;
            flex-wrap: wrap;
        }
        
        .form-group {
            flex: 1;
            min-width: 200px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
            font-size: 14px;
        }
        
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
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
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
        
        /* Statistics Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            text-align: center;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card h3 {
            font-size: 13px;
            color: #666;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stat-card .number {
            font-size: 28px;
            font-weight: bold;
            color: #333;
        }
        
        .stat-card .percentage {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        .stat-card.primary {
            border-top: 3px solid #667eea;
        }
        
        .stat-card.success {
            border-top: 3px solid #48bb78;
        }
        
        .stat-card.warning {
            border-top: 3px solid #ed8936;
        }
        
        .stat-card.info {
            border-top: 3px solid #4299e1;
        }
        
        /* Table */
        .table-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            overflow-x: auto;
        }
        
        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th, .table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .table th {
            background: #f7fafc;
            font-weight: 600;
            color: #4a5568;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .table tr:hover {
            background: #f7fafc;
        }
        
        .badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .badge-success {
            background: #c6f6d5;
            color: #276749;
        }
        
        .badge-danger {
            background: #fed7d7;
            color: #9b2c2c;
        }
        
        /* Export Buttons */
        .export-buttons {
            display: flex;
            gap: 10px;
        }
        
        .btn-success {
            background: #48bb78;
            color: white;
        }
        
        .btn-success:hover {
            background: #38a169;
        }
        
        .btn-warning {
            background: #ed8936;
            color: white;
        }
        
        .btn-warning:hover {
            background: #dd6b20;
        }
        
        /* Mobile Menu Toggle */
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
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .menu-toggle {
                display: block;
            }
            
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
            
            .filter-form form {
                flex-direction: column;
            }
            
            .form-group {
                width: 100%;
            }
        }
        
        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
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
            <li><a href="laporan_absensi.php" class="active">Laporan Absensi</a></li>
            <li><a href="pengaturan.php">Pengaturan UMKM</a></li>
            <li><a href="ganti_password.php">Ganti Password</a></li>
            <li><a href="logout.php" onclick="return confirm('Yakin ingin keluar?')">Logout</a></li>
        </ul>
    </div>
    
    <div class="main-content">
        <div class="header">
            <h1>Laporan Absensi</h1>
            <p>Pantau kehadiran dan ketepatan waktu karyawan</p>
        </div>
        
        <div class="filter-form">
            <form method="GET" action="">
                <div class="form-group">
                    <label>Tanggal Mulai</label>
                    <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>" required>
                </div>
                <div class="form-group">
                    <label>Tanggal Akhir</label>
                    <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>" required>
                </div>
                <button type="submit" class="btn btn-primary">Filter</button>
            </form>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card primary">
                <h3>Total Karyawan</h3>
                <div class="number"><?php echo $total_karyawan; ?></div>
            </div>
            <div class="stat-card info">
                <h3>Karyawan Hadir</h3>
                <div class="number"><?php echo $stats['total_karyawan_hadir']; ?></div>
                <div class="percentage">
                    <?php 
                    $persen_hadir = $total_karyawan > 0 ? round(($stats['total_karyawan_hadir'] / $total_karyawan) * 100, 1) : 0;
                    echo $persen_hadir . '% dari total';
                    ?>
                </div>
            </div>
            <div class="stat-card success">
                <h3>Tepat Waktu</h3>
                <div class="number"><?php echo $stats['total_tepat_waktu']; ?></div>
                <div class="percentage">
                    <?php 
                    $total_kehadiran = $stats['total_tepat_waktu'] + $stats['total_terlambat'];
                    $persen_tepat = $total_kehadiran > 0 ? round(($stats['total_tepat_waktu'] / $total_kehadiran) * 100, 1) : 0;
                    echo $persen_tepat . '% kehadiran';
                    ?>
                </div>
            </div>
            <div class="stat-card warning">
                <h3>Terlambat</h3>
                <div class="number"><?php echo $stats['total_terlambat']; ?></div>
                <div class="percentage">
                    <?php 
                    $persen_terlambat = $total_kehadiran > 0 ? round(($stats['total_terlambat'] / $total_kehadiran) * 100, 1) : 0;
                    echo $persen_terlambat . '% kehadiran';
                    ?>
                </div>
            </div>
        </div>
        
        <div class="table-container">
            <div class="table-header">
                <h2>Detail Absensi</h2>
                <div class="export-buttons">
                    <a href="export_excel.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" class="btn btn-success">
                        📊 Export Excel
                    </a>
                    <a href="export_pdf.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" class="btn btn-warning">
                        📄 Export PDF
                    </a>
                </div>
            </div>
            
            <table class="table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>Nama Karyawan</th>
                        <th>Username</th>
                        <th>Jam Masuk</th>
                        <th>Jam Pulang</th>
                        <th>Status</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    if ($result->num_rows > 0):
                        while($row = $result->fetch_assoc()): 
                    ?>
                    <tr>
                        <td><?php echo $no++; ?></td>
                        <td><?php echo formatTanggalIndonesia($row['tanggal']); ?></td>
                        <td><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                        <td><?php echo $row['jam_masuk'] ? date('H:i', strtotime($row['jam_masuk'])) : '-'; ?></td>
                        <td><?php echo $row['jam_pulang'] ? date('H:i', strtotime($row['jam_pulang'])) : '-'; ?></td>
                        <td>
                            <?php if ($row['status_masuk'] == 'terlambat'): ?>
                                <span class="badge badge-danger">Terlambat</span>
                            <?php else: ?>
                                <span class="badge badge-success">Tepat Waktu</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($row['keterangan'] ?? '-'); ?></td>
                    </tr>
                    <?php 
                        endwhile;
                    else:
                    ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 30px;">Tidak ada data absensi pada periode ini</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('active');
        }
        
        // Menutup sidebar saat klik di luar pada mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const menuToggle = document.querySelector('.menu-toggle');
            
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(event.target) && !menuToggle.contains(event.target)) {
                    sidebar.classList.remove('active');
                }
            }
        });
    </script>
</body>
</html>