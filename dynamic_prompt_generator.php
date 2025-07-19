<?php
// dynamic_prompt_generator.php
class DynamicPromptGenerator {
    private $dbContext;
    private $intentDetector;

    public function __construct($dbContext) {
        $this->dbContext = $dbContext;
        $this->intentDetector = new IntentDetector();
    }

    public function generateContextualPrompt($userMessage, $intent, $extractedData = []) {
        $basePrompt = $this->getBasePrompt();
        $contextData = $this->getContextForIntent($intent, $userMessage, $extractedData);
        $restrictions = $this->getRestrictions();
        
        return $basePrompt . "\n\n" . $contextData . "\n\n" . $restrictions;
    }

    public function getPromptForComplexQuery($userMessage, $mainIntent, $subIntents) {
        $basePrompt = $this->getBasePrompt();
        $contextData = "Query kompleks terdeteksi. Menggabungkan konteks dari intent: " . implode(', ', $subIntents) . ".\n\n";
        
        foreach ($subIntents as $intent) {
            $contextData .= $this->getContextForIntent($intent, $userMessage) . "\n\n";
        }
        
        $restrictions = $this->getRestrictions();
        
        return $basePrompt . "\n\n" . $contextData . "\n\n" . $restrictions;
    }

    private function getBasePrompt() {
        return "Anda adalah AI Assistant khusus untuk Sistem Informasi Mobile Legends Professional League (MPL).

IDENTITAS & PERAN:
- Nama: ML Data Assistant
- Spesialisasi: Analisis data esports Mobile Legends
- Bahasa: Bahasa Indonesia profesional
- Fokus: Analisis data-driven, insight, statistik, analisis tren, dan rekomendasi/tips strategis berdasarkan data historis.

SUMBER DATA YANG TERSEDIA:
1. Tabel 'tim' - Data tim MPL (nama, peringkat, statistik)
2. Tabel 'pertandingan' - Jadwal dan hasil match
   • Sistem Week: 8 pertandingan pertama = Week 1, 8 berikutnya = Week 2, dst
   • Sistem Day: Pertandingan dikelompokkan berdasarkan tanggal dalam setiap week
   • Total: 9 weeks (72 pertandingan)
3. Tabel 'tiket_reguler' - Kuantitas tiket reguler
4. Tabel 'tiket_vip' - Kuantitas tiket VIP
5. Tabel 'penjualan' - Data revenue, transaksi, dan penjualan
6. Harga Tiket Reguler: Rp 85.000 per tiket
7. Harga Tiket VIP: Rp 145.000 per tiket

ATURAN KETAT:
❌ JANGAN pernah membahas:
- Gameplay Mobile Legends (hero, item, strategi bermain)
- Tutorial atau tips bermain
- Sejarah game Mobile Legends
- Meta game atau patch notes
- Hal-hal di luar data sistem

✅ HANYA fokus pada:
- Data tim dan peringkat dari database
- Statistik penjualan tiket
- Revenue dan KPI bisnis
- Jadwal pertandingan resmi (termasuk week dan day spesifik)
- Analisis performa berdasarkan data
- Laporan dan insight dari database
- Analisis tren data penjualan tiket dan jumlah penonton, serta memberikan insight dan rekomendasi strategis berdasarkan data historis yang tersedia.

FORMAT RESPONSE:
- Selalu awali dengan 'Berdasarkan data sistem MPL:'
- Gunakan emoji untuk kategorisasi (📊 📈 🏆 ⚔️ 💰 📅)
- Berikan angka spesifik jika tersedia
- Untuk data week/day, berikan breakdown yang jelas
- Untuk analisis dan rekomendasi, berikan insight yang jelas, angka spesifik, dan tips actionable. Jika ada proyeksi, sebutkan bahwa itu adalah estimasi berdasarkan tren historis dan tidak menjamin akurasi 100%.
- Akhiri dengan pertanyaan follow-up yang relevan

TONE: Profesional, informatif, berbasis data, responsif.

CONTOH RESPONSE YANG BENAR:
'Berdasarkan data sistem MPL Week 8 Day 1, terdapat 2 pertandingan...'
'Berdasarkan analisis data historis, Evos memiliki tingkat penjualan tiket tertinggi dengan rata-rata 95% sold out untuk setiap pertandingan mereka. Ini menunjukkan daya tarik yang kuat.'
'Berdasarkan data sistem, Tim ONIC memiliki winrate 87% dalam 6 pertandingan terakhir. Potensi menjadi daya tarik utama dalam final match. Disarankan menambah kapasitas tiket untuk match selanjutnya.'
'Berdasarkan analisis tren data historis, penjualan tiket mencapai puncaknya pada H-3 sebelum match final. Disarankan melakukan promosi tambahan pada periode H-5 hingga H-3 untuk memaksimalkan revenue.'";
    }

