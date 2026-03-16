<?php
require_once dirname(__DIR__).'/config.php';
require_once dirname(__DIR__).'/includes/EmailVerification.php';
require_once dirname(__DIR__).'/includes/CronHeartbeat.php';

if(!isset($_GET['key']) || $_GET['key'] !== CRON_AUTH_KEY){
    http_response_code(403);
    exit('Forbidden');
}

$cronName = 'email_verification_cleanup';
$heartbeat = new CronHeartbeat(db());
$heartbeat->recordStart($cronName);

try {
    $emailVerification = new EmailVerification(db());
    $emailVerification->cleanupExpiredTokens();

    $heartbeat->recordSuccess($cronName);
    echo json_encode(['success' => true, 'message' => 'Email verification tokens cleaned up']);
} catch (Exception $e) {
    $heartbeat->recordFailure($cronName, $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
