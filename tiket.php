<?php
require_once 'config.php';

// Fetch match data from database with team information
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
            t1.logo as logo_tim1,
            t2.nama as nama_tim2,
            t2.logo as logo_tim2,
            p.id_tim1,
            p.id_tim2
        FROM pertandingan p
        LEFT JOIN tiket_reguler tr ON p.id_tiket_reguler = tr.id_tiket_reguler
        LEFT JOIN tiket_vip tv ON p.id_tiket_vip = tv.id_tiket_vip
        LEFT JOIN tim t1 ON p.id_tim1 = t1.id_tim
        LEFT JOIN tim t2 ON p.id_tim2 = t2.id_tim
        ORDER BY p.jadwal ASC, p.jam ASC
    ");
    $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group matches into weeks (8 matches per week)
    $weeks = [];
    $matchesPerWeek = 8;
    $weekNumber = 1;
    
    for ($i = 0; $i < count($matches); $i += $matchesPerWeek) {
        $weekMatches = array_slice($matches, $i, $matchesPerWeek);
        
        // Group matches by date within each week
        $groupedByDate = [];
        foreach ($weekMatches as $match) {
            $date = $match['jadwal'];
            if (!isset($groupedByDate[$date])) {
                $groupedByDate[$date] = [];
            }
            $groupedByDate[$date][] = $match;
        }
        
        $weeks[$weekNumber] = $groupedByDate;
        $weekNumber++;
    }
    
} catch(PDOException $e) {
    $matches = [];
    $weeks = [];
    $error_message = "Error mengambil data: " . $e->getMessage();
}

// Function to format Indonesian date
function formatIndonesianDate($date) {
    $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    $months = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
               'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    
    $timestamp = strtotime($date);
    $dayName = $days[date('w', $timestamp)];
    $day = date('j', $timestamp);
    $month = $months[date('n', $timestamp)];
    $year = date('Y', $timestamp);
    
    return "$dayName $day $month $year";
}

// Function to check if tickets are available
function hasAvailableTickets($jumlah_reguler, $jumlah_vip) {
    return ($jumlah_reguler > 0 || $jumlah_vip > 0);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MPL ID Season 15 - Dark System</title>
    <link rel="stylesheet" href="tiket.css">
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
  
    <div class="week-nav">
        <?php for ($i = 1; $i <= count($weeks); $i++): ?>
            <div class="week-item" onclick="showWeek(<?php echo $i; ?>)">Week <?php echo $i; ?></div>
        <?php endfor; ?>
    </div>

    <section class="week-content">
        <?php foreach ($weeks as $weekNum => $weekData): ?>
            <div id="week-<?php echo $weekNum; ?>" class="week-info">
                <h2>Week <?php echo $weekNum; ?></h2>

                <?php 
                $dayCounter = 1;
                foreach ($weekData as $date => $dayMatches): 
                ?>
                    <div class="week-schedule">
                        <div class="day-header">
                            <h2>DAY <?php echo $dayCounter; ?><br><span><?php echo formatIndonesianDate($date); ?></span></h2>
                        </div>
                        <div class="matches">
                            <?php foreach ($dayMatches as $match): ?>
                                <div class="match">
                                    <div class="match-details">
                                        <div class="teams">
                                            <div class="team">
                                                <?php if (!empty($match['logo_tim1']) && file_exists($match['logo_tim1'])): ?>
                                                    <img src="<?php echo htmlspecialchars($match['logo_tim1']); ?>" alt="<?php echo htmlspecialchars($match['nama_tim1']); ?> logo" />
                                                <?php else: ?>
                                                    <img src="img/default-logo.png" alt="Default logo" />
                                                <?php endif; ?>
                                                <span><?php echo htmlspecialchars($match['nama_tim1'] ?? 'TIM 1'); ?></span>
                                            </div>
                                            <div class="match-info">
                                                <p class="time"><?php echo date('H:i', strtotime($match['jam'])); ?></p>
                                                <p><?php echo htmlspecialchars($match['deskripsi_pertandingan']); ?></p>
                                                <p>Open Gate <?php echo date('H:i', strtotime($match['jam'] . ' -30 minutes')); ?> WIB</p>
                                            </div>
                                            <div class="team">
                                                <?php if (!empty($match['logo_tim2']) && file_exists($match['logo_tim2'])): ?>
                                                    <img src="<?php echo htmlspecialchars($match['logo_tim2']); ?>" alt="<?php echo htmlspecialchars($match['nama_tim2']); ?> logo" />
                                                <?php else: ?>
                                                    <img src="img/default-logo.png" alt="Default logo" />
                                                <?php endif; ?>
                                                <span><?php echo htmlspecialchars($match['nama_tim2'] ?? 'TIM 2'); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <?php if (hasAvailableTickets($match['jumlah_reguler'], $match['jumlah_vip'])): ?>
                                        <button class="ticket-btn" onclick="beliTiket(
                                            'Week <?php echo $weekNum; ?>', 
                                            '<?php echo formatIndonesianDate($date); ?>', 
                                            '<?php echo htmlspecialchars($match['nama_tim1']); ?>', 
                                            '<?php echo htmlspecialchars($match['nama_tim2']); ?>', 
                                            '<?php echo date('H:i', strtotime($match['jam'])); ?>',
                                            <?php echo $match['id_pertandingan']; ?>
                                        )">BELI TIKET</button>
                                    <?php else: ?>
                                        <p class="sold-out">SOLD OUT</p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php $dayCounter++; ?>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </section>

    <footer class="footer">
        <p>&copy; 2025 Dark System. All rights reserved.</p>
    </footer>

    <script>
        function showWeek(weekNumber) {
            // Hide all weeks
            const allWeeks = document.querySelectorAll('.week-info');
            allWeeks.forEach(week => {
                week.style.display = 'none';
            });
            
            // Remove active class from all week items
            const allWeekItems = document.querySelectorAll('.week-item');
            allWeekItems.forEach(item => {
                item.classList.remove('active');
            });
            
            // Show selected week
            const selectedWeek = document.getElementById('week-' + weekNumber);
            if (selectedWeek) {
                selectedWeek.style.display = 'block';
            }
            
            // Add active class to clicked week item
            const clickedItem = document.querySelector('.week-item:nth-child(' + weekNumber + ')');
            if (clickedItem) {
                clickedItem.classList.add('active');
            }
        }
        
        function beliTiket(week, date, team1, team2, time, matchId) {
            // Redirect to booking page with match ID
            window.location.href = 'pemesanan.php?match_id=' + matchId;
        }
        
        // Show first week by default
        document.addEventListener('DOMContentLoaded', function() {
            showWeek(1);
        });
    </script>
</body>
</html>