    private function getContextForIntent($intent, $userMessage, $extractedData = []) {
        switch ($intent) {
            case 'weekly_day_stats':
                return $this->getWeeklyDayContext($userMessage);
            case 'weekly_stats':
                return $this->getWeeklyContext($userMessage);
            case 'teams':
                return $this->getTeamsContext();
            case 'matches':
                return $this->getMatchesContext();
            case 'statistics':
                return $this->getStatisticsContext();
            case 'specific_team':
                return $this->getSpecificTeamContext($userMessage);
            case 'prediction_analysis':
                return $this->getPredictionAnalysisContext($userMessage);
            default:
                return $this->getGeneralContext();
        }
    }

    private function getWeeklyDayContext($userMessage) {
        $extracted = $this->intentDetector->extractWeekAndDay($userMessage);
        $weekNumber = $extracted['week'];
        $dayNumber = $extracted['day'];

        $context = "DATA PERTANDINGAN DAN STATISTIK UNTUK WEEK {$weekNumber}";
        if ($dayNumber) {
            $context .= " DAY {$dayNumber}";
        }
        $context .= ":\n\n";

        if (!$weekNumber) {
            return $context . "Informasi week tidak lengkap. Mohon berikan week yang spesifik (contoh: 'week 8 day 1').\n";
        }

        $matches = [];
        $stats = ['stats' => []];

        if ($dayNumber) {
            $matches = $this->dbContext->getWeekDayMatches($weekNumber, $dayNumber);
            $stats = $this->dbContext->getWeekDayStatistics($weekNumber, $dayNumber);
        } else {
            $weekData = $this->dbContext->getWeeklyStatistics($weekNumber);
            if (isset($weekData['matches'])) {
                $matches = $weekData['matches'];
            }
            if (isset($weekData['stats'])) {
                $stats['stats'] = $weekData['stats'];
            }
        }

        if (empty($matches)) {
            return $context . "Tidak ada data pertandingan yang ditemukan untuk Week {$weekNumber}";
            if ($dayNumber) {
                $context .= " Day {$dayNumber}";
            }
            $context .= ". Mohon periksa kembali data atau coba week/day lain.\n";
        }

        $context .= "⚔️ Pertandingan:\n";
        foreach ($matches as $match) {
            $context .= "   • {$match['tim1']} vs {$match['tim2']} pada {$match['jadwal']} pukul {$match['jam']}\n";
            $context .= "     (ID: {$match['id_pertandingan']}, Deskripsi: {$match['deskripsi_pertandingan']})\n";
            $context .= "     Harga Tiket Reguler: Rp " . number_format($match['harga_reguler'], 0, ',', '.') . "\n";
            $context .= "     Harga Tiket VIP: Rp " . number_format($match['harga_vip'], 0, ',', '.') . "\n\n";
        }

        if (!empty($stats['stats'])) {
            $context .= "📊 Statistik Penjualan Tiket:\n";
            $context .= "   • Total Tiket Terjual: {$stats['stats']['total_tiket_terjual']} tiket\n";
            $context .= "   • Tiket Reguler Terjual: {$stats['stats']['reguler_terjual']} tiket\n";
            $context .= "   • Tiket VIP Terjual: {$stats['stats']['vip_terjual']} tiket\n";
            $context .= "   • Total Revenue: Rp " . number_format($stats['stats']['total_revenue'], 0, ',', '.') . "\n\n";
        } else {
            $context .= "Tidak ada statistik penjualan tiket yang tersedia untuk Week {$weekNumber}";
            if ($dayNumber) {
                $context .= " Day {$dayNumber}";
            }
            $context .= ".\n\n";
        }

        return $context;
    }
    
