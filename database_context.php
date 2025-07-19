<?php
class DatabaseContext {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function getTeamsData() {
        try {
            $stmt = $this->pdo->query("
                SELECT 
                    id_tim,
                    nama,
                    peringkat,
                    match_point,
                    match_wl,
                    net_game_win,
                    game_wl,
                    logo
                FROM tim 
                ORDER BY peringkat ASC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error in getTeamsData: " . $e->getMessage());
            return [];
        }
    }
    
    public function getMatchesData($limit = 10, $upcoming = false) {
        try {
            $dateCondition = $upcoming ? "WHERE p.jadwal >= CURDATE()" : "";
            $orderBy = $upcoming ? "ORDER BY p.jadwal ASC, p.jam ASC" : "ORDER BY p.jadwal DESC, p.jam DESC";
            
            $stmt = $this->pdo->prepare("
                SELECT 
                    p.id_pertandingan,
                    p.deskripsi_pertandingan,
                    p.jadwal,
                    p.jam,
                    t1.nama as tim1,
                    t1.peringkat as peringkat_tim1,
                    t2.nama as tim2,
                    t2.peringkat as peringkat_tim2,
                    tr.harga as harga_reguler,
                    tr.jumlah as stok_reguler,
                    tv.harga as harga_vip,
                    tv.jumlah as stok_vip
                FROM pertandingan p
                JOIN tim t1 ON p.id_tim1 = t1.id_tim
                JOIN tim t2 ON p.id_tim2 = t2.id_tim
                JOIN tiket_reguler tr ON p.id_tiket_reguler = tr.id_tiket_reguler
                JOIN tiket_vip tv ON p.id_tiket_vip = tv.id_tiket_vip
                {$dateCondition}
                {$orderBy}
                LIMIT :limit
            ");
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error in getMatchesData: " . $e->getMessage());
            return [];
        }
    }
    
    public function getTicketSalesData() {
        try {
            $stmt = $this->pdo->query("
                SELECT 
                    p.ticket_type,
                    COUNT(*) as jumlah_terjual,
                    SUM(CASE 
                        WHEN p.ticket_type = 'reguler' THEN tr.harga
                        WHEN p.ticket_type = 'vip' THEN tv.harga
                    END) as total_revenue
                FROM penjualan p
                JOIN pertandingan pt ON p.id_pertandingan = pt.id_pertandingan
                LEFT JOIN tiket_reguler tr ON pt.id_tiket_reguler = tr.id_tiket_reguler
                LEFT JOIN tiket_vip tv ON pt.id_tiket_vip = tv.id_tiket_vip
                GROUP BY p.ticket_type
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error in getTicketSalesData: " . $e->getMessage());
            return [];
        }
    }
    
    public function getMatchStatistics($matchId = null) {
        try {
            $whereClause = $matchId ? "WHERE p.id_pertandingan = :matchId" : "";
            
            $query = "
                SELECT 
                    p.id_pertandingan,
                    t1.nama as tim1,
                    t2.nama as tim2,
                    p.jadwal,
                    p.jam,
                    COUNT(pj.id_penjualan) as total_tiket_terjual,
                    SUM(CASE WHEN pj.ticket_type = 'reguler' THEN tr.harga ELSE tv.harga END) as revenue,
                    COUNT(CASE WHEN pj.ticket_type = 'reguler' THEN 1 END) as tiket_reguler_terjual,
                    COUNT(CASE WHEN pj.ticket_type = 'vip' THEN 1 END) as tiket_vip_terjual,
                    tr.jumlah as stok_reguler,
                    tv.jumlah as stok_vip,
                    tr.harga as harga_reguler,
                    tv.harga as harga_vip
                FROM pertandingan p
                JOIN tim t1 ON p.id_tim1 = t1.id_tim
                JOIN tim t2 ON p.id_tim2 = t2.id_tim
                JOIN tiket_reguler tr ON p.id_tiket_reguler = tr.id_tiket_reguler
                JOIN tiket_vip tv ON p.id_tiket_vip = tv.id_tiket_vip
                LEFT JOIN penjualan pj ON p.id_pertandingan = pj.id_pertandingan
                {$whereClause}
                GROUP BY p.id_pertandingan, p.jadwal, p.jam, t1.nama, t2.nama, tr.harga, tv.harga, tr.jumlah, tv.jumlah
                ORDER BY p.jadwal DESC
                LIMIT 1000
            ";
            
            $stmt = $this->pdo->prepare($query);
            if ($matchId) {
                $stmt->bindValue(':matchId', $matchId, PDO::PARAM_INT);
            }
            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $results;
        } catch (Exception $e) {
            error_log("Error in getMatchStatistics: " . $e->getMessage());
            return [];
        }
    }
    
    public function getAllMatchesGroupedByWeek() {
        try {
            $stmt = $this->pdo->query("
                SELECT 
                    p.id_pertandingan,
                    p.deskripsi_pertandingan,
                    p.jadwal,
                    p.jam,
                    t1.nama as tim1,
                    t2.nama as tim2,
                    tr.harga as harga_reguler,
                    tv.harga as harga_vip
                FROM pertandingan p
                JOIN tim t1 ON p.id_tim1 = t1.id_tim
                JOIN tim t2 ON p.id_tim2 = t2.id_tim
                LEFT JOIN tiket_reguler tr ON p.id_tiket_reguler = tr.id_tiket_reguler
                LEFT JOIN tiket_vip tv ON p.id_tiket_vip = tv.id_tiket_vip
                ORDER BY p.jadwal ASC, p.jam ASC
            ");
            $allMatches = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $weeks = [];
            $matchesPerWeek = 8; // Asumsi 8 pertandingan per minggu
            $weekNumber = 1;
            
            for ($i = 0; $i < count($allMatches); $i += $matchesPerWeek) {
                $weekMatches = array_slice($allMatches, $i, $matchesPerWeek);
                $weeks[$weekNumber] = $weekMatches;
                $weekNumber++;
            }
            
            return $weeks;
        } catch (Exception $e) {
            error_log("Error in getAllMatchesGroupedByWeek: " . $e->getMessage());
            return [];
        }
    }

    public function getWeekDayMatches($week, $day) {
        $allWeeks = $this->getAllMatchesGroupedByWeek();
        
        if (!isset($allWeeks[$week])) {
            return []; // Week tidak ditemukan
        }
        
        $weekMatches = $allWeeks[$week];
        
        // Group matches by date
        $matchesByDate = [];
        foreach ($weekMatches as $match) {
            $date = $match['jadwal'];
            if (!isset($matchesByDate[$date])) {
                $matchesByDate[$date] = [];
            }
            $matchesByDate[$date][] = $match;
        }
        
        // Sort dates to ensure consistent day numbering
        ksort($matchesByDate);
        
        // Get the specific day's matches
        $dayDates = array_keys($matchesByDate);
        if (isset($dayDates[$day - 1])) { // $day is 1-indexed
            $targetDate = $dayDates[$day - 1];
            return $matchesByDate[$targetDate];
        }
        
        return []; // Day tidak memiliki pertandingan
    }

    public function getWeekDayStatistics($week, $day) {
        $matches = $this->getWeekDayMatches($week, $day);
        
        if (empty($matches)) {
            return ['matches' => [], 'stats' => []];
        }
        
        return $this->calculateStatsForMatches($matches);
    }

    public function getWeeklyStatistics($week = null) {
        $allWeeks = $this->getAllMatchesGroupedByWeek();
        
        if ($week && isset($allWeeks[$week])) {
            return $this->calculateStatsForMatches($allWeeks[$week]);
        }
        
        // Jika tidak ada week spesifik, kembalikan semua data minggu dengan statistik
        $result = [];
        foreach ($allWeeks as $weekNum => $matches) {
            $result[$weekNum] = $this->calculateStatsForMatches($matches);
        }
        return $result;
    }

    public function calculateStatsForMatches($matches) {
        $matchIds = array_column($matches, 'id_pertandingan');
        
        if (empty($matchIds)) {
            error_log("DEBUG: calculateStatsForMatches received empty matchIds array.");
            return ['matches' => $matches, 'stats' => []];
        }
        
        $placeholders = implode(',', array_fill(0, count($matchIds), '?'));
        
        try {
            // Get sales data
            $stmt = $this->pdo->prepare("
                SELECT 
                    COUNT(*) as total_tiket_terjual,
                    COUNT(CASE WHEN ticket_type = 'reguler' THEN 1 END) as reguler_terjual,
                    COUNT(CASE WHEN ticket_type = 'vip' THEN 1 END) as vip_terjual
                FROM penjualan 
                WHERE id_pertandingan IN ({$placeholders})
            ");
            $stmt->execute($matchIds);
            $salesData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Calculate revenue
            $stmt = $this->pdo->prepare("
                SELECT 
                    SUM(CASE 
                        WHEN pj.ticket_type = 'reguler' THEN tr.harga
                        WHEN pj.ticket_type = 'vip' THEN tv.harga
                    END) as total_revenue
                FROM penjualan pj
                JOIN pertandingan pt ON pj.id_pertandingan = pt.id_pertandingan
                LEFT JOIN tiket_reguler tr ON pt.id_tiket_reguler = tr.id_tiket_reguler
                LEFT JOIN tiket_vip tv ON pt.id_tiket_vip = tv.id_tiket_vip
                WHERE pj.id_pertandingan IN ({$placeholders})
            ");
            $stmt->execute($matchIds);
            $revenueData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // --- DEBUG LOGGING START ---
            error_log("DEBUG: calculateStatsForMatches - Match IDs: " . implode(', ', $matchIds));
            error_log("DEBUG: calculateStatsForMatches - Sales Data: " . json_encode($salesData));
            error_log("DEBUG: calculateStatsForMatches - Revenue Data: " . json_encode($revenueData));
            // --- DEBUG LOGGING END ---

            return [
                'matches' => $matches,
                'stats' => [
                    'total_tiket_terjual' => $salesData['total_tiket_terjual'] ?? 0,
                    'reguler_terjual' => $salesData['reguler_terjual'] ?? 0,
                    'vip_terjual' => $salesData['vip_terjual'] ?? 0,
                    'total_revenue' => $revenueData['total_revenue'] ?? 0,
                    'jumlah_pertandingan' => count($matches)
                ]
            ];
        } catch (Exception $e) {
            error_log("Error in calculateStatsForMatches: " . $e->getMessage());
            return ['matches' => $matches, 'stats' => []];
        }
    }
    
    public function getSpecificTeamData($teamName) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    t.*,
                    COUNT(DISTINCT CASE WHEN p.id_tim1 = t.id_tim OR p.id_tim2 = t.id_tim THEN p.id_pertandingan END) as total_pertandingan,
                    SUM(CASE WHEN pj.ticket_type = 'reguler' THEN tr.harga ELSE tv.harga END) as total_revenue_generated
                FROM tim t
                LEFT JOIN pertandingan p ON (p.id_tim1 = t.id_tim OR p.id_tim2 = t.id_tim)
                LEFT JOIN penjualan pj ON pj.id_pertandingan = p.id_pertandingan
                LEFT JOIN tiket_reguler tr ON p.id_tiket_reguler = tr.id_tiket_reguler
                LEFT JOIN tiket_vip tv ON p.id_tiket_vip = tv.id_tiket_vip
                WHERE LOWER(t.nama) LIKE LOWER(?)
                GROUP BY t.id_tim
            ");
            $stmt->execute(["%{$teamName}%"]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error in getSpecificTeamData: " . $e->getMessage());
            return null;
        }
    }

    public function getHistoricalSalesData() {
        try {
            $stmt = $this->pdo->query("
                SELECT 
                    p.jadwal,
                    COUNT(pj.id_penjualan) as total_tickets_sold,
                    SUM(CASE 
                        WHEN pj.ticket_type = 'reguler' THEN tr.harga
                        WHEN pj.ticket_type = 'vip' THEN tv.harga
                    END) as total_revenue
                FROM penjualan pj
                JOIN pertandingan p ON pj.id_pertandingan = p.id_pertandingan
                LEFT JOIN tiket_reguler tr ON p.id_tiket_reguler = tr.id_tiket_reguler
                LEFT JOIN tiket_vip tv ON p.id_tiket_vip = tv.id_tiket_vip
                GROUP BY p.jadwal
                ORDER BY p.jadwal ASC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error in getHistoricalSalesData: " . $e->getMessage());
            return [];
        }
    }
}
?>
