<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $username = trim($_POST['username']);
  $email = trim($_POST['email']);
  $password = $_POST['password'];
  
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
  
  if (empty($password)) {
      $errors[] = "Password tidak boleh kosong";
  } elseif (strlen($password) < 6) {
      $errors[] = "Password minimal 6 karakter";
  }
  
  // Cek apakah email sudah terdaftar
  if (empty($errors)) {
      $stmt = $pdo->prepare("SELECT id_akun FROM akun WHERE email = ?");
      $stmt->execute([$email]);
      if ($stmt->fetch()) {
          $errors[] = "Email sudah terdaftar";
      }
  }
  
  // Cek apakah username sudah terdaftar
  if (empty($errors)) {
      $stmt = $pdo->prepare("SELECT id_akun FROM akun WHERE username = ?");
      $stmt->execute([$username]);
      if ($stmt->fetch()) {
          $errors[] = "Username sudah terdaftar";
      }
  }
  
  if (empty($errors)) {
      // Hash password
      $hashed_password = password_hash($password, PASSWORD_DEFAULT);
      
      // Insert ke database
      $stmt = $pdo->prepare("INSERT INTO akun (username, email, password) VALUES (?, ?, ?)");
      
      if ($stmt->execute([$username, $email, $hashed_password])) {
          $_SESSION['success'] = "Registrasi berhasil! Silakan login.";
          header("Location: loginDashboard.php");
          exit();
      } else {
          $errors[] = "Terjadi kesalahan saat mendaftar";
      }
  }
  
  // Jika ada error, kembali ke form dengan data
  $_SESSION['errors'] = $errors;
  $_SESSION['form_data'] = ['username' => $username, 'email' => $email];
  header("Location: registerDashboard.php");
  exit();
}
?>
