<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $email = trim($_POST['email']);
  $password = $_POST['password'];
  
  $errors = [];
  
  if (empty($email)) {
      $errors[] = "Email tidak boleh kosong";
  }
  
  if (empty($password)) {
      $errors[] = "Password tidak boleh kosong";
  }
  
  if (empty($errors)) {
      // Cari user berdasarkan email
      $stmt = $pdo->prepare("SELECT id_akun, username, email, password FROM akun WHERE email = ?");
      $stmt->execute([$email]);
      $user = $stmt->fetch(PDO::FETCH_ASSOC);
      
      if ($user && password_verify($password, $user['password'])) {
          // Login berhasil
          $_SESSION['akun_id'] = $user['id_akun'];
          $_SESSION['username'] = $user['username'];
          $_SESSION['email'] = $user['email'];
          
          header("Location: dashboardUser.php");
          exit();
      } else {
          $errors[] = "Email atau password salah";
      }
  }
  
  $_SESSION['errors'] = $errors;
  header("Location: loginDashboard.php");
  exit();
}
?>
