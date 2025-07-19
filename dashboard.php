<?php
session_start();
require_once 'config.php';

// Cek apakah user sudah login
if (!isset($_SESSION['akun_id'])) {
    header("Location: loginDashboard.php");
    exit();
}

// Get selected week from URL parameter, default to week 1
$selected_week = isset($_GET['week']) ? (int)$_GET['week'] : 1;

try {
    // Get all matches ordered by date and time
    $stmt = $pdo->query("
        SELECT 
            p.id_pertandingan,
            p.jadwal,
            p.jam,
            tr.harga as harga_reguler,
            tv.harga as harga_vip
        FROM pertandingan p
        LEFT JOIN tiket_reguler tr ON p.id_tiket_reguler = tr.id_tiket_reguler
        LEFT JOIN tiket_vip tv ON p.id_tiket_vip = tv.id_tiket_vip
        ORDER BY p.jadwal ASC, p.jam ASC
    ");
    $all_matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group matches into weeks (8 matches per week)
    $weeks = [];
    $matchesPerWeek = 8;
    $weekNumber = 1;
    
    for ($i = 0; $i < count($all_matches); $i += $matchesPerWeek) {
        $weekMatches = array_slice($all_matches, $i, $matchesPerWeek);
        $weeks[$weekNumber] = $weekMatches;
        $weekNumber++;
    }
    
    // Calculate KPIs for selected week using real ticket_type data
    $total_tickets_sold = 0;
    $total_revenue = 0;
    $matches_in_week = 0;
    
    if (isset($weeks[$selected_week])) {
        $matches_in_week = count($weeks[$selected_week]);
        $match_ids = array_column($weeks[$selected_week], 'id_pertandingan');
        
        if (!empty($match_ids)) {
            $placeholders = str_repeat('?,', count($match_ids) - 1) . '?';
            
            // Get total tickets sold for selected week
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as total_sold
                FROM penjualan p
                WHERE p.id_pertandingan IN ($placeholders)
            ");
            $stmt->execute($match_ids);
            $result = $stmt->fetch();
            $total_tickets_sold = $result['total_sold'];
            
            // Calculate real revenue using ticket_type
            $stmt = $pdo->prepare("
                SELECT 
                    p.ticket_type,
                    COUNT(*) as jumlah,
                    CASE 
                        WHEN p.ticket_type = 'reguler' THEN tr.harga
                        WHEN p.ticket_type = 'vip' THEN tv.harga
                    END as harga
                FROM penjualan p
                JOIN pertandingan pt ON p.id_pertandingan = pt.id_pertandingan
                LEFT JOIN tiket_reguler tr ON pt.id_tiket_reguler = tr.id_tiket_reguler
                LEFT JOIN tiket_vip tv ON pt.id_tiket_vip = tv.id_tiket_vip
                WHERE p.id_pertandingan IN ($placeholders)
                GROUP BY p.ticket_type, tr.harga, tv.harga
            ");
            $stmt->execute($match_ids);
            $revenue_data = $stmt->fetchAll();
            
            foreach ($revenue_data as $revenue) {
                $total_revenue += $revenue['jumlah'] * $revenue['harga'];
            }
        }
    }
    
    // Get total matches this season
    $stmt = $pdo->query("SELECT COUNT(*) as total_matches FROM pertandingan");
    $total_matches_season = $stmt->fetch()['total_matches'];
    
    // Prepare chart data for selected week
    $daily_viewers = [];
    $match_viewers = [];
    $daily_labels = [];
    $match_labels = [];

    if (isset($weeks[$selected_week])) {
        $weekMatches = $weeks[$selected_week];
        
        // Group matches by date for daily chart
        $matches_by_date = [];
        foreach ($weekMatches as $match) {
            $date = $match['jadwal'];
            if (!isset($matches_by_date[$date])) {
                $matches_by_date[$date] = [];
            }
            $matches_by_date[$date][] = $match;
        }
        
        // Get daily viewers data
        $day_counter = 1;
        foreach ($matches_by_date as $date => $dayMatches) {
            $day_match_ids = array_column($dayMatches, 'id_pertandingan');
            
            if (!empty($day_match_ids)) {
                $placeholders = str_repeat('?,', count($day_match_ids) - 1) . '?';
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as viewers
                    FROM penjualan p
                    WHERE p.id_pertandingan IN ($placeholders)
                ");
                $stmt->execute($day_match_ids);
                $viewers = $stmt->fetch()['viewers'];
            } else {
                $viewers = 0;
            }
            
            $daily_labels[] = "Day " . $day_counter;
            $daily_viewers[] = $viewers;
            $day_counter++;
        }
        
        // Get match viewers data
        $match_counter = 1;
        foreach ($weekMatches as $match) {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as viewers
                FROM penjualan p
                WHERE p.id_pertandingan = ?
            ");
            $stmt->execute([$match['id_pertandingan']]);
            $viewers = $stmt->fetch()['viewers'];
            
            $match_labels[] = "Match " . $match_counter;
            $match_viewers[] = $viewers;
            $match_counter++;
        }
    }
    
} catch(PDOException $e) {
    $error_message = "Error: " . $e->getMessage();
    $total_tickets_sold = 0;
    $total_revenue = 0;
    $matches_in_week = 0;
    $total_matches_season = 0;
    $weeks = [];
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Dark System - Dashboard</title>
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css"
      rel="stylesheet"
      integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT"
      crossorigin="anonymous"
    />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="dashboard.css" />
    <link rel="stylesheet" href="chatbot_styles.css">
    
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
          <a href="dashboard.php" class="active">
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
        <h3>DASHBOARD</h3>
        <hr />
        
        <?php if (isset($error_message)): ?>
          <div class="error-message">
            <?php echo htmlspecialchars($error_message); ?>
          </div>
        <?php endif; ?>
        
        <!-- Week Selector -->
        <div class="week-selector">
          <h5 style="color: #09122c; margin-bottom: 15px; text-align: center;">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-calendar-week" viewBox="0 0 16 16" style="margin-right: 8px;">
              <path d="M11 6.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5zm-3 0a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5zm-5 3a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5z"/>
              <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5M1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4z"/>
            </svg>
            Pilih Week untuk Melihat Statistik
          </h5>
          <div class="week-buttons">
            <?php for ($i = 1; $i <= count($weeks); $i++): ?>
              <a href="?week=<?php echo $i; ?>" 
                 class="week-btn <?php echo ($i == $selected_week) ? 'active' : ''; ?>"
                 onclick="showLoading()">
                Week <?php echo $i; ?>
              </a>
            <?php endfor; ?>
          </div>
        </div>
        
        <!-- KPI Cards -->
        <div class="row justify-content-center" id="kpiCards">
          <div class="col-lg-3 col-md-4 col-sm-6 d-flex justify-content-center">
            <div class="card kpi-card">
              <div class="card-header">
                <h5>
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-ticket-perforated" viewBox="0 0 16 16" style="margin-right: 8px;">
                    <path d="M4 4.85v.9h1v-.9zm7 0v.9h1v-.9zm-7 1.8v.9h1v-.9zm7 0v.9h1v-.9zm-7 1.8v.9h1v-.9zm7 0v.9h1v-.9zm-7 1.8v.9h1v-.9zm7 0v.9h1v-.9z"/>
                    <path d="M1.5 3A1.5 1.5 0 0 0 0 4.5V6a.5.5 0 0 0 .5.5 1.5 1.5 0 1 1 0 3 .5.5 0 0 0-.5.5v1.5A1.5 1.5 0 0 0 1.5 13h13a1.5 1.5 0 0 0 1.5-1.5V10a.5.5 0 0 0-.5-.5 1.5 1.5 0 0 1 0-3A.5.5 0 0 0 16 6V4.5A1.5 1.5 0 0 0 14.5 3zM1 4.5a.5.5 0 0 1 .5-.5h13a.5.5 0 0 1 .5.5v1.05a2.5 2.5 0 0 0 0 4.9v1.05a.5.5 0 0 1-.5.5h-13a.5.5 0 0 1-.5-.5v-1.05a2.5 2.5 0 0 0 0-4.9z"/>
                  </svg>
                  Total Tiket Terjual
                </h5>
              </div>
              <div class="card-body text-center">
                <h2 class="text-success"><?php echo number_format($total_tickets_sold); ?></h2>
                <p class="text-muted">Week <?php echo $selected_week; ?></p>
              </div>
            </div>
          </div>
          
          <div class="col-lg-3 col-md-4 col-sm-6 d-flex justify-content-center">
            <div class="card kpi-card">
              <div class="card-header">
                <h5>
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-currency-dollar" viewBox="0 0 16 16" style="margin-right: 8px;">
                    <path d="M4 10.781c.148 1.667 1.513 2.85 3.591 3.003V15h1.043v-1.216c2.27-.179 3.678-1.438 3.678-3.3 0-1.59-.947-2.51-2.956-3.028l-.722-.187V3.467c1.122.11 1.879.714 2.07 1.616h1.47c-.166-1.6-1.54-2.748-3.54-2.875V1H7.591v1.233c-1.939.23-3.27 1.472-3.27 3.156 0 1.454.966 2.483 2.661 2.917l.61.162v4.031c-1.149-.17-1.94-.8-2.131-1.718zm3.391-3.836c-1.043-.263-1.6-.825-1.6-1.616 0-.944.704-1.641 1.8-1.828v3.495l-.2-.05zm1.591 1.872c1.287.323 1.852.859 1.852 1.769 0 1.097-.826 1.828-2.2 1.939V8.73z"/>
                  </svg>
                  Total Pendapatan
                </h5>
              </div>
              <div class="card-body text-center">
                <h2 class="text-danger">Rp <?php echo number_format($total_revenue, 0, ',', '.'); ?></h2>
                <p class="text-muted">Week <?php echo $selected_week; ?></p>
              </div>
            </div>
          </div>
          
          <div class="col-lg-3 col-md-4 col-sm-6 d-flex justify-content-center">
            <div class="card kpi-card">
              <div class="card-header">
                <h5>
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trophy" viewBox="0 0 16 16" style="margin-right: 8px;">
                    <path d="M2.5.5A.5.5 0 0 1 3 0h10a.5.5 0 0 1 .5.5q0 .807-.034 1.536a3 3 0 1 1-1.133 5.89c-.79 1.865-1.878 2.777-2.833 3.011v2.173l1.425.356c.194.048.377.135.537.255L13.3 15.1a.5.5 0 0 1-.3.9H3a.5.5 0 0 1-.3-.9l1.838-1.379c.16-.12.343-.207.537-.255L6.5 13.11v-2.173c-.955-.234-2.043-1.146-2.833-3.012a3 3 0 1 1-1.132-5.89A33 33 0 0 1 2.5.5m.099 2.54a2 2 0 0 0 .72 3.935c-.333-1.05-.588-2.346-.72-3.935m10.083 3.935a2 2 0 0 0 .72-3.935c-.133 1.59-.388 2.885-.72 3.935"/>
                  </svg>
                  Pertandingan Season Ini
                </h5>
              </div>
              <div class="card-body text-center">
                <h2 class="text-primary"><?php echo $total_matches_season; ?></h2>
                <p class="text-muted">Total Pertandingan</p>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Charts Section -->
        <div class="row mt-4 justify-content-center">
          <div class="col-lg-5 col-md-6 col-sm-12 d-flex justify-content-center mb-4">
            <div class="card chart-card">
              <div class="card-header">
                <h5>
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-graph-up" viewBox="0 0 16 16" style="margin-right: 8px;">
                    <path fill-rule="evenodd" d="M0 0h1v15h15v1H0zm14.817 3.113a.5.5 0 0 1 .07.704l-4.5 5.5a.5.5 0 0 1-.74.037L7.06 6.767l-3.656 5.027a.5.5 0 0 1-.808-.588l4-5.5a.5.5 0 0 1 .758-.06l2.609 2.61 4.15-5.073a.5.5 0 0 1 .704-.07"/>
                  </svg>
                  Penonton per Hari - Week <?php echo $selected_week; ?>
                </h5>
              </div>
              <div class="card-body">
                <canvas id="dailyChart" width="400" height="200"></canvas>
              </div>
            </div>
          </div>
          
          <div class="col-lg-5 col-md-6 col-sm-12 d-flex justify-content-center mb-4">
            <div class="card chart-card">
              <div class="card-header">
                <h5>
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-graph-up" viewBox="0 0 16 16" style="margin-right: 8px;">
                    <path fill-rule="evenodd" d="M0 0h1v15h15v1H0zm14.817 3.113a.5.5 0 0 1 .07.704l-4.5 5.5a.5.5 0 0 1-.74.037L7.06 6.767l-3.656 5.027a.5.5 0 0 1-.808-.588l4-5.5a.5.5 0 0 1 .758-.06l2.609 2.61 4.15-5.073a.5.5 0 0 1 .704-.07"/>
                  </svg>
                  Penonton per Pertandingan - Week <?php echo $selected_week; ?>
                </h5>
              </div>
              <div class="card-body">
                <canvas id="matchChart" width="400" height="200"></canvas>
              </div>
            </div>
          </div>
        </div>
        
      </div>
      <div id="chatbot-toggle" class="chatbot-toggle">
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-chat-dots" viewBox="0 0 16 16">
        <path d="M5 8a1 1 0 1 1-2 0 1 1 0 0 1 2 0m4 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0m3 1a1 1 0 1 0 0-2 1 1 0 0 0 0 2"/>
        <path d="m2.165 15.803.02-.004c1.83-.363 2.948-.842 3.468-1.105A9 9 0 0 0 8 15c4.418 0 8-3.134 8-7s-3.582-7-8-7-8 3.134-8 7c0 1.76.743 3.37 1.97 4.6a10.4 10.4 0 0 1-.524 2.318l-.003.011a11 11 0 0 1-.244.637c-.079.186.074.394.273.362a22 22 0 0 0 .693-.125M8 2c3.314 0 6 2.462 6 5.5S11.314 13 8 13a7 7 0 0 1-2.96-.664A47 47 0 0 1 5 12.5c0 .613-.065 1.187-.13 1.613C3.678 13.348 2 11.674 2 9.5 2 4.462 4.686 2 8 2"/>
    </svg>
</div>

<div id="chatbot-container" class="chatbot-container">
    <div class="chatbot-header">
        <h5>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-robot" viewBox="0 0 16 16">
                <path d="M6 12.5a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 0 1h-3a.5.5 0 0 1-.5-.5M3 8.062C3 6.76 4.235 5.765 5.53 5.886a26.6 26.6 0 0 0 4.94 0C11.765 5.765 13 6.76 13 8.062v1.157a.93.93 0 0 1-.765.935c-.845.147-2.34.346-4.235.346s-3.39-.2-4.235-.346A.93.93 0 0 1 3 9.219zm4.542-.827a.25.25 0 0 0-.217.068l-.92.9a25 25 0 0 1-1.871-.183.25.25 0 0 0-.068.495c.55.076 1.232.149 2.02.193a.25.25 0 0 0 .189-.071l.754-.736.847 1.71a.25.25 0 0 0 .404.062l.932-.97a25 25 0 0 0 1.922-.188.25.25 0 0 0-.068-.495c-.538.074-1.207.145-1.98.189a.25.25 0 0 0-.166.076l-.754.785-.842-1.7a.25.25 0 0 0-.182-.135"/>
                <path d="M8.5 1.866a1 1 0 1 0-1 0V3h-2A4.5 4.5 0 0 0 1 7.5V8a1 1 0 0 0-1 1v2a1 1 0 0 0 1 1v1a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-1a1 1 0 0 0 1-1V9a1 1 0 0 0-1-1v-.5A4.5 4.5 0 0 0 10.5 3h-2zM14 7.5V13a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V7.5A3.5 3.5 0 0 1 5.5 4h5A3.5 3.5 0 0 1 14 7.5"/>
            </svg>
            ML Assistant
        </h5>
        <button id="chatbot-close" class="chatbot-close">×</button>
    </div>
    
    <div id="chatbot-messages" class="chatbot-messages">
        <div class="message bot-message">
            <div class="message-content">
                Halo! Saya adalah asisten AI untuk sistem Mobile Legends. Saya dapat membantu Anda dengan informasi tentang tim, pertandingan, dan statistik. Ada yang bisa saya bantu?
            </div>
        </div>
    </div>
    
    <div class="chatbot-input-container">
        <input type="text" id="chatbot-input" placeholder="Ketik pesan Anda..." maxlength="500">
        <button id="chatbot-send">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-send" viewBox="0 0 16 16">
                <path d="M15.854.146a.5.5 0 0 1 .11.54L13.026 8.5l2.938 7.814a.5.5 0 0 1-.11.54.5.5 0 0 1-.54.11L1.5 8.5l13.814-8.354a.5.5 0 0 1 .54.11M6.636 10.07l2.761 7.314L13.026 8.5z"/>
            </svg>
        </button>
    </div>
</div>
    </div>
    <script src="chatbot_script.js"></script>

    <script
      src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"
      integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWCozFSxQBxwHKO"
      crossorigin="anonymous"
    ></script>
    
    <script>
      function showLoading() {
        document.getElementById('kpiCards').style.display = 'none';
        document.getElementById('loadingDiv').style.display = 'block';
      }
      
      // Hide loading on page load
      document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('loadingDiv').style.display = 'none';
        document.getElementById('kpiCards').style.display = 'block';
      });
      
      // Add smooth transitions
      document.querySelectorAll('.week-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
          // Remove active class from all buttons
          document.querySelectorAll('.week-btn').forEach(b => b.classList.remove('active'));
          // Add active class to clicked button
          this.classList.add('active');
        });
      });

      // Chart data from PHP
      const dailyData = <?php echo json_encode($daily_viewers); ?>;
      const dailyLabels = <?php echo json_encode($daily_labels); ?>;
      const matchData = <?php echo json_encode($match_viewers); ?>;
      const matchLabels = <?php echo json_encode($match_labels); ?>;

      // Daily Chart
      const dailyCtx = document.getElementById('dailyChart').getContext('2d');
      const dailyChart = new Chart(dailyCtx, {
          type: 'line',
          data: {
              labels: dailyLabels,
              datasets: [{
                  label: 'Penonton',
                  data: dailyData,
                  borderColor: '#be3144',
                  backgroundColor: 'rgba(190, 49, 68, 0.1)',
                  borderWidth: 3,
                  fill: true,
                  tension: 0.4,
                  pointBackgroundColor: '#be3144',
                  pointBorderColor: '#ffffff',
                  pointBorderWidth: 2,
                  pointRadius: 6,
                  pointHoverRadius: 8
              }]
          },
          options: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: {
                  legend: {
                      display: false
                  }
              },
              scales: {
                  y: {
                      beginAtZero: true,
                      grid: {
                          color: 'rgba(0,0,0,0.1)'
                      },
                      ticks: {
                          color: '#666'
                      }
                  },
                  x: {
                      grid: {
                          color: 'rgba(0,0,0,0.1)'
                      },
                      ticks: {
                          color: '#666'
                      }
                  }
              },
              elements: {
                  point: {
                      hoverBackgroundColor: '#872341'
                  }
              }
          }
      });

      // Match Chart
      const matchCtx = document.getElementById('matchChart').getContext('2d');
      const matchChart = new Chart(matchCtx, {
          type: 'line',
          data: {
              labels: matchLabels,
              datasets: [{
                  label: 'Penonton',
                  data: matchData,
                  borderColor: '#09122c',
                  backgroundColor: 'rgba(9, 18, 44, 0.1)',
                  borderWidth: 3,
                  fill: true,
                  tension: 0.4,
                  pointBackgroundColor: '#09122c',
                  pointBorderColor: '#ffffff',
                  pointBorderWidth: 2,
                  pointRadius: 6,
                  pointHoverRadius: 8
              }]
          },
          options: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: {
                  legend: {
                      display: false
                  }
              },
              scales: {
                  y: {
                      beginAtZero: true,
                      grid: {
                          color: 'rgba(0,0,0,0.1)'
                      },
                      ticks: {
                          color: '#666'
                      }
                  },
                  x: {
                      grid: {
                          color: 'rgba(0,0,0,0.1)'
                      },
                      ticks: {
                          color: '#666',
                          maxRotation: 45
                      }
                  }
              },
              elements: {
                  point: {
                      hoverBackgroundColor: '#872341'
                  }
              }
          }
      });

      // Update charts when week changes
      function updateCharts() {
          dailyChart.update();
          matchChart.update();
      }

      // Call updateCharts when page loads
      document.addEventListener('DOMContentLoaded', function() {
          updateCharts();
      });
    </script>
  </body>
</html>
