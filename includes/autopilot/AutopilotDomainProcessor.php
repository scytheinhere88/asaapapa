<?php

class AutopilotDomainProcessor
{
    private $conn;
    private $aiApiKey;

    public function __construct($conn, $aiApiKey = null)
    {
        $this->conn = $conn;
        $this->aiApiKey = $aiApiKey ?? ($_ENV['OPENAI_API_KEY'] ?? null);
    }

    public function getPendingDomains($jobId, $limit = 10)
    {
        $stmt = $this->conn->prepare("
            SELECT id, domain
            FROM autopilot_queue
            WHERE job_id = ? AND status = 'pending'
            ORDER BY created_at ASC
            LIMIT ?
        ");
        $stmt->execute([$jobId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function markDomainProcessing($queueId)
    {
        $stmt = $this->conn->prepare("
            UPDATE autopilot_queue
            SET status = 'processing'
            WHERE id = ?
        ");
        return $stmt->execute([$queueId]);
    }

    public function markDomainCompleted($queueId, $resultData)
    {
        $stmt = $this->conn->prepare("
            UPDATE autopilot_queue
            SET status = 'completed', result_data = ?, processed_at = NOW()
            WHERE id = ?
        ");
        return $stmt->execute([json_encode($resultData), $queueId]);
    }

    public function markDomainFailed($queueId, $errorMessage)
    {
        $stmt = $this->conn->prepare("
            UPDATE autopilot_queue
            SET status = 'failed', error_message = ?, processed_at = NOW()
            WHERE id = ?
        ");
        return $stmt->execute([$errorMessage, $queueId]);
    }

    public function scrapeDomainContent($domain)
    {
        $url = 'https://' . preg_replace('#^https?://#', '', $domain);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ]);

        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error || $httpCode >= 400) {
            return ['success' => false, 'error' => $error ?: "HTTP $httpCode"];
        }

        return ['success' => true, 'html' => $html, 'url' => $url];
    }

    public function extractTextFromHtml($html)
    {
        $html = preg_replace('#<script[^>]*>.*?</script>#is', '', $html);
        $html = preg_replace('#<style[^>]*>.*?</style>#is', '', $html);
        $html = preg_replace('#<head[^>]*>.*?</head>#is', '', $html);

        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        return mb_substr($text, 0, 8000);
    }

    public function analyzeWithAI($domain, $content, $keywordHint = '')
    {
        if (!$this->aiApiKey) {
            return ['success' => false, 'error' => 'AI API key not configured'];
        }

        $prompt = $this->buildAnalysisPrompt($domain, $content, $keywordHint);

        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->aiApiKey
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a content analysis assistant. Analyze website content and identify patterns.'],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'temperature' => 0.3,
                'max_tokens' => 500
            ]),
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return ['success' => false, 'error' => "AI API returned HTTP $httpCode"];
        }

        $data = json_decode($response, true);
        if (!isset($data['choices'][0]['message']['content'])) {
            return ['success' => false, 'error' => 'Invalid AI response'];
        }

        return ['success' => true, 'analysis' => $data['choices'][0]['message']['content']];
    }

    private function buildAnalysisPrompt($domain, $content, $keywordHint)
    {
        $prompt = "Analyze this website content from $domain.\n\n";

        if ($keywordHint) {
            $prompt .= "Focus on finding: $keywordHint\n\n";
        }

        $prompt .= "Content:\n$content\n\n";
        $prompt .= "Please provide:\n";
        $prompt .= "1. Main topic/purpose\n";
        $prompt .= "2. Key patterns or repeated elements\n";
        $prompt .= "3. Suggested find/replace patterns (if any)\n";
        $prompt .= "4. Content structure notes\n";

        return $prompt;
    }

    public function updateJobProcessedCount($jobId)
    {
        $stmt = $this->conn->prepare("
            UPDATE autopilot_jobs aj
            SET processed_domains = (
                SELECT COUNT(*)
                FROM autopilot_queue
                WHERE job_id = aj.id AND status IN ('completed', 'failed')
            ),
            updated_at = NOW()
            WHERE id = ?
        ");
        return $stmt->execute([$jobId]);
    }
}
