<?php

class SmartContentDetector
{
    private $openaiKey;
    private $cache = [];

    public function __construct()
    {
        if (defined('OPENAI_API_KEY') && !in_array(OPENAI_API_KEY, ['', 'YOUR_OPENAI_API_KEY'])) {
            $this->openaiKey = OPENAI_API_KEY;
        }
    }

    public function isAvailable(): bool
    {
        return !empty($this->openaiKey);
    }

    public function detectContent(string $html, string $domain): array
    {
        if (!$this->isAvailable()) {
            return $this->regexDetection($html, $domain);
        }

        $cacheKey = md5($domain . substr($html, 0, 1000));
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $cleanText = $this->cleanHTML($html);

        if (mb_strlen($cleanText) > 8000) {
            $cleanText = mb_substr($cleanText, 0, 8000) . '...';
        }

        $prompt = $this->buildDetectionPrompt($domain);
        $result = $this->callAI($prompt, $cleanText);

        if ($result === false) {
            $result = $this->regexDetection($html, $domain);
        }

        $this->cache[$cacheKey] = $result;
        return $result;
    }

    private function cleanHTML(string $html): string
    {
        $html = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/si', '', $html);
        $html = preg_replace('/<style\b[^<]*(?:(?!<\/style>)<[^<]*)*<\/style>/si', '', $html);
        $html = preg_replace('/<nav\b[^<]*(?:(?!<\/nav>)<[^<]*)*<\/nav>/si', '', $html);

        $text = strip_tags($html);

        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        return $text;
    }

    private function buildDetectionPrompt(string $domain): string
    {
        return <<<PROMPT
Extract contact information from this Indonesian website content.

Website: {$domain}

Extract the following (return "not found" if not present):

1. PHONE NUMBERS:
   - Main phone number (office landline)
   - Mobile phone numbers
   - WhatsApp number (if different)
   - Format: Keep original format with country code if present

2. EMAIL ADDRESSES:
   - Primary email (usually info@, contact@, or admin@)
   - Official emails only (ignore personal emails)

3. PHYSICAL ADDRESS:
   - Full address with street name, number
   - Include kecamatan, city, postal code if present
   - Format: Complete readable address

4. LOCATION:
   - City/Regency name (Kota/Kabupaten)
   - Province name

5. ORGANIZATION NAME:
   - Official institution/organization name
   - Full name, not abbreviation

CRITICAL RULES:
- Extract ONLY from content, DO NOT hallucinate
- If multiple phones found, return the most official one (usually listed first)
- Prefer shorter, cleaner addresses over very long descriptions
- Return "not found" for any field without clear data
- Keep Indonesian spelling and formatting

OUTPUT (valid JSON only):
{
  "phones": ["+62-21-5555-0100", "0811-2345-6789"],
  "email": "info@domain.org",
  "address": "Jl. Ir. H. Juanda No. 7, Kec. Pasuruan, Kota Pasuruan 67123",
  "city": "Kota Pasuruan",
  "province": "Jawa Timur",
  "organization": "Asosiasi Perguruan Tinggi Swasta Indonesia Kota Pasuruan"
}

Respond with ONLY valid JSON. No markdown, no explanation.
PROMPT;
    }

