<?php
session_start();
require_once 'config.php';

// Cek apakah user sudah login
if (!isset($_SESSION['akun_id'])) {
    header("Location: loginDashboard.php");
    exit();
}

// Fungsi untuk upload file
function uploadLogo($file) {
    $uploadDir = 'uploads/logos/';
    
    // Buat direktori jika belum ada
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $errors = [];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxSize = 2 * 1024 * 1024; // 2MB
    
    // Validasi file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        if ($file['error'] === UPLOAD_ERR_NO_FILE) {
            return ['success' => true, 'path' => '']; // Tidak ada file diupload
        }
        return ['success' => false, 'error' => 'Error saat upload file'];
    }
    
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'error' => 'Tipe file tidak didukung. Gunakan JPG, PNG, atau GIF'];
    }
    
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'Ukuran file terlalu besar. Maksimal 2MB'];
    }
    
    // Generate nama file unik
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = uniqid('logo_') . '.' . $extension;
    $filePath = $uploadDir . $fileName;
    
    // Pindahkan file
    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        return ['success' => true, 'path' => $filePath];
    } else {
        return ['success' => false, 'error' => 'Gagal menyimpan file'];
    }
}

// Fungsi untuk menghapus file logo lama
function deleteOldLogo($logoPath) {
    if (!empty($logoPath) && file_exists($logoPath) && strpos($logoPath, 'uploads/logos/') === 0) {
        unlink($logoPath);
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    $errors = [];
    
    try {
        switch ($action) {
            case 'add':
                $nama = trim($_POST['nama']);
                $peringkat = (int)$_POST['peringkat'];
                $match_point = (int)($_POST['match_point'] ?? 0);
                $match_wl = trim($_POST['match_wl'] ?? '');
                $net_game_win = (int)($_POST['net_game_win'] ?? 0);
                $game_wl = trim($_POST['game_wl'] ?? '');
                $logoPath = '';
                
                // Validasi input
                if (empty($nama)) {
                    $errors[] = "Nama tim tidak boleh kosong";
                }
                
                if ($peringkat < 1) {
                    $errors[] = "Peringkat harus lebih dari 0";
                }
                
                // Cek apakah nama tim sudah ada
                if (empty($errors)) {
                    $stmt = $pdo->prepare("SELECT id_tim FROM tim WHERE nama = ?");
                    $stmt->execute([$nama]);
                    if ($stmt->fetch()) {
                        $errors[] = "Nama tim sudah ada";
                    }
                }
                
                // Cek apakah peringkat sudah digunakan
                if (empty($errors)) {
                    $stmt = $pdo->prepare("SELECT id_tim FROM tim WHERE peringkat = ?");
                    $stmt->execute([$peringkat]);
                    if ($stmt->fetch()) {
                        $errors[] = "Peringkat sudah digunakan tim lain";
                    }
                }
                
                // Handle upload logo
                if (empty($errors) && isset($_FILES['logo'])) {
                    $uploadResult = uploadLogo($_FILES['logo']);
                    if (!$uploadResult['success']) {
                        $errors[] = $uploadResult['error'];
                    } else {
                        $logoPath = $uploadResult['path'];
                    }
                }
                
                if (empty($errors)) {
                    $stmt = $pdo->prepare("INSERT INTO tim (nama, peringkat, logo, match_point, match_wl, net_game_win, game_wl) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    if ($stmt->execute([$nama, $peringkat, $logoPath, $match_point, $match_wl, $net_game_win, $game_wl])) {
                        $_SESSION['success'] = "Tim berhasil ditambahkan!";
                    } else {
                        // Hapus file yang sudah diupload jika gagal insert
                        if (!empty($logoPath)) {
                            deleteOldLogo($logoPath);
                        }
                        $errors[] = "Gagal menambahkan tim";
                    }
                }
                break;
                
            case 'edit':
                $id_tim = (int)$_POST['id_tim'];
                $nama = trim($_POST['nama']);
                $peringkat = (int)$_POST['peringkat'];
                $match_point = (int)($_POST['match_point'] ?? 0);
                $match_wl = trim($_POST['match_wl'] ?? '');
                $net_game_win = (int)($_POST['net_game_win'] ?? 0);
                $game_wl = trim($_POST['game_wl'] ?? '');
                
                // Ambil data tim saat ini
                $stmt = $pdo->prepare("SELECT logo FROM tim WHERE id_tim = ?");
                $stmt->execute([$id_tim]);
                $currentTeam = $stmt->fetch(PDO::FETCH_ASSOC);
                $logoPath = $currentTeam['logo']; // Gunakan logo lama sebagai default
                
                // Validasi input
                if (empty($nama)) {
                    $errors[] = "Nama tim tidak boleh kosong";
                }
                
                if ($peringkat < 1) {
                    $errors[] = "Peringkat harus lebih dari 0";
                }
                
                // Cek apakah nama tim sudah ada (kecuali tim yang sedang diedit)
                if (empty($errors)) {
                    $stmt = $pdo->prepare("SELECT id_tim FROM tim WHERE nama = ? AND id_tim != ?");
                    $stmt->execute([$nama, $id_tim]);
                    if ($stmt->fetch()) {
                        $errors[] = "Nama tim sudah ada";
                    }
                }
                
                // Cek apakah peringkat sudah digunakan (kecuali tim yang sedang diedit)
                if (empty($errors)) {
                    $stmt = $pdo->prepare("SELECT id_tim FROM tim WHERE peringkat = ? AND id_tim != ?");
                    $stmt->execute([$peringkat, $id_tim]);
                    if ($stmt->fetch()) {
                        $errors[] = "Peringkat sudah digunakan tim lain";
                    }
                }
                
                // Handle upload logo baru jika ada
                if (empty($errors) && isset($_FILES['logo']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
                    $uploadResult = uploadLogo($_FILES['logo']);
                    if (!$uploadResult['success']) {
                        $errors[] = $uploadResult['error'];
                    } else {
                        // Hapus logo lama jika ada
                        deleteOldLogo($currentTeam['logo']);
                        $logoPath = $uploadResult['path'];
                    }
                }
                
                if (empty($errors)) {
                    $stmt = $pdo->prepare("UPDATE tim SET nama = ?, peringkat = ?, logo = ?, match_point = ?, match_wl = ?, net_game_win = ?, game_wl = ? WHERE id_tim = ?");
                    if ($stmt->execute([$nama, $peringkat, $logoPath, $match_point, $match_wl, $net_game_win, $game_wl, $id_tim])) {
                        $_SESSION['success'] = "Tim berhasil diperbarui!";
                    } else {
                        $errors[] = "Gagal memperbarui tim";
                    }
                }
                break;
                
            case 'delete':
                $id_tim = (int)$_POST['id_tim'];
                
                if ($id_tim > 0) {
                    // Ambil path logo untuk dihapus
                    $stmt = $pdo->prepare("SELECT logo FROM tim WHERE id_tim = ?");
                    $stmt->execute([$id_tim]);
                    $team = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    $stmt = $pdo->prepare("DELETE FROM tim WHERE id_tim = ?");
                    if ($stmt->execute([$id_tim])) {
                        // Hapus file logo jika ada
                        if ($team && !empty($team['logo'])) {
                            deleteOldLogo($team['logo']);
                        }
                        $_SESSION['success'] = "Tim berhasil dihapus!";
                    } else {
                        $errors[] = "Gagal menghapus tim";
                    }
                } else {
                    $errors[] = "ID tim tidak valid";
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

header("Location: dashboardTim.php");
exit();
?>