<?php
session_start();
require_once 'config.php';

// Cek apakah user sudah login
if (!isset($_SESSION['akun_id'])) {
    header("Location: loginDashboard.php");
    exit();
}

// Ambil data tim dari database
try {
    $stmt = $pdo->query("SELECT * FROM tim ORDER BY peringkat ASC");
    $teams = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $teams = [];
    $error_message = "Error mengambil data tim: " . $e->getMessage();
}

// Handle success/error messages
$success = isset($_SESSION['success']) ? $_SESSION['success'] : '';
$errors = isset($_SESSION['errors']) ? $_SESSION['errors'] : [];
unset($_SESSION['success'], $_SESSION['errors']);
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Dark System - Manajemen Tim</title>
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css"
      rel="stylesheet"
      integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT"
      crossorigin="anonymous"
    />
    <link rel="stylesheet" href="dashboardTim.css" />
  </head>
  <body>
    <nav class="navbar">
      <div class="container-fluid">
        <span class="navbar-brand mb-0 h1">
          <span class="dark">DARK</span>SYSTEM
        </span>
        <span class="navbar-text text-white">
          Selamat datang, <?php echo htmlspecialchars($_SESSION['username']); ?>!
        </span>
      </div>
    </nav>

    <div class="sidebar" id="sidebar">
      <ul class="sidebar-menu">
        <li>
          <a href="dashboardUser.php">
            <span>
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-fill" viewBox="2 2 16 16">
                <path d="M3 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6"/>
              </svg>
              User
            </span>
          </a>
        </li>
        <li>
          <a href="dashboard.php">
            <span>
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-bar-chart-fill" viewBox="2 2 16 16">
                <path d="M1 11a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1zm5-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v7a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1zm5-5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1z"/>
              </svg>
              Dashboard
            </span>
          </a>
        </li>
        <li>
          <a href="dashboardFinance.php">
            <span>
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-currency-dollar" viewBox="2 2 16 16">
                <path d="M4 10.781c.148 1.667 1.513 2.85 3.591 3.003V15h1.043v-1.216c2.27-.179 3.678-1.438 3.678-3.3 0-1.59-.947-2.51-2.956-3.028l-.722-.187V3.467c1.122.11 1.879.714 2.07 1.616h1.47c-.166-1.6-1.54-2.748-3.54-2.875V1H7.591v1.233c-1.939.23-3.27 1.472-3.27 3.156 0 1.454.966 2.483 2.661 2.917l.61.162v4.031c-1.149-.17-1.94-.8-2.131-1.718zm3.391-3.836c-1.043-.263-1.6-.825-1.6-1.616 0-.944.704-1.641 1.8-1.828v3.495l-.2-.05zm1.591 1.872c1.287.323 1.852.859 1.852 1.769 0 1.097-.826 1.828-2.2 1.939V8.73z"/>
              </svg>
              Finance
            </span>
          </a>
        </li>
        <li>
          <a href="dashboardTim.php" class="active">
            <span>
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-people-fill" viewBox="0 2 17 17">
                <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6m-5.784 6A2.24 2.24 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.3 6.3 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1zM4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5"/>
              </svg>
              Tim
            </span>
          </a>
        </li>
        <li>
          <a href="dashboardTiket.php">
            <span>
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-ticket-perforated" viewBox="0 2 17 17">
                <path d="M4 4.85v.9h1v-.9zm7 0v.9h1v-.9zm-7 1.8v.9h1v-.9zm7 0v.9h1v-.9zm-7 1.8v.9h1v-.9zm7 0v.9h1v-.9zm-7 1.8v.9h1v-.9zm7 0v.9h1v-.9z"/>
                <path d="M1.5 3A1.5 1.5 0 0 0 0 4.5V6a.5.5 0 0 0 .5.5 1.5 1.5 0 1 1 0 3 .5.5 0 0 0-.5.5v1.5A1.5 1.5 0 0 0 1.5 13h13a1.5 1.5 0 0 0 1.5-1.5V10a.5.5 0 0 0-.5-.5 1.5 1.5 0 0 1 0-3A.5.5 0 0 0 16 6V4.5A1.5 1.5 0 0 0 14.5 3zM1 4.5a.5.5 0 0 1 .5-.5h13a.5.5 0 0 1 .5.5v1.05a2.5 2.5 0 0 0 0 4.9v1.05a.5.5 0 0 1-.5.5h-13a.5.5 0 0 1-.5-.5v-1.05a2.5 2.5 0 0 0 0-4.9z"/>
              </svg>
              Tiket
            </span>
          </a>
        </li>
        <li>
          <a href="logout.php">
            <span>
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-box-arrow-right" viewBox="0 2 16 16">
                <path fill-rule="evenodd" d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0z"/>
                <path fill-rule="evenodd" d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708z"/>
              </svg>
              Keluar
            </span>
          </a>
        </li>
      </ul>
    </div>

    <div class="main-content">
      <div class="container-fluid">
        <h3>DASHBOARD TIM</h3>
        <hr />
        
        <?php if ($success): ?>
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php foreach ($errors as $error): ?>
              <div><?php echo htmlspecialchars($error); ?></div>
            <?php endforeach; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>
        
        <div class="row">
          <div class="col-12">
            <div class="card">
              <div class="card-header d-flex justify-content-between align-items-center">
                <h5>Daftar Tim</h5>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addTeamModal">
                  <i class="bi bi-plus-circle"></i> Tambah Tim
                </button>
              </div>
              <div class="card-body">
                <div class="table-responsive">
                  <table class="table table-striped">
                    <thead>
                      <tr>
                        <th>Peringkat</th>
                        <th>Tim</th>
                        <th>Match Point</th>
                        <th>Match W.L.</th>
                        <th>Net Game Win</th>
                        <th>Game W.L.</th>
                        <th>Logo</th>
                        <th>Aksi</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (empty($teams)): ?>
                        <tr>
                          <td colspan="8" class="text-center">Belum ada data tim</td>
                        </tr>
                      <?php else: ?>
                        <?php foreach ($teams as $team): ?>
                          <tr>
                            <td>
                              <span class="badge bg-<?php echo $team['peringkat'] <= 3 ? 'success' : ($team['peringkat'] <= 6 ? 'primary' : 'secondary'); ?>">
                                <?php echo $team['peringkat']; ?>
                              </span>
                            </td>
                            <td>
                              <div class="d-flex align-items-center">
                                <strong><?php echo htmlspecialchars($team['nama']); ?></strong>
                              </div>
                            </td>
                            <td><?php echo $team['match_point']; ?></td>
                            <td><?php echo htmlspecialchars($team['match_wl']); ?></td>
                            <td><?php echo $team['net_game_win']; ?></td>
                            <td><?php echo htmlspecialchars($team['game_wl']); ?></td>
                            <td>
                              <?php if (!empty($team['logo']) && file_exists($team['logo'])): ?>
                                <img src="<?php echo htmlspecialchars($team['logo']); ?>" alt="Logo" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;">
                              <?php else: ?>
                                <span class="text-muted">Tidak ada logo</span>
                              <?php endif; ?>
                            </td>
                            <td>
                              <button class="btn btn-sm btn-warning" onclick="editTeam(<?php echo $team['id_tim']; ?>, '<?php echo htmlspecialchars($team['nama']); ?>', <?php echo $team['peringkat']; ?>, '<?php echo htmlspecialchars($team['logo']); ?>', <?php echo $team['match_point']; ?>, '<?php echo htmlspecialchars($team['match_wl']); ?>', <?php echo $team['net_game_win']; ?>, '<?php echo htmlspecialchars($team['game_wl']); ?>')">
                                Edit
                              </button>
                              <button class="btn btn-sm btn-danger" onclick="deleteTeam(<?php echo $team['id_tim']; ?>, '<?php echo htmlspecialchars($team['nama']); ?>')">
                                Hapus
                              </button>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Tambah Tim -->
    <div class="modal fade" id="addTeamModal" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Tambah Tim Baru</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <form action="tim_process.php" method="POST" enctype="multipart/form-data">
            <div class="modal-body">
              <input type="hidden" name="action" value="add">
              <div class="row">
                <div class="col-md-6">
                  <div class="mb-3">
                    <label for="nama" class="form-label">Nama Tim</label>
                    <input type="text" class="form-control" id="nama" name="nama" required>
                  </div>
                  <div class="mb-3">
                    <label for="peringkat" class="form-label">Peringkat</label>
                    <input type="number" class="form-control" id="peringkat" name="peringkat" min="1" required>
                  </div>
                  <div class="mb-3">
                    <label for="match_point" class="form-label">Match Point</label>
                    <input type="number" class="form-control" id="match_point" name="match_point" min="0" value="0">
                  </div>
                  <div class="mb-3">
                    <label for="net_game_win" class="form-label">Net Game Win</label>
                    <input type="number" class="form-control" id="net_game_win" name="net_game_win" value="0">
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="mb-3">
                    <label for="match_wl" class="form-label">Match W.L.</label>
                    <input type="text" class="form-control" id="match_wl" name="match_wl" placeholder="contoh: 5 - 0">
                  </div>
                  <div class="mb-3">
                    <label for="game_wl" class="form-label">Game W.L.</label>
                    <input type="text" class="form-control" id="game_wl" name="game_wl" placeholder="contoh: 10 - 2">
                  </div>
                  <div class="mb-3">
                    <label for="logo" class="form-label">Logo Tim</label>
                    <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
                    <div class="form-text">Format yang didukung: JPG, PNG, GIF. Maksimal 2MB</div>
                  </div>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
              <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Modal Edit Tim -->
    <div class="modal fade" id="editTeamModal" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Edit Tim</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <form action="tim_process.php" method="POST" enctype="multipart/form-data">
            <div class="modal-body">
              <input type="hidden" name="action" value="edit">
              <input type="hidden" name="id_tim" id="edit_id_tim">
              <div class="row">
                <div class="col-md-6">
                  <div class="mb-3">
                    <label for="edit_nama" class="form-label">Nama Tim</label>
                    <input type="text" class="form-control" id="edit_nama" name="nama" required>
                  </div>
                  <div class="mb-3">
                    <label for="edit_peringkat" class="form-label">Peringkat</label>
                    <input type="number" class="form-control" id="edit_peringkat" name="peringkat" min="1" required>
                  </div>
                  <div class="mb-3">
                    <label for="edit_match_point" class="form-label">Match Point</label>
                    <input type="number" class="form-control" id="edit_match_point" name="match_point" min="0">
                  </div>
                  <div class="mb-3">
                    <label for="edit_net_game_win" class="form-label">Net Game Win</label>
                    <input type="number" class="form-control" id="edit_net_game_win" name="net_game_win">
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="mb-3">
                    <label for="edit_match_wl" class="form-label">Match W.L.</label>
                    <input type="text" class="form-control" id="edit_match_wl" name="match_wl" placeholder="contoh: 5 - 0">
                  </div>
                  <div class="mb-3">
                    <label for="edit_game_wl" class="form-label">Game W.L.</label>
                    <input type="text" class="form-control" id="edit_game_wl" name="game_wl" placeholder="contoh: 10 - 2">
                  </div>
                  <div class="mb-3">
                    <label for="edit_logo" class="form-label">Logo Tim</label>
                    <input type="file" class="form-control" id="edit_logo" name="logo" accept="image/*">
                    <div class="form-text">Format yang didukung: JPG, PNG, GIF. Maksimal 2MB</div>
                    <div id="current_logo_preview" class="mt-2"></div>
                  </div>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
              <button type="submit" class="btn btn-warning">Update</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Modal Konfirmasi Hapus -->
    <div class="modal fade" id="deleteTeamModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Konfirmasi Hapus</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <p>Apakah Anda yakin ingin menghapus tim <strong id="delete_team_name"></strong>?</p>
            <p class="text-danger">Tindakan ini tidak dapat dibatalkan!</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <form action="tim_process.php" method="POST" style="display: inline;">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id_tim" id="delete_id_tim">
              <button type="submit" class="btn btn-danger">Hapus</button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <script
      src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"
      integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO"
      crossorigin="anonymous"
    ></script>
    
    <script>
      function editTeam(id, nama, peringkat, logo, matchPoint, matchWL, netGameWin, gameWL) {
        document.getElementById('edit_id_tim').value = id;
        document.getElementById('edit_nama').value = nama;
        document.getElementById('edit_peringkat').value = peringkat;
        document.getElementById('edit_match_point').value = matchPoint;
        document.getElementById('edit_match_wl').value = matchWL;
        document.getElementById('edit_net_game_win').value = netGameWin;
        document.getElementById('edit_game_wl').value = gameWL;
        
        // Tampilkan logo saat ini jika ada
        const currentLogoPreview = document.getElementById('current_logo_preview');
        if (logo && logo !== '') {
          currentLogoPreview.innerHTML = `
            <small class="text-muted">Logo saat ini:</small><br>
            <img src="${logo}" alt="Current Logo" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
            <br><small class="text-muted">Pilih file baru untuk mengganti logo</small>
          `;
        } else {
          currentLogoPreview.innerHTML = '<small class="text-muted">Belum ada logo</small>';
        }
        
        var editModal = new bootstrap.Modal(document.getElementById('editTeamModal'));
        editModal.show();
      }
      
      function deleteTeam(id, nama) {
        document.getElementById('delete_id_tim').value = id;
        document.getElementById('delete_team_name').textContent = nama;
        
        var deleteModal = new bootstrap.Modal(document.getElementById('deleteTeamModal'));
        deleteModal.show();
      }
    </script>
  </body>
</html>