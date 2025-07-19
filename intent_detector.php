<?php
class IntentDetector {
    private $intentPatterns = [
        'teams' => [
            'keywords' => ['tim', 'team', 'klasemen', 'peringkat', 'ranking', 'leaderboard', 'posisi'],
            'patterns' => ['/siapa.*peringkat/i', '/tim.*terbaik/i', '/klasemen/i']
        ],
        'matches' => [
            'keywords' => ['pertandingan', 'jadwal', 'match', 'vs', 'lawan', 'main'],
            'patterns' => ['/kapan.*main/i', '/jadwal.*pertandingan/i', '/.*vs.*/i']
        ],
        'statistics' => [
            'keywords' => ['statistik', 'stats', 'data', 'total', 'jumlah', 'penjualan', 'revenue', 'pendapatan'],
            'patterns' => ['/berapa.*total/i', '/statistik.*penjualan/i', '/data.*tiket/i']
        ],
        'ticket_sales' => [
            'keywords' => ['tiket', 'ticket', 'penjualan', 'terjual', 'harga', 'reguler', 'vip'],
            'patterns' => ['/harga.*tiket/i', '/tiket.*terjual/i', '/penjualan.*tiket/i']
        ],
        'specific_team' => [
            'keywords' => ['rrq', 'onic', 'evos', 'geek', 'bigetron', 'alter ego', 'liquid', 'dewa', 'navi'],
            'patterns' => ['/rrq.*hoshi/i', '/geek.*fam/i', '/alter.*ego/i']
        ],
        'weekly_stats' => [
            'keywords' => ['week', 'minggu', 'mingguan'],
            'patterns' => ['/week.*\d+/i', '/minggu.*ke/i', '/data.*mingguan/i']
        ],
        'revenue' => [
            'keywords' => ['revenue', 'pendapatan', 'keuntungan', 'uang', 'rupiah'],
            'patterns' => ['/berapa.*pendapatan/i', '/total.*revenue/i', '/keuntungan/i']
        ],
        // NEW: Intent for prediction and analysis
        'prediction_analysis' => [
            'keywords' => ['prediksi', 'proyeksi', 'analisis', 'tren', 'kedepannya', 'masa depan', 'penonton', 'estimasi'],
            'patterns' => ['/prediksi.*penjualan/i', '/proyeksi.*tiket/i', '/analisis.*penonton/i', '/tren.*penjualan/i', '/estimasi.*penonton/i']
        ]
    ];
    
    public function detectIntent($message) {
        $message = strtolower($message);
        
        // Prioritaskan deteksi yang lebih spesifik terlebih dahulu
        // Deteksi "week X day Y"
        if (preg_match('/(week|minggu)\s*(\d+)\s*(day|hari)\s*(\d+)/i', $message)) {
            return 'weekly_day_stats';
        }
        
        // Deteksi "week X"
        if (preg_match('/(week|minggu)\s*(\d+)/i', $message)) {
            return 'weekly_stats';
        }
        
        // Deteksi tim spesifik (jika ada nama tim yang disebutkan)
        $teamName = $this->extractTeamName($message);
        if ($teamName) {
            return 'specific_team';
        }

        // NEW: Deteksi prediksi/analisis
        foreach ($this->intentPatterns['prediction_analysis']['patterns'] as $pattern) {
            if (preg_match($pattern, $message)) {
                return 'prediction_analysis';
            }
        }
        foreach ($this->intentPatterns['prediction_analysis']['keywords'] as $keyword) {
            if (strpos($message, $keyword) !== false) {
                return 'prediction_analysis';
            }
        }

        // Deteksi intent lainnya
        foreach ($this->intentPatterns as $intent => $patterns) {
            // Skip prediction_analysis as it's handled above
            if ($intent === 'prediction_analysis') continue;

            foreach ($patterns['patterns'] as $pattern) {
                if (preg_match($pattern, $message)) {
                    return $intent;
                }
            }
            foreach ($patterns['keywords'] as $keyword) {
                if (strpos($message, $keyword) !== false) {
                    return $intent;
                }
            }
        }
        
        return 'general';
    }
    
    public function extractWeekNumber($message) {
        if (preg_match('/(week|minggu)\s*(\d+)/i', $message, $matches)) {
            return (int)$matches[2];
        }
        return null;
    }

    public function extractDayNumber($message) {
        if (preg_match('/(day|hari)\s*(\d+)/i', $message, $matches)) {
            return (int)$matches[2];
        }
        return null;
    }

    public function extractWeekAndDay($message) {
        $week = $this->extractWeekNumber($message);
        $day = $this->extractDayNumber($message);
        
        return [
            'week' => $week,
            'day' => $day
        ];
    }
    
     public function extractTeamName($message) {
        $teamNames = [
            'RRQ', 'ONIC', 'EVOS', 'GEEK', 'BIGETRON', 
            'ALTER EGO', 'LIQUID', 'DEWA', 'NAVI'
        ];
        
        foreach ($teamNames as $team) {
            if (stripos($message, $team) !== false) {
                return $team;
            }
        }
        
        return null;
    }
}

?>
