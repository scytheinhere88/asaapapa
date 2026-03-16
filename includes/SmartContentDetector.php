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

        if (mb_strlen($cleanText) > 12000) {
            $cleanText = mb_substr($cleanText, 0, 12000) . '...';
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
        $html = preg_replace('/<noscript\b[^<]*(?:(?!<\/noscript>)<[^<]*)*<\/noscript>/si', '', $html);

        $importantSections = [];
        if (preg_match('/<header[^>]*>.*?<\/header>/si', $html, $match)) {
            $importantSections[] = "=== HEADER ===" . $match[0];
        }
        if (preg_match('/<footer[^>]*>.*?<\/footer>/si', $html, $match)) {
            $importantSections[] = "=== FOOTER ===" . $match[0];
        }
        if (preg_match('/<div[^>]*(?:class|id)="[^"]*contact[^"]*"[^>]*>.*?<\/div>/si', $html, $match)) {
            $importantSections[] = "=== CONTACT SECTION ===" . $match[0];
        }
        if (preg_match('/<div[^>]*(?:class|id)="[^"]*about[^"]*"[^>]*>.*?<\/div>/si', $html, $match)) {
            $importantSections[] = "=== ABOUT SECTION ===" . $match[0];
        }

        $combinedHTML = implode("\n\n", $importantSections) . "\n\n" . $html;

        $text = strip_tags($combinedHTML);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        return $text;
    }

    private function buildDetectionPrompt(string $domain): string
    {
        return <<<PROMPT
You are an expert data extraction specialist for Indonesian websites. Your task is to extract accurate contact information with maximum precision.

WEBSITE: {$domain}

=== EXTRACTION GUIDELINES ===

1. PHONE NUMBERS (Extract ALL variants):
   Priority order:
   a) Office landline (typically starts with area code: 021, 031, 022, etc.)
   b) Mobile/WhatsApp (0811, 0812, 0813, 0821, 0822, etc.)
   c) Toll-free numbers (0800, 1500)

   Format rules:
   - Keep original formatting (dashes, spaces, parentheses)
   - Include +62 country code if present
   - Extract from: "Telp:", "Phone:", "Hubungi:", "Kontak:", "WA:", "Call:", phone icons
   - Look in: header, footer, contact page, about page, sidebar
   - Return up to 5 most relevant numbers

2. EMAIL ADDRESSES (Official domain emails ONLY):
   Priority:
   a) Official domain email (same domain as website)
   b) Organization emails (info@, contact@, admin@, humas@, sekretariat@)
   c) Department emails (cs@, support@, marketing@)

   IGNORE:
   - Personal emails (gmail, yahoo, hotmail, outlook)
   - Unless NO official email exists, then include 1 professional personal email

   Extract from: "Email:", "E-mail:", "Surel:", mailto links, contact forms

3. PHYSICAL ADDRESS:
   Extract complete address with:
   - Street name (Jl./Jalan)
   - Building/House number (No.)
   - RT/RW if present
   - Kelurahan/Desa
   - Kecamatan
   - City/Regency (Kota/Kabupaten)
   - Province
   - Postal code (5 digits)

   Look in: "Alamat:", "Address:", "Lokasi:", footer, contact page
   Prefer: Most complete and official address (usually in contact/about section)

4. CITY/REGENCY:
   Format: "Kota [Name]" or "Kabupaten [Name]"
   Examples: "Kota Surabaya", "Kabupaten Sidoarjo", "Kota Jakarta Selatan"
   Extract from: Address, location mentions, regional context

5. PROVINCE:
   Must be one of 38 Indonesian provinces:
   - Java: DKI Jakarta, Jawa Barat, Jawa Tengah, DI Yogyakarta, Jawa Timur, Banten
   - Sumatra: Aceh, Sumatra Utara, Sumatra Barat, Riau, Jambi, Sumatra Selatan, Bengkulu, Lampung, Kepulauan Bangka Belitung, Kepulauan Riau
   - Kalimantan: Kalimantan Barat, Kalimantan Tengah, Kalimantan Selatan, Kalimantan Timur, Kalimantan Utara
   - Sulawesi: Sulawesi Utara, Sulawesi Tengah, Sulawesi Selatan, Sulawesi Tenggara, Gorontalo, Sulawesi Barat
   - Others: Bali, Nusa Tenggara Barat, Nusa Tenggara Timur, Maluku, Maluku Utara, Papua, Papua Barat, Papua Tengah, Papua Pegunungan, Papua Selatan, Papua Barat Daya

