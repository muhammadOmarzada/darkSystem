<?php
session_start();
require_once 'config.php';

// Cek apakah user sudah login
if (!isset($_SESSION['akun_id'])) {
    header("Location: loginDashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    $errors = [];
    
    // Validasi input
    if (empty($username)) {
        $errors[] = "Username tidak boleh kosong";
    } elseif (strlen($username) < 3) {
        $errors[] = "Username minimal 3 karakter";
    }
    
    if (empty($email)) {
        $errors[] = "Email tidak boleh kosong";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid";
    }
    
    // Cek apakah username sudah digunakan user lain
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id_akun FROM akun WHERE username = ? AND id_akun != ?");
        $stmt->execute([$username, $_SESSION['akun_id']]);
        if ($stmt->fetch()) {
            $errors[] = "Username sudah digunakan user lain";
        }
    }
    
    // Cek apakah email sudah digunakan user lain
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id_akun FROM akun WHERE email = ? AND id_akun != ?");
        $stmt->execute([$email, $_SESSION['akun_id']]);
        if ($stmt->fetch()) {
            $errors[] = "Email sudah digunakan user lain";
        }
    }
    
    // Validasi password jika ingin mengubah
    if (!empty($new_password) || !empty($current_password)) {
        if (empty($current_password)) {
            $errors[] = "Password saat ini harus diisi untuk mengubah password";
        } else {
            // Verifikasi password saat ini
            $stmt = $pdo->prepare("SELECT password FROM akun WHERE id_akun = ?");
            $stmt->execute([$_SESSION['akun_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!password_verify($current_password, $user['password'])) {
                $errors[] = "Password saat ini salah";
            }
        }
        
        if (empty($new_password)) {
            $errors[] = "Password baru tidak boleh kosong";
        } elseif (strlen($new_password) < 6) {
            $errors[] = "Password baru minimal 6 karakter";
        }
        
        if ($new_password !== $confirm_password) {
            $errors[] = "Konfirmasi password tidak cocok";
        }
    }
    
    if (empty($errors)) {
        // Update data
        if (!empty($new_password)) {
            // Update dengan password baru
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE akun SET username = ?, email = ?, password = ? WHERE id_akun = ?");
            $result = $stmt->execute([$username, $email, $hashed_password, $_SESSION['akun_id']]);
        } else {
            // Update tanpa password
            $stmt = $pdo->prepare("UPDATE akun SET username = ?, email = ? WHERE id_akun = ?");
            $result = $stmt->execute([$username, $email, $_SESSION['akun_id']]);
        }
        
        if ($result) {
            // Update session
            $_SESSION['username'] = $username;
            $_SESSION['email'] = $email;
            $_SESSION['success'] = "Profil berhasil diperbarui!";
            header("Location: dashboardUser.php");
            exit();
        } else {
            $errors[] = "Terjadi kesalahan saat memperbarui profil";
        }
    }
    
    $_SESSION['errors'] = $errors;
    header("Location: dashboardUser.php");
    exit();
}
?>
