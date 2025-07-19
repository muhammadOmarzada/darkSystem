<?php
require_once 'config.php';
session_start();

// Check if booking data exists
if (!isset($_SESSION['booking_data'])) {
    header('Location: tiket.php');
    exit;
}

$booking = $_SESSION['booking_data'];

// Get match details for display
try {
    $stmt = $pdo->prepare("
        SELECT 
            p.deskripsi_pertandingan,
            p.jadwal,
            p.jam,
            t1.nama as nama_tim1,
            t2.nama as nama_tim2
        FROM pertandingan p
        LEFT JOIN tim t1 ON p.id_tim1 = t1.id_tim
        LEFT JOIN tim t2 ON p.id_tim2 = t2.id_tim
        WHERE p.id_pertandingan = ?
    ");
    $stmt->execute([$booking['match_id']]);
    $match = $stmt->fetch();
} catch(PDOException $e) {
    header('Location: tiket.php');
    exit;
}

// Function to format Indonesian date
function formatIndonesianDate($date) {
    $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    $months = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
               'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    
    $timestamp = strtotime($date);
    $dayName = $days[date('w', $timestamp)];
    $day = date('j', $timestamp);
    $month = $months[date('n', $timestamp)];
    $year = date('Y', $timestamp);
    
    return "$dayName, $day $month $year";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran - Dark System</title>
    <link rel="stylesheet" href="tiket.css">
    <link rel="stylesheet" href="pembayaran.css">
</head>
<body>
    <nav class="navbar">
        <div class="logo"><span class="dark">DARK</span><span class="system">SYSTEM</span></div>
        <ul class="nav-links">
            <li><a href="home.php">HOME</a></li>
            <li><a href="jadwal.php">JADWAL</a></li>
            <li><a href="tiket.php">TIKET</a></li>
        </ul>
    </nav>

    <div class="payment-container">
        <h1 style="text-align: center; color: #0a122a; margin-bottom: 30px;">Pembayaran Tiket</h1>
        
        <div class="order-summary">
            <h3 style="margin-top: 0; color: #0a122a;">Ringkasan Pesanan</h3>
            <div class="summary-row">
                <span>Pertandingan:</span>
                <span><?php echo htmlspecialchars($match['nama_tim1'] . ' vs ' . $match['nama_tim2']); ?></span>
            </div>
            <div class="summary-row">
                <span>Tanggal:</span>
                <span><?php echo formatIndonesianDate($match['jadwal']); ?></span>
            </div>
            <div class="summary-row">
                <span>Waktu:</span>
                <span><?php echo date('H:i', strtotime($match['jam'])); ?> WIB</span>
            </div>
            <div class="summary-row">
                <span>Jenis Tiket:</span>
                <span><?php echo ucfirst($booking['ticket_type']); ?></span>
            </div>
            <div class="summary-row">
                <span>Jumlah:</span>
                <span><?php echo $booking['quantity']; ?> tiket</span>
            </div>
            <div class="summary-row">
                <span>Nama Pemesan:</span>
                <span><?php echo htmlspecialchars($booking['nama']); ?></span>
            </div>
            <div class="summary-row total">
                <span>Total Pembayaran:</span>
                <span>Rp <?php echo number_format($booking['total_price'], 0, ',', '.'); ?></span>
            </div>
        </div>
        
        <h3 style="color: #0a122a; margin-bottom: 20px;">Metode Pembayaran</h3>
        
        <div class="payment-method selected" id="gopay-method">
            <div class="payment-logo">
                <div class="gopay-logo border"><img src="img\gopay.png" alt=""></div>
                <div>
                    <div style="font-weight: bold;">GoPay</div>
                    <div style="color: #666; font-size: 14px;">Bayar dengan GoPay</div>
                </div>
            </div>
            <div class="payment-info">
                Pembayaran akan diproses melalui aplikasi GoPay. Pastikan saldo GoPay Anda mencukupi.
            </div>
        </div>
        
        <div id="paymentForm">
            <button type="button" class="pay-btn" onclick="processPayment()">
                Bayar dengan GoPay - Rp <?php echo number_format($booking['total_price'], 0, ',', '.'); ?>
            </button>
            <a href="tiket.php" class="cancel-btn">Batalkan Pesanan</a>
        </div>
        
        <div class="loading" id="loadingDiv">
            <div class="spinner"></div>
            <p>Memproses pembayaran...</p>
            <p style="font-size: 14px; color: #666;">Mohon tunggu, jangan tutup halaman ini.</p>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2025 Dark System. All rights reserved.</p>
    </footer>

    <script>
        
        function processPayment() {
            
            // Submit to payment processor
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'process_payment.php';
                
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'confirm_payment';
            input.value = '1';
                
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        }
    </script>
</body>
</html>