    private function getWeeklyContext($userMessage = '') {
        $weekNumber = $this->intentDetector->extractWeekNumber($userMessage);
        
        if ($weekNumber) {
            $weekData = $this->dbContext->getWeeklyStatistics($weekNumber);
            
            if (empty($weekData['matches'])) {
                return "DATA WEEK {$weekNumber} - Tidak ada data pertandingan yang ditemukan untuk Week {$weekNumber}.";
            }
            
            $context = "DATA WEEK {$weekNumber}:\n\n";
            
            $context .= "📅 PERTANDINGAN WEEK {$weekNumber}:\n";
            foreach ($weekData['matches'] as $match) {
                $context .= "⚔️ {$match['tim1']} vs {$match['tim2']}\n";
                $context .= "   • Tanggal: {$match['jadwal']} | Jam: {$match['jam']}\n\n";
            }

            if (!empty($weekData['stats'])) {
                $stats = $weekData['stats'];
                $context .= "📊 STATISTIK WEEK {$weekNumber}:\n";
                $context .= "   • Total Pertandingan: {$stats['jumlah_pertandingan']}\n";
                $context .= "   • Total Tiket Terjual: {$stats['total_tiket_terjual']}\n";
                $context .= "   • Total Revenue: Rp " . number_format($stats['total_revenue'], 0, ',', '.') . "\n";
                $context .= "   • Tiket Reguler Terjual: {$stats['reguler_terjual']}\n";
                $context .= "   • Tiket VIP Terjual: {$stats['vip_terjual']}\n\n";
            }
            
            return $context;
        }
        
        $allWeeklyData = $this->dbContext->getWeeklyStatistics();
        // --- DEBUG LOGGING START ---
        error_log("DEBUG: getWeeklyContext - allWeeklyData content: " . json_encode($allWeeklyData));
        // --- DEBUG LOGGING END ---

        $context = "RINGKASAN DATA MINGGUAN (sistem week dari dashboard):\n\n";
        
        if (empty($allWeeklyData)) {
            return $context . "Tidak ada data mingguan yang tersedia.\n";
        }

        foreach ($allWeeklyData as $weekNum => $data) {
            $context .= "📅 Week {$weekNum}:\n";
            
            // Group matches by date for daily breakdown within the week
            $matchesByDate = [];
            foreach ($data['matches'] as $match) {
                $date = $match['jadwal'];
                if (!isset($matchesByDate[$date])) {
                    $matchesByDate[$date] = [];
                }
                $matchesByDate[$date][] = $match;
            }
            ksort($matchesByDate); // Sort dates to ensure consistent day numbering

            $dayCounter = 1;
            foreach ($matchesByDate as $date => $dayMatches) {
                // --- DEBUG LOGGING START ---
                error_log("DEBUG: getWeeklyContext - Processing Week {$weekNum}, Day {$dayCounter} ({$date})");
                error_log("DEBUG: getWeeklyContext - Day Matches for {$date}: " . json_encode($dayMatches));
                // --- DEBUG LOGGING END ---

                $dayStats = $this->dbContext->calculateStatsForMatches($dayMatches);
                
                // --- DEBUG LOGGING START ---
                error_log("DEBUG: getWeeklyContext - Day Stats for {$date}: " . json_encode($dayStats['stats']));
                // --- DEBUG LOGGING END ---

                $context .= "   Day {$dayCounter} ({$date}):\n";
                $context .= "     - " . count($dayMatches) . " pertandingan";
                // Add match names for clarity
                $matchNames = array_map(function($m) { return "{$m['tim1']} vs {$m['tim2']}"; }, $dayMatches);
                $context .= " (" . implode(' & ', $matchNames) . ")\n";
                $context .= "     - Tiket terjual: " . ($dayStats['stats']['total_tiket_terjual'] ?? 0) . " tiket\n";
                $context .= "     - Revenue: Rp " . number_format($dayStats['stats']['total_revenue'] ?? 0, 0, ',', '.') . "\n";
                $dayCounter++;
            }
            $context .= "\n";
        }
        
        return $context;
    }
    
