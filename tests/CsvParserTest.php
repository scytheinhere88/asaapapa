<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/csv/CsvParser.php';

class CsvParserTest extends TestCase
{
    private $csvParser;
    private $tempDir;

    protected function setUp(): void
    {
        $this->csvParser = new CsvParser();
        $this->tempDir = sys_get_temp_dir() . '/csv_test_' . uniqid();
        mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        $files = glob($this->tempDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        rmdir($this->tempDir);
    }

    public function testParseValidCsvFile()
    {
        $csvContent = "name,email,age\nJohn,john@test.com,30\nJane,jane@test.com,25";
        $filePath = $this->tempDir . '/test.csv';
        file_put_contents($filePath, $csvContent);

        $result = $this->csvParser->parseFile($filePath);

        $this->assertTrue($result['success']);
        $this->assertEquals(['name', 'email', 'age'], $result['headers']);
        $this->assertEquals(2, $result['count']);
        $this->assertEquals('John', $result['rows'][0]['name']);
        $this->assertEquals('john@test.com', $result['rows'][0]['email']);
    }

    public function testParseFileNotFound()
    {
        $result = $this->csvParser->parseFile('/nonexistent/file.csv');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not found', $result['error']);
    }

    public function testParseString()
    {
        $csvString = "product,price\nLaptop,999\nMouse,25";

        $result = $this->csvParser->parseString($csvString);

        $this->assertTrue($result['success']);
        $this->assertEquals(['product', 'price'], $result['headers']);
        $this->assertEquals(2, $result['count']);
        $this->assertEquals('Laptop', $result['rows'][0]['product']);
        $this->assertEquals('999', $result['rows'][0]['price']);
    }

    public function testParseWithDifferentDelimiter()
    {
        $csvContent = "name;email;city\nAlice;alice@test.com;NYC\nBob;bob@test.com;LA";
        $filePath = $this->tempDir . '/semicolon.csv';
        file_put_contents($filePath, $csvContent);

        $this->csvParser->setDelimiter(';');
        $result = $this->csvParser->parseFile($filePath);

        $this->assertTrue($result['success']);
        $this->assertEquals(['name', 'email', 'city'], $result['headers']);
        $this->assertEquals('Alice', $result['rows'][0]['name']);
    }

    public function testDetectDelimiter()
    {
        $csvContent = "name;email;city\nAlice;alice@test.com;NYC";
        $filePath = $this->tempDir . '/detect.csv';
        file_put_contents($filePath, $csvContent);

        $delimiter = $this->csvParser->detectDelimiter($filePath);
        $this->assertEquals(';', $delimiter);
    }

    public function testValidateHeaders()
    {
        $headers = ['name', 'email', 'age'];
        $requiredColumns = ['name', 'email'];

        $result = $this->csvParser->validateHeaders($headers, $requiredColumns);
        $this->assertTrue($result['valid']);
    }

    public function testValidateHeadersMissingColumns()
    {
        $headers = ['name', 'age'];
        $requiredColumns = ['name', 'email', 'phone'];

        $result = $this->csvParser->validateHeaders($headers, $requiredColumns);
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('email', $result['error']);
        $this->assertStringContainsString('phone', $result['error']);
    }

    public function testParseWithQuotedFields()
    {
        $csvContent = "name,description\n\"John Doe\",\"A person who says, \\\"Hello\\\"\"\n\"Jane\",\"Normal text\"";
        $filePath = $this->tempDir . '/quoted.csv';
        file_put_contents($filePath, $csvContent);

        $result = $this->csvParser->parseFile($filePath);

        $this->assertTrue($result['success']);
        $this->assertEquals('John Doe', $result['rows'][0]['name']);
    }

    public function testParseEmptyFile()
    {
        $filePath = $this->tempDir . '/empty.csv';
        file_put_contents($filePath, '');

        $result = $this->csvParser->parseFile($filePath);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Invalid CSV', $result['error']);
    }

    public function testMaxFileSizeLimit()
    {
        $this->csvParser->setMaxFileSize(100);

        $largeContent = str_repeat("name,email,age\nJohn,john@test.com,30\n", 100);
        $filePath = $this->tempDir . '/large.csv';
        file_put_contents($filePath, $largeContent);

        $result = $this->csvParser->parseFile($filePath);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('too large', $result['error']);
    }
}
