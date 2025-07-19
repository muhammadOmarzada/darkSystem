<?php
session_start();
require_once 'config.php';

// Cek apakah user sudah login
if (!isset($_SESSION['akun_id'])) {
    header("Location: loginDashboard.php");
    exit();
}

// Ambil data pertandingan dengan join ke tabel tiket
try {
    $stmt = $pdo->query("
        SELECT 
            p.id_pertandingan,
            p.deskripsi_pertandingan,
            p.jadwal,
            p.jam,
            tr.jumlah as jumlah_reguler,
            tr.harga as harga_reguler,
            tv.jumlah as jumlah_vip,
            tv.harga as harga_vip,
            t1.nama as nama_tim1,
            t2.nama as nama_tim2,
            p.id_tim1,
            p.id_tim2
        FROM pertandingan p
        LEFT JOIN tiket_reguler tr ON p.id_tiket_reguler = tr.id_tiket_reguler
        LEFT JOIN tiket_vip tv ON p.id_tiket_vip = tv.id_tiket_vip
        LEFT JOIN tim t1 ON p.id_tim1 = t1.id_tim
        LEFT JOIN tim t2 ON p.id_tim2 = t2.id_tim
        ORDER BY p.jadwal ASC, p.jam ASC
    ");
    $pertandingan = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Ambil data tim untuk dropdown
    $stmt_tim = $pdo->query("SELECT id_tim, nama FROM tim ORDER BY nama ASC");
    $teams = $stmt_tim->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $pertandingan = [];
    $teams = [];
    $error_message = "Error mengambil data: " . $e->getMessage();
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
    <title>Dark System - Manajemen Tiket</title>
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT"
        crossorigin="anonymous"
    />
    <link rel="stylesheet" href="dashboardTiket.css" />
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
                <a href="dashboardTim.php">
                    <span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-people-fill" viewBox="0 2 17 17">
                            <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6m-5.784 6A2.24 2.24 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.3 6.3 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1zM4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5"/>
                        </svg>
                        Tim
                    </span>
                </a>
            </li>
            <li>
                <a href="dashboardTiket.php" class="active">
                    <span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-ticket-perforated" viewBox="0 2 17 17">
                            <path d="M4 4.85v.9h1v-.9zm7 0v.9h1v-.9zm-7 1.8v.9h1v-.9zm7 0v.9h1v-.9zm-7 1.8v.9h1v-.9zm7 0v.9h1v-.9zm-7 1.8v.9h1v-.9zm7 0v.9h1v-.9z"/>
                            <path d="M1.5 3A1.5 1.5 0 0 0 0 4.5V6a.5.5 0 0 0 .5.5 1.5 1.5 0 1 1 0 3 .5.5 0 0 0-.5.5v1.5A1.5 1.5 0 0 0 1.5 13h13a1.5 1.5 0 0 0 1.5-1.5V10a.5.5 0 0 0-.5-.5 1.5 1.5 0 0 1 0-3A.5.5 0 0 0 16 6V4.5A1.5 1.5 0 0 0 14.5 3zM1 4.5a.5.5 0 0 1 .5-.5h13a.5.5 0 0 1 .5.5v1.05a2.5 2.5 0 0 0 0 4.9v1.05a.5.5 0 0 1-.5.5h-13a.5.5.5 0 0 1-.5-.5v-1.05a2.5 2.5 0 0 0 0-4.9z"/>
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
            <h3>DASHBOARD TIKET</h3>
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
            
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5>Daftar Pertandingan & Tiket</h5>
                            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addMatchModal">
                                <i class="bi bi-plus-circle"></i> Tambah Pertandingan
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Pertandingan</th>
                                            <th>Tim</th>
                                            <th>Jadwal</th>
                                            <th>Jam</th>
                                            <th>Tiket Reguler</th>
                                            <th>Tiket VIP</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($pertandingan)): ?>
                                            <tr>
                                                <td colspan="8" class="text-center">Belum ada data pertandingan</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($pertandingan as $match): ?>
                                                <tr>
                                                    <td><?php echo $match['id_pertandingan']; ?></td>
                                                    <td><?php echo htmlspecialchars($match['deskripsi_pertandingan']); ?></td>
                                                    <td><?php echo htmlspecialchars($match['nama_tim1'] ?? 'Tim 1 tidak ditemukan') . ' vs ' . htmlspecialchars($match['nama_tim2'] ?? 'Tim 2 tidak ditemukan'); ?></td>
                                                    <td><?php echo date('d/m/Y', strtotime($match['jadwal'])); ?></td>
                                                    <td><?php echo date('H:i', strtotime($match['jam'])); ?></td>
                                                    <td>
                                                        <strong><?php echo $match['jumlah_reguler']; ?></strong> tiket<br>
                                                        <small class="text-muted">Rp <?php echo number_format($match['harga_reguler'], 0, ',', '.'); ?></small>
                                                    </td>
                                                    <td>
                                                        <strong><?php echo $match['jumlah_vip']; ?></strong> tiket<br>
                                                        <small class="text-muted">Rp <?php echo number_format($match['harga_vip'], 0, ',', '.'); ?></small>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-warning" onclick="editMatch(<?php echo htmlspecialchars(json_encode($match)); ?>)">
                                                            Edit
                                                        </button>
                                                        <button class="btn btn-sm btn-danger" onclick="deleteMatch(<?php echo $match['id_pertandingan']; ?>, '<?php echo htmlspecialchars($match['deskripsi_pertandingan']); ?>')">
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

    <!-- Modal Tambah Pertandingan -->
    <div class="modal fade" id="addMatchModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Pertandingan Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="pertandingan_process.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="deskripsi_pertandingan" class="form-label">Deskripsi Pertandingan</label>
                                    <input type="text" class="form-control" id="deskripsi_pertandingan" name="deskripsi_pertandingan" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="id_tim1" class="form-label">Tim 1</label>
                                    <select class="form-select" id="id_tim1" name="id_tim1" required>
                                        <option value="">Pilih Tim 1</option>
                                        <?php foreach ($teams as $team): ?>
                                            <option value="<?php echo $team['id_tim']; ?>"><?php echo htmlspecialchars($team['nama']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="id_tim2" class="form-label">Tim 2</label>
                                    <select class="form-select" id="id_tim2" name="id_tim2" required>
                                        <option value="">Pilih Tim 2</option>
                                        <?php foreach ($teams as $team): ?>
                                            <option value="<?php echo $team['id_tim']; ?>"><?php echo htmlspecialchars($team['nama']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="jadwal" class="form-label">Tanggal Pertandingan</label>
                                    <input type="date" class="form-control" id="jadwal" name="jadwal" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="jam" class="form-label">Jam Pertandingan</label>
                                    <input type="time" class="form-control" id="jam" name="jam" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Tiket Reguler</h6>
                                <div class="mb-3">
                                    <label for="jumlah_reguler" class="form-label">Jumlah Tiket</label>
                                    <input type="number" class="form-control" id="jumlah_reguler" name="jumlah_reguler" min="0" required>
                                </div>
                                <div class="mb-3">
                                    <label for="harga_reguler" class="form-label">Harga Tiket</label>
                                    <input type="number" class="form-control" id="harga_reguler" name="harga_reguler" min="0" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6>Tiket VIP</h6>
                                <div class="mb-3">
                                    <label for="jumlah_vip" class="form-label">Jumlah Tiket</label>
                                    <input type="number" class="form-control" id="jumlah_vip" name="jumlah_vip" min="0" required>
                                </div>
                                <div class="mb-3">
                                    <label for="harga_vip" class="form-label">Harga Tiket</label>
                                    <input type="number" class="form-control" id="harga_vip" name="harga_vip" min="0" required>
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

    <!-- Modal Edit Pertandingan -->
    <div class="modal fade" id="editMatchModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Pertandingan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="pertandingan_process.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id_pertandingan" id="edit_id_pertandingan">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="edit_deskripsi_pertandingan" class="form-label">Deskripsi Pertandingan</label>
                                    <input type="text" class="form-control" id="edit_deskripsi_pertandingan" name="deskripsi_pertandingan" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_id_tim1" class="form-label">Tim 1</label>
                                    <select class="form-select" id="edit_id_tim1" name="id_tim1" required>
                                        <option value="">Pilih Tim 1</option>
                                        <?php foreach ($teams as $team): ?>
                                            <option value="<?php echo $team['id_tim']; ?>"><?php echo htmlspecialchars($team['nama']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_id_tim2" class="form-label">Tim 2</label>
                                    <select class="form-select" id="edit_id_tim2" name="id_tim2" required>
                                        <option value="">Pilih Tim 2</option>
                                        <?php foreach ($teams as $team): ?>
                                            <option value="<?php echo $team['id_tim']; ?>"><?php echo htmlspecialchars($team['nama']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_jadwal" class="form-label">Tanggal Pertandingan</label>
                                    <input type="date" class="form-control" id="edit_jadwal" name="jadwal" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_jam" class="form-label">Jam Pertandingan</label>
                                    <input type="time" class="form-control" id="edit_jam" name="jam" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Tiket Reguler</h6>
                                <div class="mb-3">
                                    <label for="edit_jumlah_reguler" class="form-label">Jumlah Tiket</label>
                                    <input type="number" class="form-control" id="edit_jumlah_reguler" name="jumlah_reguler" min="0" required>
                                </div>
                                <div class="mb-3">
                                    <label for="edit_harga_reguler" class="form-label">Harga Tiket</label>
                                    <input type="number" class="form-control" id="edit_harga_reguler" name="harga_reguler" min="0" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6>Tiket VIP</h6>
                                <div class="mb-3">
                                    <label for="edit_jumlah_vip" class="form-label">Jumlah Tiket</label>
                                    <input type="number" class="form-control" id="edit_jumlah_vip" name="jumlah_vip" min="0" required>
                                </div>
                                <div class="mb-3">
                                    <label for="edit_harga_vip" class="form-label">Harga Tiket</label>
                                    <input type="number" class="form-control" id="edit_harga_vip" name="harga_vip" min="0" required>
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
    <div class="modal fade" id="deleteMatchModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Konfirmasi Hapus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menghapus pertandingan <strong id="delete_match_name"></strong>?</p>
                    <p class="text-danger">Tindakan ini tidak dapat dibatalkan!</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <form action="pertandingan_process.php" method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id_pertandingan" id="delete_id_pertandingan">
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
        function editMatch(matchData) {
            document.getElementById('edit_id_pertandingan').value = matchData.id_pertandingan;
            document.getElementById('edit_deskripsi_pertandingan').value = matchData.deskripsi_pertandingan;
            document.getElementById('edit_id_tim1').value = matchData.id_tim1;
            document.getElementById('edit_id_tim2').value = matchData.id_tim2;
            document.getElementById('edit_jadwal').value = matchData.jadwal;
            document.getElementById('edit_jam').value = matchData.jam;
            document.getElementById('edit_jumlah_reguler').value = matchData.jumlah_reguler;
            document.getElementById('edit_harga_reguler').value = matchData.harga_reguler;
            document.getElementById('edit_jumlah_vip').value = matchData.jumlah_vip;
            document.getElementById('edit_harga_vip').value = matchData.harga_vip;
            
            var editModal = new bootstrap.Modal(document.getElementById('editMatchModal'));
            editModal.show();
        }
        
        function deleteMatch(id, matchName) {
            document.getElementById('delete_id_pertandingan').value = id;
            document.getElementById('delete_match_name').textContent = matchName;
            
            var deleteModal = new bootstrap.Modal(document.getElementById('deleteMatchModal'));
            deleteModal.show();
        }
    </script>
</body>
</html>