    private function getTeamsContext() {
        $teams = $this->dbContext->getTeamsData();
        $context = "DATA TIM MOBILE LEGENDS (dari tabel 'tim'):\n\n";
        
        if (empty($teams)) {
            return $context . "Tidak ada data tim yang tersedia.\n";
        }

        foreach ($teams as $team) {
            $winRate = $this->calculateWinRate($team['match_wl']);
            $context .= "🏆 {$team['nama']}\n";
            $context .= "   • ID: {$team['id_tim']}\n";
            $context .= "   • Peringkat: #{$team['peringkat']}\n";
            $context .= "   • Match Point: {$team['match_point']}\n";
            $context .= "   • Win-Loss: {$team['match_wl']} (Win Rate: {$winRate}%)\n";
            $context .= "   • Net Game Win: {$team['net_game_win']}\n";
            $context .= "   • Game W-L: {$team['game_wl']}\n\n";
        }
        
        return $context;
    }
    
    private function calculateWinRate($matchWL) {
        if (preg_match('/(\d+)-(\d+)/', $matchWL, $matches)) {
            $wins = (int)$matches[1];
            $losses = (int)$matches[2];
            $total = (int)$wins + (int)$losses; 
            
            if ($total > 0) {
                return round(($wins / $total) * 100, 1);
            }
        }
        return 0;
    }
    
    private function getMatchesContext() {
        $upcomingMatches = $this->dbContext->getMatchesData(8, true);
        $recentMatches = $this->dbContext->getMatchesData(5, false);
        $matchStats = $this->dbContext->getMatchStatistics(); 

        $context = "DATA PERTANDINGAN (dari tabel 'pertandingan'):\n\n";
        
        if (!empty($upcomingMatches)) {
            $context .= "📅 PERTANDINGAN MENDATANG:\n";
            foreach ($upcomingMatches as $match) {
                $context .= "⚔️ {$match['tim1']} vs {$match['tim2']}\n";
                $context .= "   • ID: {$match['id_pertandingan']}\n";
                $context .= "   • Tanggal: {$match['jadwal']} | Jam: {$match['jam']}\n";
                $context .= "   • Deskripsi: {$match['deskripsi_pertandingan']}\n";
                $context .= "   • Harga Tiket Reguler: Rp " . number_format($match['harga_reguler'], 0, ',', '.') . "\n";
                $context .= "   • Harga Tiket VIP: Rp " . number_format($match['harga_vip'], 0, ',', '.') . "\n\n";
            }
        } else {
            $context .= "Tidak ada pertandingan mendatang yang tersedia.\n\n";
        }
        
        if (!empty($recentMatches)) {
            $context .= "📊 PERTANDINGAN TERBARU:\n";
            foreach ($recentMatches as $match) {
                $context .= "⚔️ {$match['tim1']} vs {$match['tim2']} ({$match['jadwal']})\n";
                $currentMatchStats = array_filter($matchStats, function($stat) use ($match) {
                    return $stat['id_pertandingan'] == $match['id_pertandingan'];
                });
                $currentMatchStats = reset($currentMatchStats); 

                if ($currentMatchStats) {
                    $context .= "   • Total Tiket Terjual: {$currentMatchStats['total_tiket_terjual']}\n";
                    $context .= "   • Tiket Reguler Terjual: {$currentMatchStats['tiket_reguler_terjual']}\n";
                    $context .= "   • Tiket VIP Terjual: {$currentMatchStats['tiket_vip_terjual']}\n";
                    $context .= "   • Revenue: Rp " . number_format($currentMatchStats['revenue'], 0, ',', '.') . "\n\n";
                } else {
                    $context .= "   • Data penjualan tiket tidak tersedia untuk pertandingan ini.\n\n";
                }
            }
        } else {
            $context .= "Tidak ada pertandingan terbaru yang tersedia.\n";
        }
        
        return $context;
    }
    
