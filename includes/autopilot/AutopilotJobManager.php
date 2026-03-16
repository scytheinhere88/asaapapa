<?php

class AutopilotJobManager
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function createJob($userId, $domains, $keywordHint = '', $userHints = '')
    {
        $jobId = $this->generateUuid();
        $totalDomains = count($domains);

        $stmt = $this->conn->prepare("
            INSERT INTO autopilot_jobs (id, user_id, total_domains, status, keyword_hint, user_hints)
            VALUES (?, ?, ?, 'pending', ?, ?)
        ");
        $stmt->execute([$jobId, $userId, $totalDomains, $keywordHint, $userHints]);

        foreach ($domains as $domain) {
            $queueId = $this->generateUuid();
            $queueStmt = $this->conn->prepare("
                INSERT INTO autopilot_queue (id, job_id, domain, status)
                VALUES (?, ?, ?, 'pending')
            ");
            $queueStmt->execute([$queueId, $jobId, trim($domain)]);
        }

        return $jobId;
    }

    public function getJob($jobId)
    {
        $stmt = $this->conn->prepare("SELECT * FROM autopilot_jobs WHERE id = ?");
        $stmt->execute([$jobId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getUserJobs($userId, $limit = 50)
    {
        $stmt = $this->conn->prepare("
            SELECT * FROM autopilot_jobs
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getJobProgress($jobId)
    {
        $job = $this->getJob($jobId);
        if (!$job) {
            return null;
        }

        $queueStmt = $this->conn->prepare("
            SELECT status, COUNT(*) as count
            FROM autopilot_queue
            WHERE job_id = ?
            GROUP BY status
        ");
        $queueStmt->execute([$jobId]);
        $statusCounts = $queueStmt->fetchAll(PDO::FETCH_KEY_PAIR);

        return [
            'job' => $job,
            'pending' => $statusCounts['pending'] ?? 0,
            'processing' => $statusCounts['processing'] ?? 0,
            'completed' => $statusCounts['completed'] ?? 0,
            'failed' => $statusCounts['failed'] ?? 0,
            'total' => $job['total_domains']
        ];
    }

    public function getJobResults($jobId)
    {
        $stmt = $this->conn->prepare("
            SELECT domain, status, result_data, error_message
            FROM autopilot_queue
            WHERE job_id = ?
            ORDER BY processed_at ASC
        ");
        $stmt->execute([$jobId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateJobStatus($jobId, $status)
    {
        $stmt = $this->conn->prepare("
            UPDATE autopilot_jobs
            SET status = ?, updated_at = NOW()
            WHERE id = ?
        ");
        return $stmt->execute([$status, $jobId]);
    }

    public function markJobCompleted($jobId)
    {
        $stmt = $this->conn->prepare("
            UPDATE autopilot_jobs
            SET status = 'completed', completed_at = NOW(), updated_at = NOW()
            WHERE id = ?
        ");
        return $stmt->execute([$jobId]);
    }

    public function deleteJob($jobId, $userId)
    {
        $stmt = $this->conn->prepare("
            DELETE FROM autopilot_jobs
            WHERE id = ? AND user_id = ?
        ");
        return $stmt->execute([$jobId, $userId]);
    }

    public function getJobStats($userId)
    {
        $stmt = $this->conn->prepare("
            SELECT
                COUNT(*) as total_jobs,
                SUM(total_domains) as total_domains_processed,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_jobs,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_jobs
            FROM autopilot_jobs
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function generateUuid()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}
