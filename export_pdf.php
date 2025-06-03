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
          p.nama_umkm, p.alamat, p.jam_masuk as jam_masuk_kantor, p.jam_pulang as jam_pulang_kantor
          FROM absensi a 
          JOIN users u ON a.user_id = u.id 
          JOIN pengaturan p ON p.id = 1
          WHERE a.tanggal BETWEEN '$start_date' AND '$end_date'
          ORDER BY a.tanggal DESC, u.nama_lengkap";

$result = $conn->query($query);

// Ambil data UMKM
$query_umkm = "SELECT * FROM pengaturan LIMIT 1";
$result_umkm = $conn->query($query_umkm);
$umkm = $result_umkm->fetch_assoc();

// Hitung statistik
$query_stats = "SELECT 
                COUNT(DISTINCT user_id) as total_karyawan,
                COUNT(CASE WHEN status_masuk = 'terlambat' THEN 1 END) as total_terlambat,
                COUNT(CASE WHEN status_masuk = 'tepat_waktu' THEN 1 END) as total_tepat_waktu,
                COUNT(DISTINCT tanggal) as total_hari
                FROM absensi 
                WHERE tanggal BETWEEN '$start_date' AND '$end_date'";
$result_stats = $conn->query($query_stats);
$stats = $result_stats->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Absensi - <?php echo $umkm['nama_umkm']; ?></title>
    <style>
        @media print {
            body {
                margin: 0;
                padding: 20px;
            }
            
            .no-print {
                display: none !important;
            }
            
            @page {
                size: A4 landscape;
                margin: 15mm;
            }
            
            table {
                page-break-inside: auto;
            }
            
            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #333;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #333;
            padding-bottom: 20px;
        }
        
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #333;
            text-transform: uppercase;
        }
        
        .header h2 {
            margin: 5px 0;
            font-size: 20px;
            color: #555;
        }
        
        .header p {
            margin: 5px 0;
            color: #666;
        }
        
        .info-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        
        .info-box {
            flex: 1;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
            margin: 0 5px;
        }
        
        .info-box h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #555;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        
        .info-box p {
            margin: 5px 0;
            font-size: 12px;
        }
        
        .info-box strong {
            color: #333;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 11px;
        }
        
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        
        th {
            background-color: #4a5568;
            color: white;
            font-weight: bold;
            text-align: center;
        }
        
        td {
            background-color: white;
        }
        
        tr:nth-child(even) td {
            background-color: #f8f9fa;
        }
        
        .status-tepat {
            color: #28a745;
            font-weight: bold;
        }
        
        .status-terlambat {
            color: #dc3545;
            font-weight: bold;
        }
        
        .footer {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
        }
        
        .signature-box {
            text-align: center;
            width: 200px;
        }
        
        .signature-line {
            border-bottom: 1px solid #333;
            margin-top: 60px;
            margin-bottom: 5px;
        }
        
        .btn-print {
            background: #4a5568;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            margin-bottom: 20px;
        }
        
        .btn-print:hover {
            background: #2d3748;
        }
        
        .text-center {
            text-align: center;
        }
        
        .summary-table {
            width: auto;
            margin: 0 auto 20px;
        }
        
        .summary-table td {
            padding: 5px 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <button class="btn-print no-print" onclick="window.print()">🖨️ Cetak/Simpan PDF</button>
        
        <div class="header">
            <h1>Laporan Absensi Karyawan</h1>
            <h2><?php echo strtoupper($umkm['nama_umkm']); ?></h2>
            <p><?php echo $umkm['alamat']; ?></p>
            <p><strong>Periode: <?php echo formatTanggalIndonesia($start_date); ?> s/d <?php echo formatTanggalIndonesia($end_date); ?></strong></p>
        </div>
        
        <div class="info-section">
            <div class="info-box">
                <h3>Statistik Kehadiran</h3>
                <p><strong>Total Hari:</strong> <?php echo $stats['total_hari']; ?> hari</p>
                <p><strong>Total Karyawan Tercatat:</strong> <?php echo $stats['total_karyawan']; ?> orang</p>
            </div>
            <div class="info-box">
                <h3>Ketepatan Waktu</h3>
                <p><strong>Tepat Waktu:</strong> <?php echo $stats['total_tepat_waktu']; ?> kali</p>
                <p><strong>Terlambat:</strong> <?php echo $stats['total_terlambat']; ?> kali</p>
            </div>
            <div class="info-box">
                <h3>Jam Kerja</h3>
                <p><strong>Jam Masuk:</strong> <?php echo date('H:i', strtotime($umkm['jam_masuk'])); ?> WIB</p>
                <p><strong>Jam Pulang:</strong> <?php echo date('H:i', strtotime($umkm['jam_pulang'])); ?> WIB</p>
            </div>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th width="12%">Tanggal</th>
                    <th width="20%">Nama Karyawan</th>
                    <th width="10%">Username</th>
                    <th width="8%">Jam Masuk</th>
                    <th width="8%">Jam Pulang</th>
                    <th width="10%">Status</th>
                    <th width="12%">Keterlambatan</th>
                    <th width="15%">Keterangan</th>
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
                ?>
                <tr>
                    <td class="text-center"><?php echo $no++; ?></td>
                    <td class="text-center"><?php echo date('d/m/Y', strtotime($row['tanggal'])); ?></td>
                    <td><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
                    <td class="text-center"><?php echo htmlspecialchars($row['username']); ?></td>
                    <td class="text-center"><?php echo $row['jam_masuk'] ? date('H:i', strtotime($row['jam_masuk'])) : '-'; ?></td>
                    <td class="text-center"><?php echo $row['jam_pulang'] ? date('H:i', strtotime($row['jam_pulang'])) : '-'; ?></td>
                    <td class="text-center">
                        <?php if ($row['status_masuk'] == 'terlambat'): ?>
                            <span class="status-terlambat">Terlambat</span>
                        <?php else: ?>
                            <span class="status-tepat">Tepat Waktu</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
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
                else:
                ?>
                <tr>
                    <td colspan="9" class="text-center">Tidak ada data absensi pada periode ini</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <div class="footer">
            <div class="signature-box">
                <p>Mengetahui,</p>
                <p>Pimpinan UMKM</p>
                <div class="signature-line"></div>
                <p><strong>(.............................)</strong></p>
            </div>
            
            <div class="signature-box">
                <p><?php echo formatTanggalIndonesia(date('Y-m-d')); ?></p>
                <p>Dibuat oleh,</p>
                <div class="signature-line"></div>
                <p><strong><?php echo $_SESSION['nama_lengkap']; ?></strong></p>
            </div>
        </div>
    </div>
    
    <script>
        // Auto print dialog on load (optional)
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>