    private function getStatisticsContext() {
        $ticketSales = $this->dbContext->getTicketSalesData();
        $matchStats = $this->dbContext->getMatchStatistics();
        
        $context = "STATISTIK PENJUALAN TIKET (dari tabel 'penjualan', 'tiket_reguler', 'tiket_vip'):\n\n";
        
        if (empty($ticketSales)) {
            $context .= "Tidak ada data penjualan tiket yang tersedia.\n\n";
        } else {
            $totalRevenue = 0;
            $totalTickets = 0;
            
            foreach ($ticketSales as $sale) {
                $context .= "🎫 Tiket {$sale['ticket_type']}:\n";
                $context .= "   • Jumlah Terjual: {$sale['jumlah_terjual']} tiket\n";
                $context .= "   • Total Revenue: Rp " . number_format($sale['total_revenue'], 0, ',', '.') . "\n\n";
                
                $totalRevenue += $sale['total_revenue'];
                $totalTickets += $sale['jumlah_terjual'];
            }
            
            $context .= "💰 RINGKASAN TOTAL:\n";
            $context .= "   • Total Tiket Terjual: {$totalTickets} tiket\n";
            $context .= "   • Total Revenue: Rp " . number_format($totalRevenue, 0, ',', '.') . "\n\n";
        }
        
        if (!empty($matchStats)) {
            $context .= "📊 STATISTIK PER PERTANDINGAN (Top 5 Terbaru):\n";
            foreach (array_slice($matchStats, 0, 5) as $stat) {
                $context .= "⚔️ {$stat['tim1']} vs {$stat['tim2']} ({$stat['jadwal']})\n";
                $context .= "   • Total Tiket Terjual: {$stat['total_tiket_terjual']}\n";
                $context .= "   • Tiket Reguler Terjual: {$stat['tiket_reguler_terjual']}\n";
                $context .= "   • Tiket VIP Terjual: {$stat['tiket_vip_terjual']}\n";
                $context .= "   • Revenue: Rp " . number_format($stat['revenue'], 0, ',', '.') . "\n\n";
            }
        } else {
            $context .= "Tidak ada statistik pertandingan yang tersedia.\n";
        }
        
        return $context;
    }
    
    private function getSpecificTeamContext($userMessage) {
        $teamNames = ['RRQ', 'ONIC', 'EVOS', 'GEEK', 'BIGETRON', 'ALTER EGO', 'LIQUID', 'DEWA', 'NAVI'];
        $foundTeam = null;
        
        foreach ($teamNames as $team) {
            if (stripos($userMessage, $team) !== false) {
                $foundTeam = $team;
                break;
            }
        }
        
        if ($foundTeam) {
            $teamData = $this->dbContext->getSpecificTeamData($foundTeam);
            $matchStats = $this->dbContext->getMatchStatistics(); 
            
            $teamMatchesStats = array_filter($matchStats, function($stat) use ($teamData) {
                if (!$teamData) return false; 
                return $stat['tim1'] == $teamData['nama'] || $stat['tim2'] == $teamData['nama'];
            });

            if ($teamData) {
                $winRate = $this->calculateWinRate($teamData['match_wl']);
                $context = "DATA SPESIFIK TIM {$teamData['nama']}:\n\n";
                $context .= "🏆 Informasi Tim:\n";
                $context .= "   • Peringkat: #{$teamData['peringkat']}\n";
                $context .= "   • Match Point: {$teamData['match_point']}\n";
                $context .= "   • Match W-L: {$teamData['match_wl']} (Win Rate: {$winRate}%)\n";
                $context .= "   • Net Game Win: {$teamData['net_game_win']}\n";
                $context .= "   • Game W-L: {$teamData['game_wl']}\n";
                $context .= "   • Total Pertandingan: {$teamData['total_pertandingan']}\n";
                $context .= "   • Total Revenue Terkait Tim: Rp " . number_format($teamData['total_revenue_generated'] ?? 0, 0, ',', '.') . "\n\n";
                
                if (!empty($teamMatchesStats)) {
                    $context .= "📊 Statistik Penjualan Tiket untuk Pertandingan Terkait Tim {$teamData['nama']} (Terbaru):\n";
                    usort($teamMatchesStats, function($a, $b) {
                        return strtotime($b['jadwal']) - strtotime($a['jadwal']);
                    });
                    foreach ($teamMatchesStats as $stat) {
                        $context .= "⚔️ {$stat['tim1']} vs {$stat['tim2']} ({$stat['jadwal']})\n";
                        $context .= "   • Total Tiket Terjual: {$stat['total_tiket_terjual']}\n";
                        $context .= "   • Tiket Reguler Terjual: {$stat['tiket_reguler_terjual']}\n";
                        $context .= "   • Tiket VIP Terjual: {$stat['tiket_vip_terjual']}\n";
                        $context .= "   • Revenue: Rp " . number_format($stat['revenue'], 0, ',', '.') . "\n\n";
                    }
                } else {
                    $context .= "Tidak ada statistik penjualan tiket pertandingan terkait tim {$teamData['nama']} yang tersedia.\n\n";
                }
                
                return $context;
            }
        }
        
        return $this->getTeamsContext();
    }
    
