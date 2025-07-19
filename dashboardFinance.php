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
            p.deskripsi_pertandingan,
            p.jadwal,
            p.jam,
            tr.harga as harga_reguler,
            tv.harga as harga_vip,
            t1.nama as team1_name,
            t2.nama as team2_name
        FROM pertandingan p
        LEFT JOIN tiket_reguler tr ON p.id_tiket_reguler = tr.id_tiket_reguler
        LEFT JOIN tiket_vip tv ON p.id_tiket_vip = tv.id_tiket_vip
        LEFT JOIN tim t1 ON p.id_tim1 = t1.id_tim
        LEFT JOIN tim t2 ON p.id_tim2 = t2.id_tim
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
    $total_regular_sold = 0;
    $total_vip_sold = 0;
    $growth_rate = 0;
    
    // Prepare chart data
    $daily_revenue_regular = [];
    $daily_revenue_vip = [];
    $daily_quantity_regular = [];
    $daily_quantity_vip = [];
    $daily_labels = [];
    
    // Data for "Pendapatan per Pertandingan" chart
    $match_revenue_labels = [];
    $match_revenue_data = [];

    // Data for "Tim dengan Pendapatan Tertinggi" chart
    $team_revenue_data = []; // Associative array: team_name => total_revenue

    // Data for "Perbandingan Pendapatan Tiket" chart (Pie Chart)
    $total_revenue_reguler = 0;
    $total_revenue_vip = 0;

    if (isset($weeks[$selected_week])) {
        $weekMatches = $weeks[$selected_week];
        $match_ids_for_week = array_column($weekMatches, 'id_pertandingan');

        if (!empty($match_ids_for_week)) {
            $placeholders_week = str_repeat('?,', count($match_ids_for_week) - 1) . '?';

            // --- REVISI PENGHITUNGAN DATA HARIAN (2, 3, 3 pertandingan per hari) ---
            $daily_match_counts = [2, 3, 3]; // Day 1: 2 matches, Day 2: 3 matches, Day 3: 3 matches
            $current_match_index = 0;

            for ($day_idx = 0; $day_idx < count($daily_match_counts); $day_idx++) {
                $num_matches_for_day = $daily_match_counts[$day_idx];
                $day_matches = array_slice($weekMatches, $current_match_index, $num_matches_for_day);
                $current_match_index += $num_matches_for_day;

                $day_revenue_regular = 0;
                $day_revenue_vip = 0;
                $day_quantity_regular = 0;
                $day_quantity_vip = 0;

                if (!empty($day_matches)) {
                    $day_match_ids = array_column($day_matches, 'id_pertandingan');
                    $placeholders_day = str_repeat('?,', count($day_match_ids) - 1) . '?';

                    $stmt = $pdo->prepare("
                        SELECT
                            p.ticket_type,
                            COUNT(*) as quantity,
                            CASE
                                WHEN p.ticket_type = 'reguler' THEN tr.harga
                                WHEN p.ticket_type = 'vip' THEN tv.harga
                            END as harga
                        FROM penjualan p
                        JOIN pertandingan pt ON p.id_pertandingan = pt.id_pertandingan
                        LEFT JOIN tiket_reguler tr ON pt.id_tiket_reguler = tr.id_tiket_reguler
                        LEFT JOIN tiket_vip tv ON pt.id_tiket_vip = tv.id_tiket_vip
                        WHERE p.id_pertandingan IN ($placeholders_day)
                        GROUP BY p.ticket_type, tr.harga, tv.harga
                    ");
                    $stmt->execute($day_match_ids);
                    $daily_sales = $stmt->fetchAll();

                    foreach ($daily_sales as $sale) {
                        if ($sale['ticket_type'] === 'reguler') {
                            $day_revenue_regular += $sale['quantity'] * $sale['harga'];
                            $day_quantity_regular += $sale['quantity'];
                        } else if ($sale['ticket_type'] === 'vip') {
                            $day_revenue_vip += $sale['quantity'] * $sale['harga'];
                            $day_quantity_vip += $sale['quantity'];
                        }
                    }
                }

                $daily_labels[] = "Day " . ($day_idx + 1);
                $daily_revenue_regular[] = $day_revenue_regular;
                $daily_revenue_vip[] = $day_revenue_vip;
                $daily_quantity_regular[] = $day_quantity_regular;
                $daily_quantity_vip[] = $day_quantity_vip;
            }
            // --- AKHIR REVISI PENGHITUNGAN DATA HARIAN ---

            // Calculate total regular and VIP tickets sold for KPI cards
            $stmt_kpi_sales = $pdo->prepare("
                SELECT
                    p.ticket_type,
                    COUNT(*) as quantity
                FROM penjualan p
                WHERE p.id_pertandingan IN ($placeholders_week)
                GROUP BY p.ticket_type
            ");
            $stmt_kpi_sales->execute($match_ids_for_week);
            $kpi_sales = $stmt_kpi_sales->fetchAll();

            foreach ($kpi_sales as $sale) {
                if ($sale['ticket_type'] === 'reguler') {
                    $total_regular_sold = $sale['quantity'];
                } else if ($sale['ticket_type'] === 'vip') {
                    $total_vip_sold = $sale['quantity'];
                }
            }

            // Calculate revenue per match (ini sudah benar, mengiterasi semua 8 pertandingan dalam seminggu)
            foreach ($weekMatches as $match) {
                $match_id = $match['id_pertandingan'];
                // Menggunakan deskripsi_pertandingan untuk label yang lebih akurat
                $match_label = $match['team1_name'] . ' vs ' . $match['team2_name'] . ' (' . $match['deskripsi_pertandingan'] . ')';

                $stmt_match_sales = $pdo->prepare("
                    SELECT 
                        p.ticket_type,
                        COUNT(*) as quantity,
                        CASE 
                            WHEN p.ticket_type = 'reguler' THEN tr.harga
                            WHEN p.ticket_type = 'vip' THEN tv.harga
                        END as harga
                    FROM penjualan p
                    JOIN pertandingan pt ON p.id_pertandingan = pt.id_pertandingan
                    LEFT JOIN tiket_reguler tr ON pt.id_tiket_reguler = tr.id_tiket_reguler
                    LEFT JOIN tiket_vip tv ON pt.id_tiket_vip = tv.id_tiket_vip
                    WHERE p.id_pertandingan = ?
                    GROUP BY p.ticket_type, tr.harga, tv.harga
                ");
                $stmt_match_sales->execute([$match_id]);
                $match_sales = $stmt_match_sales->fetchAll();

                $total_match_revenue = 0;
                foreach ($match_sales as $sale) {
                    $total_match_revenue += $sale['quantity'] * $sale['harga'];
                }

                $match_revenue_labels[] = $match_label;
                $match_revenue_data[] = $total_match_revenue;
            }

            // Calculate revenue per team (ini juga sudah benar)
            $stmt_all_sales_week = $pdo->prepare("
                SELECT 
                    s.ticket_type,
                    pt.id_tim1,
                    pt.id_tim2,
                    tr.harga as reguler_harga,
                    tv.harga as vip_harga
                FROM penjualan s
                JOIN pertandingan pt ON s.id_pertandingan = pt.id_pertandingan
                LEFT JOIN tiket_reguler tr ON pt.id_tiket_reguler = tr.id_tiket_reguler
                LEFT JOIN tiket_vip tv ON pt.id_tiket_vip = tv.id_tiket_vip
                WHERE s.id_pertandingan IN ($placeholders_week)
            ");
            $stmt_all_sales_week->execute($match_ids_for_week);
            $all_sales_week = $stmt_all_sales_week->fetchAll();

            $team_revenues_raw = []; // id_tim => total_revenue

            foreach ($all_sales_week as $sale) {
                $revenue = ($sale['ticket_type'] === 'reguler') ? $sale['reguler_harga'] : $sale['vip_harga'];

                // Add revenue to both teams involved in the match
                if (!isset($team_revenues_raw[$sale['id_tim1']])) {
                    $team_revenues_raw[$sale['id_tim1']] = 0;
                }
                $team_revenues_raw[$sale['id_tim1']] += $revenue;

                if (!isset($team_revenues_raw[$sale['id_tim2']])) {
                    $team_revenues_raw[$sale['id_tim2']] = 0;
                }
                $team_revenues_raw[$sale['id_tim2']] += $revenue;

                // Calculate total revenue for pie chart
                if ($sale['ticket_type'] === 'reguler') {
                    $total_revenue_reguler += $revenue;
                } else if ($sale['ticket_type'] === 'vip') {
                    $total_revenue_vip += $revenue;
                }
            }

            // Fetch team names and prepare data for chart
            if (!empty($team_revenues_raw)) {
                $team_ids = array_keys($team_revenues_raw);
                $placeholders_teams = str_repeat('?,', count($team_ids) - 1) . '?';
                $stmt_team_names = $pdo->prepare("SELECT id_tim, nama FROM tim WHERE id_tim IN ($placeholders_teams)");
                $stmt_team_names->execute($team_ids);
                $team_names_map = [];
                foreach ($stmt_team_names->fetchAll(PDO::FETCH_ASSOC) as $team) {
                    $team_names_map[$team['id_tim']] = $team['nama'];
                }

                foreach ($team_revenues_raw as $team_id => $revenue) {
                    if (isset($team_names_map[$team_id])) {
                        $team_revenue_data[$team_names_map[$team_id]] = $revenue;
                    }
                }
            }
        }
    }

    // Sort teams by revenue in descending order
    arsort($team_revenue_data);

    $top_teams_labels = array_keys($team_revenue_data);
    $top_teams_revenue = array_values($team_revenue_data);

    // Calculate growth rate compared to previous week (if not week 1)
    if ($selected_week > 1 && isset($weeks[$selected_week - 1])) {
        $prev_week_match_ids = array_column($weeks[$selected_week - 1], 'id_pertandingan');
        $prev_week_total = 0;
        
        if (!empty($prev_week_match_ids)) {
            $placeholders = str_repeat('?,', count($prev_week_match_ids) - 1) . '?';
            
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as total_sold
                FROM penjualan p
                WHERE p.id_pertandingan IN ($placeholders)
            ");
            $stmt->execute($prev_week_match_ids);
            $result = $stmt->fetch();
            $prev_week_total = $result['total_sold'];
        }
        
        $current_week_total = $total_regular_sold + $total_vip_sold;
        
        if ($prev_week_total > 0) {
            $growth_rate = (($current_week_total - $prev_week_total) / $prev_week_total) * 100;
        } else if ($current_week_total > 0) {
            $growth_rate = 100; // 100% growth if previous week had 0 sales
        }
    }
    
} catch(PDOException $e) {
    $error_message = "Error: " . $e->getMessage();
    $total_regular_sold = 0;
    $total_vip_sold = 0;
    $growth_rate = 0;
    $weeks = [];
    $daily_revenue_regular = [];
    $daily_revenue_vip = [];
    $daily_quantity_regular = [];
    $daily_quantity_vip = [];
    $daily_labels = [];
    $match_revenue_labels = [];
    $match_revenue_data = [];
    $top_teams_labels = [];
    $top_teams_revenue = [];
    $total_revenue_reguler = 0; // Initialize for error case
    $total_revenue_vip = 0;     // Initialize for error case
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Dark System - Manajemen Keuangan</title>
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css"
      rel="stylesheet"
      integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT"
      crossorigin="anonymous"
    />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="dashboardFinance.css" />
    
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
          <a href="dashboardFinance.php" class="active">
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
        <h3>DASHBOARD FINANCE</h3>
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
              <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1-2-2h1V.5a.5.5 0 0 1 .5-.5M1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4z"/>
            </svg>
            Pilih Week untuk Melihat Data Finance
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
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-ticket" viewBox="0 0 16 16" style="margin-right: 8px;">
                    <path d="M0 4.5A1.5 1.5 0 0 1 1.5 3h13A1.5 1.5 0 0 1 16 4.5V6a.5.5 0 0 1-.5.5 1.5 1.5 0 0 0 0 3 .5.5 0 0 1 .5.5v1.5a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 0 10.5V9a.5.5 0 0 1-.5-.5 1.5 1.5 0 1 0 0-3A.5.5 0 0 1 0 5V4.5M1.5 4a.5.5 0 0 0-.5.5v.793c.146.073.25.194.25.343a2.5 2.5 0 0 1 0 4.728.5.5 0 0 0-.25.343v.793a.5.5 0 0 0 .5.5h13a.5.5 0 0 0 .5-.5v-.793a.5.5 0 0 0-.25-.343 2.5 2.5 0 0 1 0-4.728.5.5 0 0 0 .25-.343V4.5a.5.5 0 0 0-.5-.5z"/>
                  </svg>
                  Total Tiket Reguler
                </h5>
              </div>
              <div class="card-body text-center">
                <h2 class="text-primary"><?php echo number_format($total_regular_sold); ?></h2>
                <p class="text-muted">Week <?php echo $selected_week; ?></p>
              </div>
            </div>
          </div>
          
          <div class="col-lg-3 col-md-4 col-sm-6 d-flex justify-content-center">
            <div class="card kpi-card">
              <div class="card-header">
                <h5>
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-star-fill" viewBox="0 0 16 16" style="margin-right: 8px;">
                    <path d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 2.256z"/>
                  </svg>
                  Total Tiket VIP
                </h5>
              </div>
              <div class="card-body text-center">
                <h2 class="text-warning"><?php echo number_format($total_vip_sold); ?></h2>
                <p class="text-muted">Week <?php echo $selected_week; ?></p>
              </div>
            </div>
          </div>
          
          <div class="col-lg-3 col-md-4 col-sm-6 d-flex justify-content-center">
            <div class="card kpi-card">
              <div class="card-header">
                <h5>
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-graph-up-arrow" viewBox="0 0 16 16" style="margin-right: 8px;">
                    <path fill-rule="evenodd" d="M0 0h1v15h15v1H0zm10 3.5a.5.5 0 0 1 .5-.5h4a.5.5 0 0 1 .5.5v4a.5.5 0 0 1-1 0V4.9l-3.613 4.417a.5.5 0 0 1-.74.037L7.06 6.767l-3.656 5.027a.5.5 0 0 1-.808-.588l4-5.5a.5.5 0 0 1 .758-.06l2.609 2.61L13.445 4H10.5a.5.5 0 0 1-.5-.5a.5.5 0 0 1-.5.5zm3.391-3.836c-1.043-.263-1.6-.825-1.6-1.616 0-.944.704-1.641 1.8-1.828v3.495l-.2-.05zm1.591 1.872c1.287.323 1.852.859 1.852 1.769 0 1.097-.826 1.828-2.2 1.939V8.73z"/>
                  </svg>
                  Tingkat Pertumbuhan
                </h5>
              </div>
              <div class="card-body text-center">
                <?php if ($selected_week == 1): ?>
                  <h2 class="growth-neutral">N/A</h2>
                  <p class="text-muted">Week Pertama</p>
                <?php else: ?>
                  <h2 class="<?php echo $growth_rate > 0 ? 'growth-positive' : ($growth_rate < 0 ? 'growth-negative' : 'growth-neutral'); ?>">
                    <?php echo $growth_rate > 0 ? '+' : ''; ?><?php echo number_format($growth_rate, 1); ?>%
                  </h2>
                  <p class="text-muted">vs Week <?php echo $selected_week - 1; ?></p>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Charts Section -->
        <div class="row mt-4 justify-content-center">
          <div class="col-lg-6 col-md-12 d-flex justify-content-center mb-4">
            <div class="card chart-card" style="width: 100%; max-width: 600px;">
              <div class="card-header">
                <h5>
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-currency-dollar" viewBox="0 0 16 16" style="margin-right: 8px;">
                    <path d="M4 10.781c.148 1.667 1.513 2.85 3.591 3.003V15h1.043v-1.216c2.27-.179 3.678-1.438 3.678-3.3 0-1.59-.947-2.51-2.956-3.028l-.722-.187V3.467c1.122.11 1.879.714 2.07 1.616h1.47c-.166-1.6-1.54-2.748-3.54-2.875V1H7.591v1.233c-1.939.23-3.27 1.472-3.27 3.156 0 1.454.966 2.483 2.661 2.917l.61.162v4.031c-1.149-.17-1.94-.8-2.131-1.718zm3.391-3.836c-1.043-.263-1.6-.825-1.6-1.616 0-.944.704-1.641 1.8-1.828v3.495l-.2-.05zm1.591 1.872c1.287.323 1.852.859 1.852 1.769 0 1.097-.826 1.828-2.2 1.939V8.73z"/>
                  </svg>
                  Pendapatan Harian - Week <?php echo $selected_week; ?>
                </h5>
              </div>
              <div class="card-body">
                <canvas id="revenueChart" width="400" height="200"></canvas>
              </div>
            </div>
          </div>
          
          <div class="col-lg-6 col-md-12 d-flex justify-content-center mb-4">
            <div class="card chart-card" style="width: 100%; max-width: 600px;">
              <div class="card-header">
                <h5>
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-graph-up" viewBox="0 0 16 16" style="margin-right: 8px;">
                    <path fill-rule="evenodd" d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5.5 0 0 0-1 0z"/>
                    <path fill-rule="evenodd" d="M15.854 8.354a.5.5 0 0 0 0-.708l-4.5 5.5a.5.5 0 0 0-.74.037L7.06 6.767l-3.656 5.027a.5.5 0 0 0-.808-.588l4-5.5a.5.5 0 0 0 .758-.06l2.609 2.61 4.15-5.073a.5.5 0 0 0 .704-.07"/>
                  </svg>
                  Penjualan Tiket Harian - Week <?php echo $selected_week; ?>
                </h5>
              </div>
              <div class="card-body">
                <canvas id="quantityChart" width="400" height="200"></canvas>
              </div>
            </div>
          </div>
          <div class="col-lg-6 col-md-12 d-flex justify-content-center mb-4">
            <div class="card chart-card" style="width: 100%; max-width: 600px;">
              <div class="card-header">
                <h5>
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-currency-dollar" viewBox="0 0 16 16" style="margin-right: 8px;">
                    <path d="M4 10.781c.148 1.667 1.513 2.85 3.591 3.003V15h1.043v-1.216c2.27-.179 3.678-1.438 3.678-3.3 0-1.59-.947-2.51-2.956-3.028l-.722-.187V3.467c1.122.11 1.879.714 2.07 1.616h1.47c-.166-1.6-1.54-2.748-3.54-2.875V1H7.591v1.233c-1.939.23-3.27 1.472-3.27 3.156 0 1.454.966 2.483 2.661 2.917l.61.162v4.031c-1.149-.17-1.94-.8-2.131-1.718zm3.391-3.836c-1.043-.263-1.6-.825-1.6-1.616 0-.944.704-1.641 1.8-1.828v3.495l-.2-.05zm1.591 1.872c1.287.323 1.852.859 1.852 1.769 0 1.097-.826 1.828-2.2 1.939V8.73z"/>
                  </svg>
                  Tim dengan Pendapatan Tertinggi - Week <?php echo $selected_week; ?>
                </h5>
              </div>
              <div class="card-body">
                <canvas id="topTeamsRevenueChart" width="400" height="200"></canvas>
              </div>
            </div>
          </div>
          <div class="col-lg-6 col-md-12 d-flex justify-content-center mb-4">
            <div class="card chart-card" style="width: 100%; max-width: 600px;">
              <div class="card-header">
                <h5>
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-graph-up" viewBox="0 0 16 16" style="margin-right: 8px;">
                    <path fill-rule="evenodd" d="M0 0h1v15h15v1H0zm10 3.5a.5.5 0 0 1 .5-.5h4a.5.5 0 0 1 .5.5v4a.5.5 0 0 1-1 0V4.9l-3.613 4.417a.5.5 0 0 1-.74.037L7.06 6.767l-3.656 5.027a.5.5 0 0 1-.808-.588l4-5.5a.5.5 0 0 1 .758-.06l2.609 2.61 4.15-5.073a.5.5 0 0 0 .704-.07"/>
                  </svg>
                  Pendapatan per Pertandingan - Week <?php echo $selected_week; ?>
                </h5>
              </div>
              <div class="card-body">
                <canvas id="matchRevenueChart" width="400" height="200"></canvas>
              </div>
            </div>
          </div>
          <div class="col-lg-6 col-md-12 d-flex justify-content-center mb-4">
            <div class="card chart-card" style="width: 100%; max-width: 600px;">
              <div class="card-header">
                <h5>
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pie-chart-fill" viewBox="0 0 16 16" style="margin-right: 8px;">
                    <path d="M15.985 8.5H8.207l-5.5 5.5a8 8 0 0 0 13.278-5.5zM2 13.278A8 8 0 0 1 7.5 0v7.793l-5.5 5.5zM8.5 0v7.793l5.5-5.5A8 8 0 0 0 8.5 0"/>
                  </svg>
                  Perbandingan Pendapatan Tiket - Week <?php echo $selected_week; ?>
                </h5>
              </div>
              <div class="card-body">
                <canvas id="ticketRevenuePieChart" width="400" height="200"></canvas>
              </div>
            </div>
          </div>
        </div>
        
      </div>
    </div>

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
      const dailyLabels = <?php echo json_encode($daily_labels); ?>;
      const dailyRevenueRegular = <?php echo json_encode($daily_revenue_regular); ?>;
      const dailyRevenueVip = <?php echo json_encode($daily_revenue_vip); ?>;
      const dailyQuantityRegular = <?php echo json_encode($daily_quantity_regular); ?>;
      const dailyQuantityVip = <?php echo json_encode($daily_quantity_vip); ?>;
      const topTeamsLabels = <?php echo json_encode($top_teams_labels); ?>;
      const topTeamsRevenue = <?php echo json_encode($top_teams_revenue); ?>;
      const matchRevenueLabels = <?php echo json_encode($match_revenue_labels); ?>;
      const matchRevenueData = <?php echo json_encode($match_revenue_data); ?>;
      const totalRevenueReguler = <?php echo json_encode($total_revenue_reguler); ?>;
      const totalRevenueVip = <?php echo json_encode($total_revenue_vip); ?>;

      // Revenue Chart (Bar Chart)
      const revenueCtx = document.getElementById('revenueChart').getContext('2d');
      const revenueChart = new Chart(revenueCtx, {
          type: 'bar',
          data: {
              labels: dailyLabels,
              datasets: [
                  {
                      label: 'Pendapatan Reguler',
                      data: dailyRevenueRegular,
                      backgroundColor: 'rgba(54, 162, 235, 0.8)',
                      borderColor: 'rgba(54, 162, 235, 1)',
                      borderWidth: 2,
                      borderRadius: 4,
                      borderSkipped: false,
                  },
                  {
                      label: 'Pendapatan VIP',
                      data: dailyRevenueVip,
                      backgroundColor: 'rgba(255, 193, 7, 0.8)',
                      borderColor: 'rgba(255, 193, 7, 1)',
                      borderWidth: 2,
                      borderRadius: 4,
                      borderSkipped: false,
                  }
              ]
          },
          options: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: {
                  legend: {
                      position: 'top',
                      labels: {
                          usePointStyle: true,
                          padding: 20
                      }
                  },
                  tooltip: {
                      callbacks: {
                          label: function(context) {
                              return context.dataset.label + ': Rp ' + context.parsed.y.toLocaleString('id-ID');
                          }
                      }
                  }
              },
              scales: {
                  y: {
                      beginAtZero: true,
                      grid: {
                          color: 'rgba(0,0,0,0.1)'
                      },
                      ticks: {
                          color: '#666',
                          callback: function(value) {
                              return 'Rp ' + value.toLocaleString('id-ID');
                          }
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
              interaction: {
                  intersect: false,
                  mode: 'index'
              }
          }
      });

      // Quantity Chart (Line Chart)
      const quantityCtx = document.getElementById('quantityChart').getContext('2d');
      const quantityChart = new Chart(quantityCtx, {
          type: 'line',
          data: {
              labels: dailyLabels,
              datasets: [
                  {
                      label: 'Tiket Reguler',
                      data: dailyQuantityRegular,
                      borderColor: 'rgba(54, 162, 235, 1)',
                      backgroundColor: 'rgba(54, 162, 235, 0.1)',
                      borderWidth: 3,
                      fill: true,
                      tension: 0.4,
                      pointBackgroundColor: 'rgba(54, 162, 235, 1)',
                      pointBorderColor: '#ffffff',
                      pointBorderWidth: 2,
                      pointRadius: 6,
                      pointHoverRadius: 8
                  },
                  {
                      label: 'Tiket VIP',
                      data: dailyQuantityVip,
                      borderColor: 'rgba(255, 193, 7, 1)',
                      backgroundColor: 'rgba(255, 193, 7, 0.1)',
                      borderWidth: 3,
                      fill: true,
                      tension: 0.4,
                      pointBackgroundColor: 'rgba(255, 193, 7, 1)',
                      pointBorderColor: '#ffffff',
                      pointBorderWidth: 2,
                      pointRadius: 6,
                      pointHoverRadius: 8
                  }
              ]
          },
          options: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: {
                  legend: {
                      position: 'top',
                      labels: {
                          usePointStyle: true,
                          padding: 20
                      }
                  },
                  tooltip: {
                      callbacks: {
                          label: function(context) {
                              return context.dataset.label + ': ' + context.parsed.y + ' tiket';
                          }
                      }
                  }
              },
              scales: {
                  y: {
                      beginAtZero: true,
                      grid: {
                          color: 'rgba(0,0,0,0.1)'
                      },
                      ticks: {
                          color: '#666',
                          callback: function(value) {
                              return value + ' tiket';
                          }
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
                      hoverBackgroundColor: '#be3144'
                  }
              },
              interaction: {
                  intersect: false,
                  mode: 'index'
              }
          }
      });

      // Match Revenue Chart (Line Chart)
      const matchRevenueCtx = document.getElementById('matchRevenueChart').getContext('2d');
      const matchRevenueChart = new Chart(matchRevenueCtx, {
          type: 'line',
          data: {
              labels: matchRevenueLabels,
              datasets: [{
                  label: 'Pendapatan',
                  data: matchRevenueData,
                  borderColor: 'rgba(190, 49, 68, 1)', // Warna merah dari CSS Anda
                  backgroundColor: 'rgba(190, 49, 68, 0.1)', // Warna merah transparan
                  borderWidth: 3,
                  fill: true,
                  tension: 0.4,
                  pointBackgroundColor: 'rgba(190, 49, 68, 1)',
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
                      position: 'top',
                      labels: {
                          usePointStyle: true,
                          padding: 20
                      }
                  },
                  tooltip: {
                      callbacks: {
                          label: function(context) {
                              return context.dataset.label + ': Rp ' + context.parsed.y.toLocaleString('id-ID');
                          }
                      }
                  }
              },
              scales: {
                  y: {
                      beginAtZero: true,
                      grid: {
                          color: 'rgba(0,0,0,0.1)'
                      },
                      ticks: {
                          color: '#666',
                          callback: function(value) {
                              return 'Rp ' + value.toLocaleString('id-ID');
                          }
                      }
                  },
                  x: {
                      grid: {
                          color: 'rgba(0,0,0,0.1)'
                      },
                      ticks: {
                          color: '#ffffff'
                      }
                  }
              },
              elements: {
                  point: {
                      hoverBackgroundColor: '#872341' // Warna hover dari CSS Anda
                  }
              },
              interaction: {
                  intersect: false,
                  mode: 'index'
              }
          }
      });

      // Ticket Revenue Pie Chart
      const ticketRevenuePieCtx = document.getElementById('ticketRevenuePieChart').getContext('2d');
      const ticketRevenuePieChart = new Chart(ticketRevenuePieCtx, {
          type: 'pie',
          data: {
              labels: ['Tiket Reguler', 'Tiket VIP'],
              datasets: [{
                  data: [totalRevenueReguler, totalRevenueVip],
                  backgroundColor: [
                      'rgba(54, 162, 235, 0.8)', // Warna biru untuk reguler
                      'rgba(255, 193, 7, 0.8)'  // Warna kuning untuk VIP
                  ],
                  borderColor: [
                      'rgba(54, 162, 235, 1)',
                      'rgba(255, 193, 7, 1)'
                  ],
                  borderWidth: 1
              }]
          },
          options: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: {
                  legend: {
                      position: 'top',
                      labels: {
                          usePointStyle: true,
                          padding: 20
                      }
                  },
                  tooltip: {
                      callbacks: {
                          label: function(context) {
                              let label = context.label || '';
                              if (label) {
                                  label += ': ';
                              }
                              if (context.parsed !== null) {
                                  label += 'Rp ' + context.parsed.toLocaleString('id-ID');
                              }
                              return label;
                          }
                      }
                  }
              }
          }
      });

      // Top Teams Revenue Chart (Horizontal Bar Chart)
      const topTeamsRevenueCtx = document.getElementById('topTeamsRevenueChart').getContext('2d');
      const topTeamsRevenueChart = new Chart(topTeamsRevenueCtx, {
          type: 'bar',
          data: {
              labels: topTeamsLabels,
              datasets: [{
                  label: 'Total Pendapatan',
                  data: topTeamsRevenue,
                  backgroundColor: 'rgba(9, 18, 44, 0.8)', // Using a color from your CSS
                  borderColor: 'rgba(9, 18, 44, 1)',
                  borderWidth: 1,
                  borderRadius: 4,
                  borderSkipped: false,
              }]
          },
          options: {
              indexAxis: 'y', // Make it a horizontal bar chart
              responsive: true,
              maintainAspectRatio: false,
              plugins: {
                  legend: {
                      display: false
                  },
                  tooltip: {
                      callbacks: {
                          label: function(context) {
                              return 'Total Pendapatan: Rp ' + context.parsed.x.toLocaleString('id-ID');
                          }
                      }
                  }
              },
              scales: {
                  x: {
                      beginAtZero: true,
                      grid: {
                          color: 'rgba(0,0,0,0.1)'
                      },
                      ticks: {
                          color: '#666',
                          callback: function(value) {
                              return 'Rp ' + value.toLocaleString('id-ID');
                          }
                      }
                  },
                  y: {
                      grid: {
                          color: 'rgba(0,0,0,0.1)'
                      },
                      ticks: {
                          color: '#666'
                      }
                  }
              }
          }
      });

      // Update charts when week changes
      function updateCharts() {
          revenueChart.update();
          quantityChart.update();
          matchRevenueChart.update();
          topTeamsRevenueChart.update();
          ticketRevenuePieChart.update(); // Tambahkan baris ini
      }

      // Call updateCharts when page loads
      document.addEventListener('DOMContentLoaded', function() {
          updateCharts();
      });
    </script>
  </body>
</html>
