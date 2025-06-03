<?php
require_once 'config.php';
checkSession();
checkAdmin();

// Ambil parameter tanggal
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : $date;
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : $date;

// Query data absensi
$query = "SELECT a.*, u.nama_lengkap, u.username, u.email, u.no_telp,
          p.nama_umkm, p.jam_masuk as jam_masuk_kantor, p.jam_pulang as jam_pulang_kantor
          FROM absensi a 
          JOIN users u ON a.user_id = u.id 
          JOIN pengaturan p ON p.id = 1
          WHERE a.tanggal BETWEEN '$start_date' AND '$end_date'
          ORDER BY a.tanggal DESC, u.nama_lengkap";

$result = $conn->query($query);

// Set header untuk download Excel
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=Laporan_Absensi_" . $start_date . "_sampai_" . $end_date . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

// Ambil nama UMKM
$query_umkm = "SELECT nama_umkm FROM pengaturan LIMIT 1";
$result_umkm = $conn->query($query_umkm);
$umkm = $result_umkm->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
        }
        th, td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #4CAF50;
            color: white;
            font-weight: bold;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .summary {
            margin: 20px 0;
        }
        .late {
            background-color: #ffcccc;
        }
        .ontime {
            background-color: #ccffcc;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>LAPORAN ABSENSI KARYAWAN</h2>
        <h3><?php echo strtoupper($umkm['nama_umkm']); ?></h3>
        <p>Periode: <?php echo formatTanggalIndonesia($start_date); ?> s/d <?php echo formatTanggalIndonesia($end_date); ?></p>
    </div>
    
    <?php
    // Hitung statistik
    $total_hari = (strtotime($end_date) - strtotime($start_date)) / (60 * 60 * 24) + 1;
    $query_stats = "SELECT 
                    COUNT(DISTINCT user_id) as total_karyawan,
                    COUNT(CASE WHEN status_masuk = 'terlambat' THEN 1 END) as total_terlambat,
                    COUNT(CASE WHEN status_masuk = 'tepat_waktu' THEN 1 END) as total_tepat_waktu
                    FROM absensi 
                    WHERE tanggal BETWEEN '$start_date' AND '$end_date'";
    $result_stats = $conn->query($query_stats);
    $stats = $result_stats->fetch_assoc();
    ?>
    
    <div class="summary">
        <table style="width: auto;">
            <tr>
                <td><strong>Total Hari Kerja:</strong></td>
                <td><?php echo $total_hari; ?> hari</td>
            </tr>
            <tr>
                <td><strong>Total Karyawan Hadir:</strong></td>
                <td><?php echo $stats['total_karyawan']; ?> orang</td>
            </tr>
            <tr>
                <td><strong>Total Tepat Waktu:</strong></td>
                <td><?php echo $stats['total_tepat_waktu']; ?> kali</td>
            </tr>
            <tr>
                <td><strong>Total Terlambat:</strong></td>
                <td><?php echo $stats['total_terlambat']; ?> kali</td>
            </tr>
        </table>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Nama Karyawan</th>
                <th>Username</th>
                <th>Email</th>
                <th>No. Telepon</th>
                <th>Jam Masuk</th>
                <th>Jam Pulang</th>
                <th>Status</th>
                <th>Keterlambatan</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            if ($result->num_rows > 0):
                while($row = $result->fetch_assoc()): 
                    $jam_masuk_kantor = strtotime($row['jam_masuk_kantor']);
                    $jam_masuk_karyawan = strtotime($row['jam_masuk']);
                    $selisih_menit = ($jam_masuk_karyawan - $jam_masuk_kantor) / 60;
                    $class = $row['status_masuk'] == 'terlambat' ? 'late' : 'ontime';
            ?>
            <tr class="<?php echo $class; ?>">
                <td><?php echo $no++; ?></td>
                <td><?php echo formatTanggalIndonesia($row['tanggal']); ?></td>
                <td><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
                <td><?php echo htmlspecialchars($row['username']); ?></td>
                <td><?php echo htmlspecialchars($row['email'] ?? '-'); ?></td>
                <td><?php echo htmlspecialchars($row['no_telp'] ?? '-'); ?></td>
                <td><?php echo $row['jam_masuk'] ? date('H:i', strtotime($row['jam_masuk'])) : '-'; ?></td>
                <td><?php echo $row['jam_pulang'] ? date('H:i', strtotime($row['jam_pulang'])) : '-'; ?></td>
                <td><?php echo $row['status_masuk'] == 'terlambat' ? 'Terlambat' : 'Tepat Waktu'; ?></td>
                <td>
                    <?php 
                    if ($row['status_masuk'] == 'terlambat' && $selisih_menit > 0) {
                        echo round($selisih_menit) . ' menit';
                    } else {
                        echo '-';
                    }
                    ?>
                </td>
                <td><?php echo htmlspecialchars($row['keterangan'] ?? '-'); ?></td>
            </tr>
            <?php 
                endwhile;
            endif; 
            ?>
        </tbody>
    </table>
    
    <div style="margin-top: 30px;">
        <p><strong>Dicetak pada:</strong> <?php echo date('d F Y H:i:s'); ?></p>
        <p><strong>Dicetak oleh:</strong> <?php echo $_SESSION['nama_lengkap']; ?></p>
    </div>
</body>
</html>