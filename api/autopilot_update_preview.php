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

checkApiRateLimit('autopilot_update', 60, 60);

$input = json_decode(file_get_contents('php://input'), true);
$jobId = $input['job_id'] ?? '';
$corrections = $input['corrections'] ?? [];

if (empty($jobId)) {
    echo json_encode(['success' => false, 'error' => 'Job ID required']);
    exit;
}

if (empty($corrections) || !is_array($corrections)) {
    echo json_encode(['success' => false, 'error' => 'No corrections provided']);
    exit;
}

$pdo = db();

try {
    $stmt = $pdo->prepare("
        SELECT id FROM autopilot_jobs
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$jobId, $uid]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$job) {
        echo json_encode(['success' => false, 'error' => 'Job not found']);
        exit;
    }

    $pdo->beginTransaction();

    $updated = 0;
    foreach ($corrections as $correction) {
        $domain = $correction['domain'] ?? '';
        $institution = $correction['institution'] ?? '';
        $location = $correction['location_display'] ?? '';

        if (empty($domain)) {
            continue;
        }

        $resultData = json_encode([
            'institution' => $institution,
            'institution_full' => $institution,
            'location_display' => $location,
            'location_level' => $correction['location_level'] ?? 'kota',
            'province' => $correction['province'] ?? '',
            'parse_source' => 'manual',
            'email_slug' => $correction['email_slug'] ?? '',
            'search_query' => $correction['search_query'] ?? ''
        ]);

        $stmt = $pdo->prepare("
            UPDATE autopilot_queue
            SET result_data = ?, status = 'completed'
            WHERE job_id = ? AND domain = ?
        ");
        $stmt->execute([$resultData, $jobId, $domain]);

        if ($stmt->rowCount() > 0) {
            $updated++;
        }
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'updated' => $updated,
        'message' => "$updated domains updated successfully"
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Autopilot update preview error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to update preview data']);
}
