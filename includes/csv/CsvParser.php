<?php

class CsvParser
{
    private $delimiter = ',';
    private $enclosure = '"';
    private $escape = '\\';
    private $maxFileSize = 10485760;

    public function setDelimiter($delimiter)
    {
        $this->delimiter = $delimiter;
        return $this;
    }

    public function setEnclosure($enclosure)
    {
        $this->enclosure = $enclosure;
        return $this;
    }

    public function setMaxFileSize($bytes)
    {
        $this->maxFileSize = $bytes;
        return $this;
    }

    public function parseFile($filePath)
    {
        if (!file_exists($filePath)) {
            return ['success' => false, 'error' => 'File not found'];
        }

        if (filesize($filePath) > $this->maxFileSize) {
            $maxMb = round($this->maxFileSize / 1024 / 1024, 1);
            return ['success' => false, 'error' => "File too large (max {$maxMb}MB)"];
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return ['success' => false, 'error' => 'Cannot open file'];
        }

        $headers = fgetcsv($handle, 0, $this->delimiter, $this->enclosure, $this->escape);
        if (!$headers) {
            fclose($handle);
            return ['success' => false, 'error' => 'Invalid CSV format - no headers found'];
        }

        $rows = [];
        $lineNumber = 1;

        while (($data = fgetcsv($handle, 0, $this->delimiter, $this->enclosure, $this->escape)) !== false) {
            $lineNumber++;

            if (count($data) !== count($headers)) {
                continue;
            }

            $rows[] = array_combine($headers, $data);
        }

        fclose($handle);

        return [
            'success' => true,
            'headers' => $headers,
            'rows' => $rows,
            'count' => count($rows)
        ];
    }

    public function parseString($csvString)
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'csv_');
        file_put_contents($tempFile, $csvString);

        $result = $this->parseFile($tempFile);
        unlink($tempFile);

        return $result;
    }

    public function detectDelimiter($filePath)
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return ',';
        }

        $firstLine = fgets($handle);
        fclose($handle);

        $delimiters = [',', ';', "\t", '|'];
        $counts = [];

        foreach ($delimiters as $delimiter) {
            $counts[$delimiter] = substr_count($firstLine, $delimiter);
        }

        arsort($counts);
        return array_key_first($counts);
    }

    public function validateHeaders($headers, $requiredColumns = [])
    {
        $missing = [];

        foreach ($requiredColumns as $required) {
            if (!in_array($required, $headers)) {
                $missing[] = $required;
            }
        }

        if (!empty($missing)) {
            return [
                'valid' => false,
                'error' => 'Missing required columns: ' . implode(', ', $missing)
            ];
        }

        return ['valid' => true];
    }
}
