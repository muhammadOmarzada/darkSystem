<?php
session_start();
$errors = isset($_SESSION['errors']) ? $_SESSION['errors'] : [];
$form_data = isset($_SESSION['form_data']) ? $_SESSION['form_data'] : [];
unset($_SESSION['errors'], $_SESSION['form_data']);
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Dark System - Sistem Informasi Turnamen</title>
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css"
      rel="stylesheet"
      integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT"
      crossorigin="anonymous"
    />
    <link rel="stylesheet" href="registerDashboard.css" />
  </head>
  <body>
    <div class="container">
      <div class="row rounded-3">
        <div class="col-8 rounded-start-4">
          <h2 class="mx-auto p-2">Buat Akun</h2>
          
          <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
              <?php foreach ($errors as $error): ?>
                <div><?php echo htmlspecialchars($error); ?></div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
          
          <form action="register_process.php" method="POST">
            <div class="mb-3">
              <label for="username" class="form-label">Username</label>
              <input type="text" class="form-control" id="username" name="username" 
                     value="<?php echo htmlspecialchars($form_data['username'] ?? ''); ?>" required />
            </div>
            <div class="mb-3">
              <label for="email" class="form-label">Email</label>
              <input type="email" class="form-control" id="email" name="email" 
                     value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>" required />
            </div>
            <div class="mb-4">
              <label for="password" class="form-label">Password</label>
              <input type="password" class="form-control" id="password" name="password" required />
              <div class="form-text text-light">Password minimal 6 karakter</div>
            </div>
            <div class="mb-4">
              <button
                type="submit"
                class="btn rounded-5"
                style="--bs-btn-padding-y: 0.5rem; --bs-btn-padding-x: 4rem"
              >
                DAFTAR
              </button>
            </div>
            <div class="register">
              Sudah Punya Akun?
              <a href="loginDashboard.php">Login di sini</a>
            </div>
          </form>
          <div class="footer">
            Sistem Informasi Turnamen Esport Mobile Legends
          </div>
        </div>
        <div class="col-4 rounded-end-4"><span>DARK</span>SYSTEM</div>
      </div>
    </div>
    <script
      src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"
      integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO"
      crossorigin="anonymous"
    ></script>
  </body>
</html>
