<?php
session_start();
require_once 'config.php';

// Cek apakah user sudah login
if (!isset($_SESSION['akun_id'])) {
    header("Location: loginDashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    $errors = [];
    
    try {
        switch ($action) {
            case 'add':
                $deskripsi_pertandingan = trim($_POST['deskripsi_pertandingan']);
                $id_tim1 = (int)$_POST['id_tim1'];
                $id_tim2 = (int)$_POST['id_tim2'];
                $jadwal = $_POST['jadwal'];
                $jam = $_POST['jam'];
                $jumlah_reguler = (int)$_POST['jumlah_reguler'];
                $harga_reguler = (int)$_POST['harga_reguler'];
                $jumlah_vip = (int)$_POST['jumlah_vip'];
                $harga_vip = (int)$_POST['harga_vip'];
                
                // Validasi input
                if (empty($deskripsi_pertandingan)) {
                    $errors[] = "Deskripsi pertandingan harus diisi";
                }
                
                if ($id_tim1 <= 0) {
                    $errors[] = "Tim 1 harus dipilih";
                }
                
                if ($id_tim2 <= 0) {
                    $errors[] = "Tim 2 harus dipilih";
                }
                
                if ($id_tim1 == $id_tim2) {
                    $errors[] = "Tim 1 dan Tim 2 tidak boleh sama";
                }
                
                if (empty($jadwal)) {
                    $errors[] = "Tanggal pertandingan harus diisi";
                }
                
                if (empty($jam)) {
                    $errors[] = "Jam pertandingan harus diisi";
                }
                
                if ($jumlah_reguler < 0 || $harga_reguler < 0 || $jumlah_vip < 0 || $harga_vip < 0) {
                    $errors[] = "Jumlah dan harga tiket tidak boleh negatif";
                }
                
                // Cek apakah kedua tim ada di database
                if (empty($errors)) {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tim WHERE id_tim IN (?, ?)");
                    $stmt->execute([$id_tim1, $id_tim2]);
                    if ($stmt->fetchColumn() != 2) {
                        $errors[] = "Salah satu atau kedua tim yang dipilih tidak valid";
                    }
                }
                
                if (empty($errors)) {
                    // Begin transaction
                    $pdo->beginTransaction();
                    
                    try {
                        // Insert tiket reguler
                        $stmt = $pdo->prepare("INSERT INTO tiket_reguler (jumlah, harga) VALUES (?, ?)");
                        $stmt->execute([$jumlah_reguler, $harga_reguler]);
                        $id_tiket_reguler = $pdo->lastInsertId();
                        
                        // Insert tiket vip
                        $stmt = $pdo->prepare("INSERT INTO tiket_vip (jumlah, harga) VALUES (?, ?)");
                        $stmt->execute([$jumlah_vip, $harga_vip]);
                        $id_tiket_vip = $pdo->lastInsertId();
                        
                        // Insert pertandingan
                        $stmt = $pdo->prepare("INSERT INTO pertandingan (deskripsi_pertandingan, jadwal, jam, id_tiket_reguler, id_tiket_vip, id_tim1, id_tim2) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$deskripsi_pertandingan, $jadwal, $jam, $id_tiket_reguler, $id_tiket_vip, $id_tim1, $id_tim2]);
                        
                        $pdo->commit();
                        $_SESSION['success'] = "Pertandingan berhasil ditambahkan!";
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        $errors[] = "Gagal menambahkan pertandingan: " . $e->getMessage();
                    }
                }
                break;
                
            case 'edit':
                $id_pertandingan = (int)$_POST['id_pertandingan'];
                $deskripsi_pertandingan = trim($_POST['deskripsi_pertandingan']);
                $id_tim1 = (int)$_POST['id_tim1'];
                $id_tim2 = (int)$_POST['id_tim2'];
                $jadwal = $_POST['jadwal'];
                $jam = $_POST['jam'];
                $jumlah_reguler = (int)$_POST['jumlah_reguler'];
                $harga_reguler = (int)$_POST['harga_reguler'];
                $jumlah_vip = (int)$_POST['jumlah_vip'];
                $harga_vip = (int)$_POST['harga_vip'];
                
                // Validasi input
                if (empty($deskripsi_pertandingan)) {
                    $errors[] = "Deskripsi pertandingan harus diisi";
                }
                
                if ($id_tim1 <= 0) {
                    $errors[] = "Tim 1 harus dipilih";
                }
                
                if ($id_tim2 <= 0) {
                    $errors[] = "Tim 2 harus dipilih";
                }
                
                if ($id_tim1 == $id_tim2) {
                    $errors[] = "Tim 1 dan Tim 2 tidak boleh sama";
                }
                
                if (empty($jadwal)) {
                    $errors[] = "Tanggal pertandingan harus diisi";
                }
                
                if (empty($jam)) {
                    $errors[] = "Jam pertandingan harus diisi";
                }
                
                if ($jumlah_reguler < 0 || $harga_reguler < 0 || $jumlah_vip < 0 || $harga_vip < 0) {
                    $errors[] = "Jumlah dan harga tiket tidak boleh negatif";
                }
                
                // Cek apakah kedua tim ada di database
                if (empty($errors)) {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tim WHERE id_tim IN (?, ?)");
                    $stmt->execute([$id_tim1, $id_tim2]);
                    if ($stmt->fetchColumn() != 2) {
                        $errors[] = "Salah satu atau kedua tim yang dipilih tidak valid";
                    }
                }
                
                if (empty($errors)) {
                    // Begin transaction
                    $pdo->beginTransaction();
                    
                    try {
                        // Get current tiket IDs
                        $stmt = $pdo->prepare("SELECT id_tiket_reguler, id_tiket_vip FROM pertandingan WHERE id_pertandingan = ?");
                        $stmt->execute([$id_pertandingan]);
                        $current_data = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if (!$current_data) {
                            throw new Exception("Pertandingan tidak ditemukan");
                        }
                        
                        // Update tiket reguler
                        $stmt = $pdo->prepare("UPDATE tiket_reguler SET jumlah = ?, harga = ? WHERE id_tiket_reguler = ?");
                        $stmt->execute([$jumlah_reguler, $harga_reguler, $current_data['id_tiket_reguler']]);
                        
                        // Update tiket vip
                        $stmt = $pdo->prepare("UPDATE tiket_vip SET jumlah = ?, harga = ? WHERE id_tiket_vip = ?");
                        $stmt->execute([$jumlah_vip, $harga_vip, $current_data['id_tiket_vip']]);
                        
                        // Update pertandingan
                        $stmt = $pdo->prepare("UPDATE pertandingan SET deskripsi_pertandingan = ?, jadwal = ?, jam = ?, id_tim1 = ?, id_tim2 = ? WHERE id_pertandingan = ?");
                        $stmt->execute([$deskripsi_pertandingan, $jadwal, $jam, $id_tim1, $id_tim2, $id_pertandingan]);
                        
                        $pdo->commit();
                        $_SESSION['success'] = "Pertandingan berhasil diperbarui!";
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        $errors[] = "Gagal memperbarui pertandingan: " . $e->getMessage();
                    }
                }
                break;
                
            case 'delete':
                $id_pertandingan = (int)$_POST['id_pertandingan'];
                
                if ($id_pertandingan > 0) {
                    // Begin transaction
                    $pdo->beginTransaction();
                    
                    try {
                        // Get tiket IDs before deleting
                        $stmt = $pdo->prepare("SELECT id_tiket_reguler, id_tiket_vip FROM pertandingan WHERE id_pertandingan = ?");
                        $stmt->execute([$id_pertandingan]);
                        $tiket_data = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($tiket_data) {
                            // Delete pertandingan first (due to foreign key constraints)
                            $stmt = $pdo->prepare("DELETE FROM pertandingan WHERE id_pertandingan = ?");
                            $stmt->execute([$id_pertandingan]);
                            
                            // Delete related tiket records
                            $stmt = $pdo->prepare("DELETE FROM tiket_reguler WHERE id_tiket_reguler = ?");
                            $stmt->execute([$tiket_data['id_tiket_reguler']]);
                            
                            $stmt = $pdo->prepare("DELETE FROM tiket_vip WHERE id_tiket_vip = ?");
                            $stmt->execute([$tiket_data['id_tiket_vip']]);
                        }
                        
                        $pdo->commit();
                        $_SESSION['success'] = "Pertandingan berhasil dihapus!";
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        $errors[] = "Gagal menghapus pertandingan: " . $e->getMessage();
                    }
                } else {
                    $errors[] = "ID pertandingan tidak valid";
                }
                break;
                
            default:
                $errors[] = "Aksi tidak valid";
                break;
        }
        
    } catch (PDOException $e) {
        $errors[] = "Database error: " . $e->getMessage();
    }
    
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
    }
}

header("Location: dashboardTiket.php");
exit();
?>
