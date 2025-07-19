<?php
require_once 'config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: tiket.php');
    exit;
}

// Get form data
$match_id = (int)$_POST['match_id'];
$nama = trim($_POST['nama']);
$email = trim($_POST['email']);
$no_telepon = trim($_POST['no_telepon']);
$ticket_type = $_POST['ticket_type'];
$quantity = (int)$_POST['quantity'];
$total_price = (int)$_POST['total_price'];

// Validate input
if (empty($nama) || empty($email) || empty($no_telepon) || empty($ticket_type) || $quantity <= 0) {
    $_SESSION['error'] = 'Data tidak lengkap!';
    header('Location: pemesanan.php?match_id=' . $match_id);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Normalize email for comparison
    $email = strtolower(trim($email));
    
    // Check if user already exists based on email
    $stmt = $pdo->prepare("SELECT id_user, nama, no_telepon FROM user WHERE LOWER(email) = ?");
    $stmt->execute([$email]);
    $existing_user = $stmt->fetch();
    
    if ($existing_user) {
        // User exists, use existing ID and update information if different
        $user_id = $existing_user['id_user'];
        
        // Update user information if it has changed
        if ($existing_user['nama'] !== $nama || $existing_user['no_telepon'] !== $no_telepon) {
            $stmt = $pdo->prepare("UPDATE user SET nama = ?, no_telepon = ? WHERE id_user = ?");
            $stmt->execute([$nama, $no_telepon, $user_id]);
        }
    } else {
        // Insert new user
        $stmt = $pdo->prepare("INSERT INTO user (nama, email, no_telepon) VALUES (?, ?, ?)");
        $stmt->execute([$nama, $email, $no_telepon]);
        $user_id = $pdo->lastInsertId();
    }
    
    // Verify match exists and get ticket info
    $stmt = $pdo->prepare("
        SELECT 
            p.id_pertandingan,
            tr.jumlah as jumlah_reguler,
            tr.harga as harga_reguler,
            tv.jumlah as jumlah_vip,
            tv.harga as harga_vip,
            p.id_tiket_reguler,
            p.id_tiket_vip
        FROM pertandingan p
        LEFT JOIN tiket_reguler tr ON p.id_tiket_reguler = tr.id_tiket_reguler
        LEFT JOIN tiket_vip tv ON p.id_tiket_vip = tv.id_tiket_vip
        WHERE p.id_pertandingan = ?
    ");
    $stmt->execute([$match_id]);
    $match = $stmt->fetch();
    
    if (!$match) {
        throw new Exception('Pertandingan tidak ditemukan!');
    }
    
    // Check ticket availability
    if ($ticket_type === 'reguler') {
        if ($quantity > $match['jumlah_reguler']) {
            throw new Exception('Tiket reguler tidak mencukupi!');
        }
        $expected_price = $match['harga_reguler'] * $quantity;
    } else {
        if ($quantity > $match['jumlah_vip']) {
            throw new Exception('Tiket VIP tidak mencukupi!');
        }
        $expected_price = $match['harga_vip'] * $quantity;
    }
    
    // Verify price calculation
    if ($total_price !== $expected_price) {
        throw new Exception('Harga tidak sesuai!');
    }
    
    // Store booking data in session for payment
    $_SESSION['booking_data'] = [
        'user_id' => $user_id,
        'match_id' => $match_id,
        'ticket_type' => $ticket_type,
        'quantity' => $quantity,
        'total_price' => $total_price,
        'nama' => $nama,
        'email' => $email,
        'no_telepon' => $no_telepon
    ];
    
    $pdo->commit();
    
    // Redirect to payment page
    header('Location: pembayaran.php');
    exit;
    
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = $e->getMessage();
    header('Location: pemesanan.php?match_id=' . $match_id);
    exit;
}
?>