    private function callAI(string $prompt, string $content): array|false
    {
        $payload = json_encode([
            'model' => 'gpt-4o-mini',
            'temperature' => 0,
            'max_tokens' => 800,
            'messages' => [
                ['role' => 'system', 'content' => $prompt],
                ['role' => 'user', 'content' => "Content:\n\n" . $content],
            ],
        ]);

        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 45,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->openaiKey,
            ],
        ]);

        $resp = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$resp) {
            return false;
        }

        $data = json_decode($resp, true);
        if (!$data || !isset($data['choices'][0]['message']['content'])) {
            return false;
        }

        $content = $data['choices'][0]['message']['content'];

        if (preg_match('/```json\s*(\{.*?\})\s*```/s', $content, $m)) {
            $content = $m[1];
        } elseif (preg_match('/(\{.*?\})/s', $content, $m)) {
            $content = $m[1];
        }

        $parsed = json_decode($content, true);
        if (!$parsed) {
            return false;
        }

        return [
            'phones' => $parsed['phones'] ?? [],
            'email' => $parsed['email'] ?? 'not found',
            'address' => $parsed['address'] ?? 'not found',
            'city' => $parsed['city'] ?? 'not found',
            'province' => $parsed['province'] ?? 'not found',
            'organization' => $parsed['organization'] ?? 'not found',
            'detection_source' => 'ai'
        ];
    }

    private function regexDetection(string $html, string $domain): array
    {
        $text = $this->cleanHTML($html);

        $phones = [];
        if (preg_match_all('/(?:\+?62|0)[- ]?\d{2,4}[- ]?\d{3,4}[- ]?\d{3,4}/', $text, $matches)) {
            $phones = array_unique($matches[0]);
            $phones = array_slice($phones, 0, 3);
        }

        $email = 'not found';
        if (preg_match('/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/', $text, $match)) {
            if (!preg_match('/@(gmail|yahoo|hotmail|outlook)\./', $match[0])) {
                $email = $match[0];
            }
        }

        $address = 'not found';
        if (preg_match('/Jl\.\s+[^,]+(?:,\s*[^,]+){1,3}/', $text, $match)) {
            $address = trim($match[0]);
            if (mb_strlen($address) > 150) {
                $address = mb_substr($address, 0, 150) . '...';
            }
        }

        $city = 'not found';
        if (preg_match('/Kota\s+([A-Z][a-z]+(?:\s+[A-Z][a-z]+)?)/', $text, $match)) {
            $city = 'Kota ' . $match[1];
        } elseif (preg_match('/Kab(?:upaten)?\.\s+([A-Z][a-z]+(?:\s+[A-Z][a-z]+)?)/', $text, $match)) {
            $city = 'Kab. ' . $match[1];
        }

        $province = 'not found';
        $provinces = [
            'Aceh', 'Sumatra Utara', 'Sumatra Barat', 'Riau', 'Jambi',
            'Sumatra Selatan', 'Bengkulu', 'Lampung', 'Kepulauan Bangka Belitung',
            'Kepulauan Riau', 'DKI Jakarta', 'Jawa Barat', 'Jawa Tengah',
            'DI Yogyakarta', 'Jawa Timur', 'Banten', 'Bali', 'Nusa Tenggara Barat',
            'Nusa Tenggara Timur', 'Kalimantan Barat', 'Kalimantan Tengah',
            'Kalimantan Selatan', 'Kalimantan Timur', 'Kalimantan Utara',
            'Sulawesi Utara', 'Sulawesi Tengah', 'Sulawesi Selatan', 'Sulawesi Tenggara',
            'Gorontalo', 'Sulawesi Barat', 'Maluku', 'Maluku Utara', 'Papua',
            'Papua Barat', 'Papua Tengah', 'Papua Pegunungan', 'Papua Selatan', 'Papua Barat Daya'
        ];

        foreach ($provinces as $prov) {
            if (stripos($text, $prov) !== false) {
                $province = $prov;
                break;
            }
        }

        return [
            'phones' => $phones,
            'email' => $email,
            'address' => $address,
            'city' => $city,
            'province' => $province,
            'organization' => 'not found',
            'detection_source' => 'regex'
        ];
    }

    public function detectFromMultipleDomains(array $domains, callable $progressCallback = null): array
    {
        $results = [];
        $total = count($domains);

        foreach ($domains as $index => $domain) {
            if ($progressCallback) {
                $progressCallback($index + 1, $total, $domain);
            }

            $html = $this->fetchHTML($domain);
            if ($html === false) {
                $results[$domain] = [
                    'error' => 'Failed to fetch content',
                    'detection_source' => 'error'
                ];
                continue;
            }

            $results[$domain] = $this->detectContent($html, $domain);
            usleep(500000);
        }

        return $results;
    }

    private function fetchHTML(string $domain): string|false
    {
        if (!preg_match('/^https?:\/\//', $domain)) {
            $domain = 'https://' . $domain;
        }

        $ch = curl_init($domain);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        ]);

        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 400 && $html !== false) {
            return $html;
        }

        return false;
    }
}