6. ORGANIZATION NAME:
   Priority:
   a) Official registered name (from header, title, about section)
   b) Full name with legal entity type (PT, CV, Yayasan, Koperasi, Asosiasi, Lembaga, Dinas, Badan, etc.)
   c) Avoid: Abbreviations, taglines, slogans

   Extract from: Page title, header logo text, about page, footer
   Example: "PT Maju Bersama Indonesia" NOT "MBI" or "Maju Bersama - Your Partner"

7. WEBSITE URL:
   Clean main domain URL
   Format: https://domain.com (no trailing slash, no paths)

8. SOCIAL MEDIA (If found):
   Extract URLs for: Facebook, Instagram, Twitter/X, LinkedIn, YouTube, TikTok
   Look for social media icons/links in header/footer

=== CRITICAL EXTRACTION RULES ===

✓ DO:
- Extract ONLY information that is EXPLICITLY stated in the content
- Prioritize official contact information over general mentions
- Look in multiple sections: header, footer, contact page, about page, sidebar
- Keep original Indonesian spelling and formatting
- Be thorough - check entire content before concluding "not found"
- For ambiguous data, choose the most official/complete version
- Cross-reference data for consistency (e.g., city in address should match city field)

✗ DO NOT:
- Hallucinate or infer information not present
- Extract competitor/partner contact information
- Include incomplete data (e.g., half addresses)
- Return personal social media accounts
- Translate or modify Indonesian terms
- Include data from advertisements or embedded content

=== QUALITY CHECKS ===

Before finalizing:
1. Phone numbers: Valid Indonesian format? (starts with +62/0, correct length)
2. Email: Valid format? Official domain preferred?
3. Address: Complete with street, city, province?
4. City: Properly formatted with Kota/Kabupaten prefix?
5. Province: Exactly matches one of 38 provinces?
6. Organization: Full official name without abbreviations?

=== OUTPUT FORMAT ===

Return ONLY valid JSON with this exact structure:

{
  "organization": "PT Telekomunikasi Indonesia Tbk",
  "phones": ["+62-21-5555-0100", "0811-2345-6789", "1500-123"],
  "email": "info@domain.co.id",
  "address": "Jl. Gatot Subroto Kav. 52, RT.6/RW.1, Kuningan Barat, Kec. Mampang Prapatan, Kota Jakarta Selatan, DKI Jakarta 12710",
  "city": "Kota Jakarta Selatan",
  "province": "DKI Jakarta",
  "website": "https://domain.co.id",
  "social_media": {
    "facebook": "https://facebook.com/officialpage",
    "instagram": "https://instagram.com/officialaccount",
    "twitter": "https://twitter.com/officialhandle",
    "linkedin": "https://linkedin.com/company/officialcompany",
    "youtube": "https://youtube.com/@officialchannel"
  },
  "confidence_score": 95,
  "extraction_notes": "All data extracted from contact page and footer"
}

For missing fields, use: "not found"
For empty arrays, use: []
For missing social media, use: null or omit the platform

RESPOND WITH ONLY THE JSON OBJECT. NO MARKDOWN FORMATTING. NO EXPLANATIONS. NO CODE BLOCKS.
PROMPT;
    }

    private function callAI(string $prompt, string $content): array|false
    {
        $payload = json_encode([
            'model' => 'gpt-4o-mini',
            'temperature' => 0.1,
            'max_tokens' => 1500,
            'response_format' => ['type' => 'json_object'],
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
            'website' => $parsed['website'] ?? 'not found',
            'social_media' => $parsed['social_media'] ?? null,
            'confidence_score' => $parsed['confidence_score'] ?? 0,
            'extraction_notes' => $parsed['extraction_notes'] ?? '',
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
