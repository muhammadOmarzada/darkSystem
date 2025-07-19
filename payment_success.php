<?php
session_start();

// Check if payment success data exists
if (!isset($_SESSION['payment_success'])) {
    header('Location: tiket.php');
    exit;
}

$success_data = $_SESSION['payment_success'];
$booking = $success_data['booking'];
$user_info = $success_data['user_info'];
$transaction_id = $success_data['transaction_id'];

// Clear success data after displaying
unset($_SESSION['payment_success']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Berhasil - Dark System</title>
    <link rel="stylesheet" href="tiket.css">
    <link rel="stylesheet" href="payment_success.css">
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

    <div class="success-container">
        <div class="success-icon">✓</div>
        <h1 class="success-title">Pembayaran Berhasil!</h1>
        <p>Terima kasih! Pembayaran Anda telah berhasil diproses.</p>
        
        <div class="transaction-info">
            <h3 style="margin-top: 0; color: #0a122a;">Detail Transaksi</h3>
            <div class="info-row">
                <span>ID Transaksi:</span>
                <span><strong><?php echo $transaction_id; ?></strong></span>
            </div>
            <div class="info-row">
                <span>Nama Pemesan:</span>
                <span><?php echo htmlspecialchars($user_info['nama']); ?></span>
            </div>
            <div class="info-row">
                <span>Email:</span>
                <span><?php echo htmlspecialchars($user_info['email']); ?></span>
            </div>
            <div class="info-row">
                <span>No. Telepon:</span>
                <span><?php echo htmlspecialchars($booking['no_telepon']); ?></span>
            </div>
            <div class="info-row">
                <span>Jenis Tiket:</span>
                <span><?php echo ucfirst($booking['ticket_type']); ?></span>
            </div>
            <div class="info-row">
                <span>Jumlah Tiket:</span>
                <span><?php echo $booking['quantity']; ?> tiket</span>
            </div>
            <div class="info-row">
                <span>Metode Pembayaran:</span>
                <span>GoPay</span>
            </div>
            <div class="info-row highlight">
                <span>Total Dibayar:</span>
                <span>Rp <?php echo number_format($booking['total_price'], 0, ',', '.'); ?></span>
            </div>
        </div>
        
        <div class="important-note">
            <strong>Penting:</strong> Simpan ID transaksi ini sebagai bukti pembelian. Tunjukkan ID transaksi dan identitas diri saat memasuki venue pertandingan.
        </div>
        
        <div class="action-buttons">
            <a href="tiket.php" class="btn btn-primary">Beli Tiket Lagi</a>
            <a href="home.php" class="btn btn-secondary">Kembali ke Beranda</a>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2025 Dark System. All rights reserved.</p>
    </footer>

    <script>
    </script>
</body>
</html>
