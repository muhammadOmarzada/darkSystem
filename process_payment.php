<?php
require_once 'config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['confirm_payment'])) {
    header('Location: tiket.php');
    exit;
}

// Check if booking data exists
if (!isset($_SESSION['booking_data'])) {
    header('Location: tiket.php');
    exit;
}

$booking = $_SESSION['booking_data'];

try {
    $pdo->beginTransaction();
    
    // Verify user exists and get current information
    $stmt = $pdo->prepare("SELECT id_user, nama, email FROM user WHERE id_user = ?");
    $stmt->execute([$booking['user_id']]);
    $user_info = $stmt->fetch();
    
    if (!$user_info) {
        throw new Exception('User tidak ditemukan! Silakan pesan ulang.');
    }
    
    // Log for debugging (optional - can be removed in production)
    error_log("Processing payment for user_id: " . $booking['user_id'] . ", email: " . $user_info['email']);
    
    // Verify match and ticket availability again
    $stmt = $pdo->prepare("
        SELECT 
            p.id_pertandingan,
            tr.jumlah as jumlah_reguler,
            tv.jumlah as jumlah_vip,
            p.id_tiket_reguler,
            p.id_tiket_vip
        FROM pertandingan p
        LEFT JOIN tiket_reguler tr ON p.id_tiket_reguler = tr.id_tiket_reguler
        LEFT JOIN tiket_vip tv ON p.id_tiket_vip = tv.id_tiket_vip
        WHERE p.id_pertandingan = ?
    ");
    $stmt->execute([$booking['match_id']]);
    $match = $stmt->fetch();
    
    if (!$match) {
        throw new Exception('Pertandingan tidak ditemukan!');
    }
    
    // Check ticket availability
    if ($booking['ticket_type'] === 'reguler') {
        if ($booking['quantity'] > $match['jumlah_reguler']) {
            throw new Exception('Tiket reguler tidak mencukupi!');
        }
        $ticket_table = 'tiket_reguler';
        $ticket_id = $match['id_tiket_reguler'];
        $new_quantity = $match['jumlah_reguler'] - $booking['quantity'];
    } else {
        if ($booking['quantity'] > $match['jumlah_vip']) {
            throw new Exception('Tiket VIP tidak mencukupi!');
        }
        $ticket_table = 'tiket_vip';
        $ticket_id = $match['id_tiket_vip'];
        $new_quantity = $match['jumlah_vip'] - $booking['quantity'];
    }
    
    // Insert into penjualan table for each ticket with ticket_type
    $stmt_penjualan = $pdo->prepare("INSERT INTO penjualan (id_user, id_pertandingan, ticket_type) VALUES (?, ?, ?)");
    for ($i = 0; $i < $booking['quantity']; $i++) {
        $stmt_penjualan->execute([$booking['user_id'], $booking['match_id'], $booking['ticket_type']]);
        
        // Log each insertion for debugging (optional - can be removed in production)
        error_log("Inserted penjualan record: user_id=" . $booking['user_id'] . ", match_id=" . $booking['match_id'] . ", ticket_type=" . $booking['ticket_type']);
    }
    
    // Update ticket quantity
    $stmt = $pdo->prepare("UPDATE $ticket_table SET jumlah = ? WHERE id_$ticket_table = ?");
    $stmt->execute([$new_quantity, $ticket_id]);
    
    $pdo->commit();
    
    // Store success data for confirmation page
    $_SESSION['payment_success'] = [
        'booking' => $booking,
        'user_info' => $user_info,
        'transaction_id' => 'TXN' . date('YmdHis') . rand(1000, 9999)
    ];
    
    // Clear booking data
    unset($_SESSION['booking_data']);
    
    // Redirect to success page
    header('Location: payment_success.php');
    exit;
    
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = $e->getMessage();
    header('Location: pembayaran.php');
    exit;
}
?>
