<?php
require_once 'config.php';
checkSession();
checkAdmin();

$message = '';
$error = '';

// Proses ganti password
if (isset($_POST['ganti_password'])) {
    $password_lama = $_POST['password_lama'];
    $password_baru = $_POST['password_baru'];
    $konfirmasi_password = $_POST['konfirmasi_password'];
    
    // Validasi input
    if (empty($password_lama) || empty($password_baru) || empty($konfirmasi_password)) {
        $error = 'Semua field harus diisi!';
    } elseif ($password_baru !== $konfirmasi_password) {
        $error = 'Password baru dan konfirmasi password tidak cocok!';
    } elseif (strlen($password_baru) < 6) {
        $error = 'Password baru minimal 6 karakter!';
    } else {
        // Cek password lama
        $user_id = $_SESSION['user_id'];
        $query_check = "SELECT password FROM users WHERE id = ? AND role = 'admin'";
        $stmt_check = $conn->prepare($query_check);
        $stmt_check->bind_param("i", $user_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($result_check->num_rows > 0) {
            $user_data = $result_check->fetch_assoc();
            
            // Verifikasi password lama
            if (password_verify($password_lama, $user_data['password'])) {
                // Hash password baru
                $password_hash = password_hash($password_baru, PASSWORD_DEFAULT);
                
                // Update password
                $query_update = "UPDATE users SET password = ? WHERE id = ? AND role = 'admin'";
                $stmt_update = $conn->prepare($query_update);
                $stmt_update->bind_param("si", $password_hash, $user_id);
                
                if ($stmt_update->execute()) {
                    $message = 'Password berhasil diubah!';
                } else {
                    $error = 'Gagal mengubah password. Silakan coba lagi.';
                }
            } else {
                $error = 'Password lama tidak benar!';
            }
        } else {
            $error = 'User tidak ditemukan!';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ganti Password - Sistem Absensi UMKM</title>
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
        
        /* Sidebar - sama seperti dashboard */
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
        
        .nav-menu a:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .nav-menu a.active {
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
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 24px;
            color: #333;
        }
        
        /* Form Container */
        .form-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            max-width: 500px;
            margin: 0 auto;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
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
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
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
        
        .btn-secondary {
            background: #718096;
            color: white;
            margin-right: 10px;
        }
        
        .btn-secondary:hover {
            background: #4a5568;
        }
        
        /* Alert Messages */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 500;
        }
        
        .alert-success {
            background: #c6f6d5;
            color: #276749;
            border: 1px solid #9ae6b4;
        }
        
        .alert-danger {
            background: #fed7d7;
            color: #9b2c2c;
            border: 1px solid #fc8181;
        }
        
        .password-requirements {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
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
            
            .form-container {
                margin: 0 15px;
                padding: 20px;
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
            <li><a href="laporan_absensi.php">Laporan Absensi</a></li>
            <li><a href="pengaturan.php">Pengaturan UMKM</a></li>
            <li><a href="ganti_password.php" class="active">Ganti Password</a></li>
            <li><a href="logout.php" onclick="return confirm('Yakin ingin keluar?')">Logout</a></li>
        </ul>
    </div>
    
    <div class="main-content">
        <div class="header">
            <h1>Ganti Password</h1>
            <a href="dashboard_admin.php" class="btn btn-secondary">Kembali</a>
        </div>
        
        <div class="form-container">
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="password_lama">Password Lama</label>
                    <input type="password" class="form-control" id="password_lama" name="password_lama" required>
                </div>
                
                <div class="form-group">
                    <label for="password_baru">Password Baru</label>
                    <input type="password" class="form-control" id="password_baru" name="password_baru" required>
                    <div class="password-requirements">
                        Password minimal 6 karakter
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="konfirmasi_password">Konfirmasi Password Baru</label>
                    <input type="password" class="form-control" id="konfirmasi_password" name="konfirmasi_password" required>
                </div>
                
                <div class="form-group">
                    <button type="submit" name="ganti_password" class="btn btn-primary">Ganti Password</button>
                    <a href="dashboard_admin.php" class="btn btn-secondary">Batal</a>
                </div>
            </form>
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
        
        // Validasi konfirmasi password
        document.getElementById('konfirmasi_password').addEventListener('input', function() {
            const passwordBaru = document.getElementById('password_baru').value;
            const konfirmasiPassword = this.value;
            
            if (passwordBaru !== konfirmasiPassword && konfirmasiPassword.length > 0) {
                this.style.borderColor = '#e53e3e';
            } else {
                this.style.borderColor = '#e2e8f0';
            }
        });
    </script>
</body>
</html>
