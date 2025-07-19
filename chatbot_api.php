<?php
session_start();
require_once 'config.php';
require_once 'groq_config.php'; 
require_once 'database_context.php';
require_once 'dynamic_prompt_generator.php'; // Ini adalah file yang berisi class FocusedPromptGenerator
require_once 'intent_detector.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (!isset($_SESSION['akun_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$userMessage = $input['message'] ?? '';

if (empty($userMessage)) {
    echo json_encode(['error' => 'Message is required']);
    exit;
}

try {
    // Initialize components
    $dbContext = new DatabaseContext($pdo);
    $intentDetector = new IntentDetector();
    
    // PERBAIKAN UTAMA: Gunakan nama class yang benar: FocusedPromptGenerator
    $promptGenerator = new DynamicPromptGenerator($dbContext);
    
    // Detect intent
    $intent = $intentDetector->detectIntent($userMessage);
    
    // Generate focused system prompt
    // userMessage diteruskan ke generateSystemPrompt untuk ekstraksi konteks di dalamnya
    $systemPrompt = $promptGenerator->generateContextualPrompt($userMessage, $intent);
    
    // Fungsi enhanceUserMessage tetap digunakan untuk penambahan instruksi non-LLM spesifik
    $enhancedUserMessage = enhanceUserMessage($userMessage, $intent, $intentDetector);
    
    $messages = [
        [
            'role' => 'system',
            'content' => $systemPrompt
        ],
        [
            'role' => 'user',
            'content' => $enhancedUserMessage
        ]
    ];
    
    // Pastikan $groqClient sudah diinisialisasi secara global dari groq_config.php
    global $groqClient;
    if (!isset($groqClient) || !($groqClient instanceof GroqAPIClient)) {
        // Fallback atau error jika client tidak terinisialisasi
        throw new Exception("GroqAPIClient not initialized. Check groq_config.php.");
    }

    // Panggil API menggunakan instance $groqClient
    $response = $groqClient->callAPI($messages);
    
    if (isset($response['error'])) {
        error_log("Groq API Error in chatbot_api.php: " . json_encode($response));
        echo json_encode(['error' => $response['error']]);
    } else {
        $botResponse = $response['choices'][0]['message']['content'];
        
        // Validasi respons untuk memastikan tetap dalam konteks
        $validatedResponse = validateResponse($botResponse, $intent);
        
        echo json_encode([
            'response' => $validatedResponse,
            'intent' => $intent,
            'context' => 'mobile_legends_data',
            'model' => $response['model'] ?? 'unknown',
            'usage' => $response['usage'] ?? null
        ]);
    }
    
} catch (Exception $e) {
    error_log("Chatbot API Error: " . $e->getMessage());
    echo json_encode(['error' => 'Internal server error: ' . $e->getMessage()]);
}

// Fungsi enhanceUserMessage dan validateResponse tetap sama seperti sebelumnya
function enhanceUserMessage($message, $intent, $intentDetector) {
    $enhanced = $message;
    
    switch ($intent) {
        case 'weekly_stats':
            $weekNumber = $intentDetector->extractWeekNumber($message);
            if ($weekNumber) {
                $enhanced .= " (Fokus pada data Week {$weekNumber} dari sistem dashboard)";
            }
            break;
        case 'weekly_day_stats': // NEW CASE
            $extracted = $intentDetector->extractWeekAndDay($message);
            $weekNumber = $extracted['week'];
            $dayNumber = $extracted['day'];
            if ($weekNumber && $dayNumber) {
                $enhanced .= " (Fokus pada data Week {$weekNumber} Day {$dayNumber} dari sistem dashboard)";
            }
            break;
        case 'specific_team':
            $teamName = $intentDetector->extractTeamName($message);
            if ($teamName) {
                $enhanced .= " (Fokus pada data tim {$teamName} dari database)";
            }
            break;
        case 'statistics':
            $enhanced .= " (Berikan data statistik dari tabel penjualan, tiket_reguler, dan tiket_vip)";
            break;
        case 'matches':
            $enhanced .= " (Berikan data dari tabel pertandingan dengan relasi ke tabel tim)";
            break;
        case 'prediction_analysis': // NEW CASE
            $enhanced .= " (Lakukan analisis tren, berikan insight, dan rekomendasi strategis penjualan tiket/penonton berdasarkan data historis)";
            break;
    }
    
    return $enhanced;
}

function validateResponse($response, $intent) {
    // Check if response contains out-of-context information
    $forbiddenTopics = [
        'sejarah mobile legends',
        'cara bermain',
        'hero mobile legends',
        'item build',
        'strategi game',
        'tutorial',
        'tips bermain'
    ];
    
    $lowerResponse = strtolower($response);
    
    foreach ($forbiddenTopics as $topic) {
        if (strpos($lowerResponse, $topic) !== false) {
            return "Maaf, saya hanya dapat memberikan informasi berdasarkan data yang tersedia di sistem database Mobile Legends Professional League. Silakan tanyakan tentang:\n\n" .
                   "🏆 Data tim dan peringkat\n" .
                   "⚔️ Jadwal pertandingan\n" .
                   "📊 Statistik penjualan tiket\n" .
                   "💰 Data revenue dan KPI\n\n" .
                   "Apakah ada yang ingin Anda ketahui tentang data-data tersebut?";
        }
    }
    
    // Ensure response mentions data source
    if (!preg_match('/(berdasarkan data|dari sistem|database|tabel|analisis data historis|proyeksi berdasarkan data)/i', $response)) {
        $response = "Berdasarkan data di sistem kami:\n\n" . $response;
    }
    
    return $response;
}
?>
