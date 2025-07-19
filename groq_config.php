<?php
// Enhanced Groq Configuration for Mobile Legends Chatbot
define('GROQ_API_KEY', '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'); // Ganti dengan API key Groq Anda
define('GROQ_API_URL', 'https://api.groq.com/openai/v1/chat/completions');

// Model configurations
define('GROQ_DEFAULT_MODEL', 'moonshotai/kimi-k2-instruct');
define('GROQ_MAX_TOKENS', 1500);
define('GROQ_TEMPERATURE', 0);
define('GROQ_TOP_P', 0.9);

class GroqAPIClient {
    private $apiKey;
    private $apiUrl;
    private $defaultModel;
    private $maxRetries;
    
    public function __construct() {
        $this->apiKey = GROQ_API_KEY;
        $this->apiUrl = GROQ_API_URL;
        $this->defaultModel = GROQ_DEFAULT_MODEL;
        $this->maxRetries = 3;
    }
    
    public function callAPI($messages, $options = []) {
        // Merge default options with provided options
        $defaultOptions = [
            'model' => $this->defaultModel,
            'temperature' => GROQ_TEMPERATURE,
            'max_tokens' => GROQ_MAX_TOKENS,
            'top_p' => GROQ_TOP_P,
            'stream' => false
        ];
        
        $apiOptions = array_merge($defaultOptions, $options);
        
        $data = [
            'model' => $apiOptions['model'],
            'messages' => $messages,
            'temperature' => $apiOptions['temperature'],
            'max_tokens' => $apiOptions['max_tokens'],
            'top_p' => $apiOptions['top_p'],
            'stream' => $apiOptions['stream']
        ];
        
        // Add system-specific parameters for better ML context
        $data['stop'] = ['Human:', 'User:', 'Assistant:'];
        $data['presence_penalty'] = 0.1; // Slight penalty for repetition
        $data['frequency_penalty'] = 0.1; // Slight penalty for frequency
        
        return $this->makeRequest($data);
    }
    
    private function makeRequest($data, $attempt = 1) {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
                'User-Agent: ML-Chatbot/1.0'
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_FOLLOWLOCATION => true
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        // Handle cURL errors
        if ($error) {
            $this->logError("cURL Error: " . $error);
            return ['error' => 'Connection error: ' . $error];
        }
        
        // Handle HTTP errors with retry logic
        if ($httpCode !== 200) {
            $this->logError("HTTP Error {$httpCode}: " . $response);
            
            // Retry logic for certain errors
            if ($this->shouldRetry($httpCode) && $attempt < $this->maxRetries) {
                sleep(pow(2, $attempt)); // Exponential backoff
                return $this->makeRequest($data, $attempt + 1);
            }
            
            return $this->handleHttpError($httpCode, $response);
        }
        
        $decodedResponse = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logError("JSON Decode Error: " . json_last_error_msg());
            return ['error' => 'Invalid response format'];
        }
        
        // Validate response structure
        if (!$this->validateResponse($decodedResponse)) {
            $this->logError("Invalid response structure: " . json_encode($decodedResponse));
            return ['error' => 'Invalid response structure'];
        }
        
        return $decodedResponse;
    }
    
    private function shouldRetry($httpCode) {
        // Retry on server errors and rate limits
        return in_array($httpCode, [429, 500, 502, 503, 504]);
    }
    
    private function handleHttpError($httpCode, $response) {
        $errorMessages = [
            400 => 'Bad request - Invalid parameters',
            401 => 'Unauthorized - Check your API key',
            403 => 'Forbidden - API key may not have required permissions',
            404 => 'Not found - Check API endpoint',
            429 => 'Rate limit exceeded - Please try again later',
            500 => 'Internal server error - Groq service issue',
            502 => 'Bad gateway - Groq service temporarily unavailable',
            503 => 'Service unavailable - Groq service overloaded',
            504 => 'Gateway timeout - Request took too long'
        ];
        
        $message = $errorMessages[$httpCode] ?? "HTTP Error {$httpCode}";
        
        // Try to extract more specific error from response
        $responseData = json_decode($response, true);
        if ($responseData && isset($responseData['error']['message'])) {
            $message .= ': ' . $responseData['error']['message'];
        }
        
        return ['error' => $message, 'http_code' => $httpCode];
    }
    
    private function validateResponse($response) {
        return isset($response['choices']) && 
               is_array($response['choices']) && 
               count($response['choices']) > 0 &&
               isset($response['choices'][0]['message']['content']);
    }
    
    private function logError($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] Groq API Error: {$message}\n";
        
        // Log to file if logs directory exists
        if (is_dir('logs')) {
            file_put_contents('logs/groq_errors.log', $logMessage, FILE_APPEND | LOCK_EX);
        }
        
        // Also log to PHP error log
        error_log($logMessage);
    }
    
    public function testConnection() {
        $testMessages = [
            [
                'role' => 'system',
                'content' => 'You are a test assistant. Respond with exactly: "Connection test successful"'
            ],
            [
                'role' => 'user',
                'content' => 'Test connection'
            ]
        ];
        
        $response = $this->callAPI($testMessages, ['max_tokens' => 50]);
        
        if (isset($response['error'])) {
            return [
                'success' => false,
                'error' => $response['error']
            ];
        }
        
        $content = $response['choices'][0]['message']['content'] ?? '';
        
        return [
            'success' => true,
            'response' => $content,
            'model' => $response['model'] ?? 'unknown',
            'usage' => $response['usage'] ?? []
        ];
    }
    
    public function getModelInfo() {
        return [
            'default_model' => $this->defaultModel,
            'max_tokens' => GROQ_MAX_TOKENS,
            'temperature' => GROQ_TEMPERATURE,
            'top_p' => GROQ_TOP_P
        ];
    }
}

