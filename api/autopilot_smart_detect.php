<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/RateLimitMiddleware.php';
require_once __DIR__ . '/../includes/SmartContentDetector.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$uid = $_SESSION['uid'] ?? null;
if (!$uid) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

checkApiRateLimit('smart_detect', 30, 60);

$input = json_decode(file_get_contents('php://input'), true);
$domain = $input['domain'] ?? '';

if (empty($domain)) {
    echo json_encode(['success' => false, 'error' => 'Domain required']);
    exit;
}

try {
    $detector = new SmartContentDetector();

    if (!$detector->isAvailable()) {
        echo json_encode([
            'success' => false,
            'error' => 'Smart content detection requires OpenAI API key',
            'fallback' => 'Please configure OPENAI_API_KEY in .env'
        ]);
        exit;
    }

    if (!preg_match('/^https?:\/\//', $domain)) {
        $url = 'https://' . $domain;
    } else {
        $url = $domain;
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 3,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
    ]);

    $html = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode < 200 || $httpCode >= 400 || !$html) {
        echo json_encode([
            'success' => false,
            'error' => "Failed to fetch website (HTTP $httpCode)",
            'help' => 'Website might be down or blocking requests'
        ]);
        exit;
    }

    $detected = $detector->detectContent($html, $domain);

    echo json_encode([
        'success' => true,
        'domain' => $domain,
        'data' => $detected,
        'source' => $detected['detection_source'] ?? 'unknown'
    ]);

} catch (Exception $e) {
    error_log("Smart detect error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Content detection failed',
        'details' => $e->getMessage()
    ]);
}
