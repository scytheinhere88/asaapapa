<?php

use PHPUnit\Framework\TestCase;

class AutopilotParsingTest extends TestCase
{
    private $pdo;
    private $aiParser;

    protected function setUp(): void
    {
        require_once __DIR__ . '/../config.php';
        require_once __DIR__ . '/../api/ai_parser.php';

        $this->pdo = db();
        $this->aiParser = new AIDomainParser();
    }

    public function testKotaPasuruanParsing()
    {
        $domain = 'aptisikotapasuruan.org';
        $keywordHint = 'APTISI';

        $result = $this->aiParser->parseOne($domain, $keywordHint);

        $this->assertEquals('Kota Pasuruan', $result['location_display'],
            'Should parse "aptisikotapasuruan" as "Kota Pasuruan", not "Aptisikarang asem"');

        $this->assertEquals('APTISI', $result['institution']);
        $this->assertEquals('kota', $result['location_level']);
    }

    public function testKarangpilangParsing()
    {
        $domain = 'ksbsikarangpilang.org';
        $keywordHint = 'KSBSI';

        $result = $this->aiParser->parseOne($domain, $keywordHint);

        $this->assertEquals('Karangpilang', $result['location_display'],
            'Should preserve exact spelling: Karangpilang (not "Karang Pilang")');

        $this->assertEquals('KSBSI', $result['institution']);
    }

    public function testKabupatenParsing()
    {
        $domain = 'aptisikabtulangbawang.org';
        $keywordHint = 'APTISI';

        $result = $this->aiParser->parseOne($domain, $keywordHint);

        $this->assertStringContainsString('Kab.', $result['location_display'],
            'Should prefix kabupaten with "Kab."');

        $this->assertEquals('kabupaten', $result['location_level']);
    }

    public function testBundaParsing()
    {
        $domain = 'aptisibunda.org';
        $keywordHint = 'APTISI';

        $result = $this->aiParser->parseOne($domain, $keywordHint);

        $this->assertEquals('Bunda', $result['location_display']);
        $this->assertEquals('APTISI', $result['institution']);
    }

    public function testMultipleDomainsBatch()
    {
        $domains = [
            'aptisikotapasuruan.org',
            'ksbsikarangpilang.org',
            'aptisikabtulangbawang.org',
            'aptisibunda.org'
        ];
        $keywordHint = 'APTISI';

        $results = $this->aiParser->parseBatch($domains, $keywordHint);

        $this->assertCount(4, $results, 'Should parse all 4 domains');

        $this->assertEquals('Kota Pasuruan',
            $results['aptisikotapasuruan.org']['location_display']);

        $this->assertEquals('Karangpilang',
            $results['ksbsikarangpilang.org']['location_display']);
    }

    public function testParseSourceTracking()
    {
        $domain = 'aptisikotapasuruan.org';
        $keywordHint = 'APTISI';

        $result = $this->aiParser->parseOne($domain, $keywordHint);

        $this->assertContains($result['parse_source'], ['ai', 'regex'],
            'Should track whether parsed by AI or regex fallback');
    }

    public function testEmailSlugGeneration()
    {
        $domain = 'aptisikotapasuruan.org';
        $keywordHint = 'APTISI';

        $result = $this->aiParser->parseOne($domain, $keywordHint);

        $this->assertNotEmpty($result['email_slug']);
        $this->assertMatchesRegularExpression('/^[a-z0-9]+$/', $result['email_slug'],
            'Email slug should be lowercase alphanumeric only');
    }

    public function testSearchQueryGeneration()
    {
        $domain = 'aptisikotapasuruan.org';
        $keywordHint = 'APTISI';

        $result = $this->aiParser->parseOne($domain, $keywordHint);

        $this->assertNotEmpty($result['search_query']);
        $this->assertStringContainsString('Pasuruan', $result['search_query'],
            'Search query should contain location name');
    }

    public function testRegexFallbackWhenNoAI()
    {
        $parser = new AIDomainParser();

        if (!$parser->isAvailable()) {
            $domain = 'aptisikotapasuruan.org';
            $keywordHint = 'APTISI';

            $result = $parser->parseOne($domain, $keywordHint);

            $this->assertEquals('regex', $result['parse_source'],
                'Should fall back to regex when AI not available');

            $this->assertNotEmpty($result['location_display']);
        } else {
            $this->markTestSkipped('AI is available, skipping regex fallback test');
        }
    }
}