// Backward compatibility functions
function callGroqAPI($messages, $options = []) {
    static $client = null;
    
    if ($client === null) {
        $client = new GroqAPIClient();
    }
    
    return $client->callAPI($messages, $options);
}

// Enhanced formatting functions for ML data
function formatDataForDisplay($data, $type) {
    switch ($type) {
        case 'teams':
            return formatTeamsData($data);
        case 'matches':
            return formatMatchesData($data);
        case 'statistics':
            return formatStatisticsData($data);
        case 'revenue':
            return formatRevenueData($data);
        default:
            return $data;
    }
}

function formatTeamsData($teams) {
    if (empty($teams)) {
        return "Tidak ada data tim yang tersedia.";
    }
    
    $formatted = "🏆 **KLASEMEN TIM MOBILE LEGENDS**\n\n";
    
    foreach ($teams as $team) {
        $winRate = calculateWinRate($team['match_wl']);
        $formatted .= "**#{$team['peringkat']} {$team['nama']}**\n";
        $formatted .= "├─ Match Point: **{$team['match_point']}** pts\n";
        $formatted .= "├─ Win-Loss: {$team['match_wl']} (**{$winRate}%** WR)\n";
        $formatted .= "├─ Net Game Win: {$team['net_game_win']}\n";
        $formatted .= "└─ Game W-L: {$team['game_wl']}\n\n";
    }
    
    return $formatted;
}

function formatMatchesData($matches) {
    if (empty($matches)) {
        return "Tidak ada data pertandingan yang tersedia.";
    }
    
    $formatted = "⚔️ **JADWAL PERTANDINGAN**\n\n";
    
    foreach ($matches as $match) {
        $date = date('d M Y', strtotime($match['jadwal']));
        $time = date('H:i', strtotime($match['jam']));
        
        $formatted .= "**{$match['tim1']} vs {$match['tim2']}**\n";
        $formatted .= "├─ 📅 {$date} | ⏰ {$time} WIB\n";
        $formatted .= "├─ 🎫 Reguler: Rp " . number_format($match['harga_reguler'], 0, ',', '.') . "\n";
        $formatted .= "├─ 🎫 VIP: Rp " . number_format($match['harga_vip'], 0, ',', '.') . "\n";
        $formatted .= "└─ 📝 {$match['deskripsi_pertandingan']}\n\n";
    }
    
    return $formatted;
}

function formatStatisticsData($stats) {
    if (empty($stats)) {
        return "Tidak ada data statistik yang tersedia.";
    }
    
    $formatted = "📊 **STATISTIK PENJUALAN TIKET**\n\n";
    
    $totalRevenue = 0;
    $totalTickets = 0;
    
    foreach ($stats as $stat) {
        $formatted .= "🎫 **Tiket {$stat['ticket_type']}**\n";
        $formatted .= "├─ Terjual: **{$stat['jumlah_terjual']}** tiket\n";
        $formatted .= "├─ Harga: Rp " . number_format($stat['harga_per_tiket'], 0, ',', '.') . "\n";
        $formatted .= "└─ Revenue: Rp " . number_format($stat['total_revenue'], 0, ',', '.') . "\n\n";
        
        $totalRevenue += $stat['total_revenue'];
        $totalTickets += $stat['jumlah_terjual'];
    }
    
    $formatted .= "💰 **TOTAL KESELURUHAN**\n";
    $formatted .= "├─ Total Tiket: **{$totalTickets}** tiket\n";
    $formatted .= "└─ Total Revenue: **Rp " . number_format($totalRevenue, 0, ',', '.') . "**\n";
    
    return $formatted;
}

function formatRevenueData($revenue) {
    $formatted = "💰 **ANALISIS REVENUE**\n\n";
    $formatted .= "Total Pendapatan: **Rp " . number_format($revenue, 0, ',', '.') . "**\n";
    
    // Add revenue breakdown if it's an array
    if (is_array($revenue)) {
        $formatted .= "\n📈 **Breakdown Revenue:**\n";
        foreach ($revenue as $key => $value) {
            $formatted .= "├─ {$key}: Rp " . number_format($value, 0, ',', '.') . "\n";
        }
    }
    
    return $formatted;
}

function calculateWinRate($matchWL) {
    if (preg_match('/(\d+)-(\d+)/', $matchWL, $matches)) {
        $wins = (int)$matches[1];
        $losses = (int)$matches[2];
        $total = $wins + $losses;
        
        if ($total > 0) {
            return round(($wins / $total) * 100, 1);
        }
    }
    return 0;
}

// Configuration validation
function validateGroqConfig() {
    $errors = [];
    
    if (!defined('GROQ_API_KEY') || GROQ_API_KEY === 'gsk_your_groq_api_key_here') {
        $errors[] = 'GROQ_API_KEY not configured properly';
    }
    
    if (!defined('GROQ_API_URL') || empty(GROQ_API_URL)) {
        $errors[] = 'GROQ_API_URL not configured';
    }
    
    if (!function_exists('curl_init')) {
        $errors[] = 'cURL extension is required but not installed';
    }
    
    return empty($errors) ? true : $errors;
}

// Initialize and test configuration
$configValidation = validateGroqConfig();
if ($configValidation !== true) {
    error_log("Groq Configuration Errors: " . implode(', ', $configValidation));
}

// Export client instance for global use
$groqClient = new GroqAPIClient();
?>