<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/RateLimitMiddleware.php';

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

checkApiRateLimit('autopilot_preview', 60, 60);

$input = json_decode(file_get_contents('php://input'), true);
$domains = $input['domains'] ?? [];
$keywordHint = $input['keyword_hint'] ?? '';

if (empty($domains)) {
    echo json_encode(['success' => false, 'error' => 'No domains provided']);
    exit;
}

if (count($domains) > 50) {
    echo json_encode(['success' => false, 'error' => 'Maximum 50 domains for preview']);
    exit;
}

require_once __DIR__ . '/../api/ai_parser.php';

try {
    $parser = new AIDomainParser();

    $results = [];

    if ($parser->isAvailable()) {
        $parsedData = $parser->parseBatch($domains, $keywordHint, function($processed, $total, $type, $data = null) {
        });

        foreach ($parsedData as $domain => $data) {
            $results[] = [
                'domain' => $domain,
                'institution' => $data['institution'] ?? '',
                'institution_full' => $data['institution_full'] ?? '',
                'location_display' => $data['location_display'] ?? '',
                'location_level' => $data['location_level'] ?? 'kota',
                'province' => $data['province'] ?? '',
                'parse_source' => $data['parse_source'] ?? 'ai',
                'email_slug' => $data['email_slug'] ?? '',
                'search_query' => $data['search_query'] ?? ''
            ];
        }
    } else {
        require_once __DIR__ . '/../api/scraper.php';

        foreach ($domains as $domain) {
            $parsed = DataScraper::parseDomain($domain, $keywordHint);
            $results[] = [
                'domain' => $domain,
                'institution' => $parsed['institution'] ?? '',
                'institution_full' => $parsed['institution_full'] ?? '',
                'location_display' => $parsed['location_display'] ?? '',
                'location_level' => $parsed['location_level'] ?? 'kota',
                'province' => $parsed['province'] ?? '',
                'parse_source' => 'regex',
                'email_slug' => $parsed['email_slug'] ?? '',
                'search_query' => $parsed['search_query'] ?? ''
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'results' => $results,
        'total' => count($results),
        'ai_available' => $parser->isAvailable()
    ]);

} catch (Exception $e) {
    error_log("Autopilot preview error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to preview domains']);
}