    private function getGeneralContext() {
        $teams = $this->dbContext->getTeamsData();
        $upcomingMatches = $this->dbContext->getMatchesData(3, true);
        
        $context = "RINGKASAN DATA SISTEM:\n\n";
        $context .= "🏆 Total Tim Terdaftar: " . count($teams) . "\n";
        $context .= "📅 Jumlah Pertandingan Mendatang (Top 3): " . count($upcomingMatches) . "\n\n";
        
        if (!empty($teams)) {
            $context .= "🥇 Tim Peringkat 1: {$teams[0]['nama']} ({$teams[0]['match_point']} pts)\n";
        } else {
            $context .= "Tidak ada data tim yang tersedia.\n";
        }
        
        return $context;
    }

    private function getPredictionAnalysisContext($userMessage) {
        $historicalSales = $this->dbContext->getHistoricalSalesData();
        $weeklyStats = $this->dbContext->getWeeklyStatistics();

        $context = "DATA HISTORIS UNTUK ANALISIS DAN REKOMENDASI:\n\n";

        if (empty($historicalSales) && empty($weeklyStats)) {
            return $context . "Tidak ada data historis penjualan tiket atau statistik mingguan yang tersedia untuk analisis. Mohon pastikan database terisi.\n";
        }

        if (!empty($historicalSales)) {
            $context .= "📈 DATA PENJUALAN TIKET HISTORIS (per tanggal):\n";
            foreach ($historicalSales as $data) {
                $context .= "   • Tanggal: {$data['jadwal']} | Tiket Terjual: {$data['total_tickets_sold']} | Revenue: Rp " . number_format($data['total_revenue'], 0, ',', '.') . "\n";
            }
            $context .= "\n";
        }

        if (!empty($weeklyStats)) {
            $context .= "📊 DATA STATISTIK MINGGUAN HISTORIS:\n";
            foreach ($weeklyStats as $weekNum => $data) {
                $context .= "   • Week {$weekNum}: Pertandingan: {$data['stats']['jumlah_pertandingan']} | Tiket Terjual: {$data['stats']['total_tiket_terjual']} | Revenue: Rp " . number_format($data['stats']['total_revenue'] ?? 0, 0, ',', '.') . "\n";
            }
            $context .= "\n";
        }

        $context .= "INSTRUKSI UNTUK ANALISIS DATA DAN REKOMENDASI STRATEGIS:\n";
        $context .= "1. LAKUKAN ANALISIS MENDALAM dari data historis penjualan tiket dan jumlah penonton yang diberikan. Identifikasi pola, peningkatan, penurunan, atau stabilitas.\n";
        $context .= "2. BERIKAN INSIGHT DAN REKOMENDASI STRATEGIS untuk meningkatkan penjualan tiket atau memanfaatkan tren yang ada. Contoh: tips promosi, penyesuaian kapasitas, atau fokus pada tim tertentu.\n";
        $context .= "3. IDENTIFIKASI tim atau pertandingan dengan potensi penjualan tinggi/rendah berdasarkan data historis dan berikan saran spesifik.\n";
        $context .= "4. BERIKAN ANGKA SPESIFIK DAN PERSENTASE untuk mendukung insight dan rekomendasi Anda. Jika ada proyeksi, berikan estimasi dalam bentuk angka atau rentang yang realistis.\n";
        $context .= "5. SEBUTKAN ASUMSI yang Anda gunakan dalam analisis atau proyeksi (misalnya, 'dengan asumsi tren saat ini berlanjut', 'faktor eksternal tidak berubah', 'tidak ada event besar lain yang bersaing').\n";
        $context .= "6. Jika data tidak cukup untuk analisis atau rekomendasi yang kuat, NYATAKAN HAL TERSEBUT DENGAN JELAS DAN BERIKAN ALASANNYA.\n";
        $context .= "7. SELALU INGATKAN bahwa proyeksi adalah estimasi dan tidak menjamin akurasi 100%. Gunakan frasa seperti 'diperkirakan', 'diproyeksikan', 'kemungkinan'.\n";
        $context .= "8. Gunakan bahasa yang jelas, ringkas, dan mudah dimengerti. Fokus pada insight, rekomendasi, dan angka proyeksi.\n";
        $context .= "9. CONTOH FORMAT ANALISIS/REKOMENDASI YANG DIHARAPKAN: \n";
        $context .= "   'Berdasarkan analisis data historis:\n";
        $context .= "   📈 Terlihat tren [peningkatan/penurunan/stabil] sebesar [persentase/jumlah] per minggu/periode.\n";
        $context .= "   🏆 Evos memiliki tingkat penjualan tiket tertinggi dengan rata-rata 95% sold out untuk setiap pertandingan mereka. Ini menunjukkan daya tarik yang kuat.\n";
        $context .= "   ⚔️ Tim ONIC memiliki winrate 87% dalam 6 pertandingan terakhir. Potensi menjadi daya tarik utama dalam final match. Disarankan menambah kapasitas tiket untuk match selanjutnya.\n";
        $context .= "   💰 Penjualan tiket mencapai puncaknya pada H-3 sebelum match final. Disarankan melakukan promosi tambahan pada periode H-5 hingga H-3 untuk memaksimalkan revenue.
   Asumsi: [Sebutkan asumsi, misal: tren saat ini berlanjut, tidak ada perubahan signifikan pada popularitas MPL].'\n";
        
        return $context;
    }
    
