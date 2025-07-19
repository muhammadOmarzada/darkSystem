<?php
require_once 'config.php';

// Ambil data tim dari database
try {
    $stmt = $pdo->query("SELECT * FROM tim ORDER BY peringkat ASC");
    $teams = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $teams = [];
    $error_message = "Error mengambil data tim: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>MPL ID Season 15 - Dark System</title>
    <link rel="stylesheet" href="home.css">
</head>
<body>
     <nav class="navbar">
    <div class="logo"><span class="dark">DARK</span><span class="system">SYSTEM</span></div>
    <ul class="nav-links">
      <li><a href="home.php">HOME</a></li>
      <li><a href="jadwal.php">JADWAL</a></li>
      <li><a href="tiket.php">TIKET</a></li>
    </ul>
  </nav>

    <section class="banner-section">
    </section>

   <section class="ranking-section">
        <h2>Peringkat</h2>
        <table class="ranking-table">
            <thead>
                <tr>
                    <th>TEAM</th>
                    <th>MATCH POINT</th>
                    <th>MATCH W.L.</th>
                    <th>NET GAME WIN</th>
                    <th>GAME W.L.</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($teams)): ?>
                    <tr>
                        <td colspan="5" class="text-center">Belum ada data tim</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($teams as $team): ?>
                        <tr>
                            <td class="team-column">
                                <span class="rank-number"><?php echo $team['peringkat']; ?></span>
                                <?php if (!empty($team['logo']) && file_exists($team['logo'])): ?>
                                    <img src="<?php echo htmlspecialchars($team['logo']); ?>" alt="<?php echo htmlspecialchars($team['nama']); ?> Logo" class="team-logo">
                                <?php else: ?>
                                    <img src="img/default-logo.png" alt="Default Logo" class="team-logo">
                                <?php endif; ?>
                                <?php echo htmlspecialchars($team['nama']); ?>
                            </td>
                            <td><?php echo $team['match_point']; ?></td>
                            <td><?php echo htmlspecialchars($team['match_wl']); ?></td>
                            <td><?php echo $team['net_game_win']; ?></td>
                            <td><?php echo htmlspecialchars($team['game_wl']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </section>

<section class="about-us">

     <div class="about-text">
            <h2>Tentang Kami</h2>
        </div>

        <div class="about-content">
            <div class="about-title">
                <span class="dark">DARK</span><span class="system">SYSTEM</span>
            </div>
            <p>Banyak penggemar esport yang kesulitan dalam mendapatkan informasi tentang liga atau turnamen Mobile Legends. Oleh karena itu, kita membuat sistem informasi berbasis website untuk esport Mobile Legends sebagai sarana untuk menyelesaikan persoalan tersebut serta menyediakan fitur penjualan tiket untuk turnamen Mobile Legends tersebut.</p>
        </div>
        
       
          <p class="official-sponsor">OFFICIAL SPONSOR</p>

        <div class="sponsor">
            <div class="sponsor-item">
                <img src="img/gopay.png" alt="GoPay Logo" class="sponsor-logo">
            </div>
            <div class="sponsor-item">
                <img src="img/mpl.png" alt="MPL Logo" class="sponsor-logo">
            </div>
        </div>
        
        
    </section>

    <footer>
        <p>Copyrights &copy; 2025 DARK SYSTEM. All Rights Reserved</p>
    </footer>
</body>
</html>