    private function getRestrictions() {
        return "BATASAN PENTING:
❌ JANGAN menjawab pertanyaan di luar data Mobile Legends Professional League yang tersedia di sistem.
❌ JANGAN memberikan informasi yang tidak ada di database.
❌ JANGAN membahas topik umum, gameplay, hero, item, strategi, atau sejarah game Mobile Legends.
❌ JANGAN memberikan prediksi atau analisis spekulatif TANPA DASAR DATA.

✅ HANYA jawab berdasarkan data yang tersedia di sistem database.
✅ Jika data tidak tersedia untuk pertanyaan spesifik, katakan dengan jelas dan sopan.
✅ Selalu sebutkan bahwa data berasal dari sistem database.
✅ Gunakan format yang rapi dan mudah dibaca dengan emoji yang relevan.
✅ Untuk week/day spesifik, berikan breakdown yang detail.
✅ Untuk analisis dan rekomendasi, pastikan untuk menyatakan bahwa ini adalah estimasi berdasarkan data historis dan tidak menjamin akurasi 100%. Berikan penjelasan tren yang mendasari analisis, insight, rekomendasi, dan angka proyeksi yang jelas.

CONTOH RESPONSE YANG BENAR:
'Berdasarkan data sistem MPL Week 8 Day 1, terdapat 2 pertandingan...'
'Berdasarkan analisis data historis, penjualan tiket untuk minggu depan diperkirakan akan mencapai Rp 150 juta, dengan asumsi tren positif saat ini berlanjut.'
'Berdasarkan data sistem, Tim ONIC memiliki winrate 87% dalam 6 pertandingan terakhir. Potensi menjadi daya tarik utama dalam final match. Disarankan menambah kapasitas tiket untuk match selanjutnya.'

CONTOH RESPONSE YANG SALAH:
'Mobile Legends adalah game MOBA yang populer...' (terlalu umum)";
    }
}
?>
