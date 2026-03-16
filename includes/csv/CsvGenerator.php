<?php

class CsvGenerator
{
    private $delimiter = ',';
    private $enclosure = '"';
    private $lineEnding = "\n";
    private $includeHeaders = true;

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

    public function setLineEnding($lineEnding)
    {
        $this->lineEnding = $lineEnding;
        return $this;
    }

    public function setIncludeHeaders($include)
    {
        $this->includeHeaders = (bool)$include;
        return $this;
    }

    public function generate($data, $headers = null)
    {
        if (empty($data)) {
            return ['success' => false, 'error' => 'No data provided'];
        }

        if ($headers === null && isset($data[0])) {
            $headers = array_keys($data[0]);
        }

        $output = '';

        if ($this->includeHeaders && $headers) {
            $output .= $this->formatRow($headers) . $this->lineEnding;
        }

        foreach ($data as $row) {
            if (is_array($row)) {
                $values = [];
                foreach ($headers as $header) {
                    $values[] = $row[$header] ?? '';
                }
                $output .= $this->formatRow($values) . $this->lineEnding;
            }
        }

        return [
            'success' => true,
            'csv' => $output,
            'size' => strlen($output),
            'rows' => count($data)
        ];
    }

    public function generateFile($data, $filePath, $headers = null)
    {
        $result = $this->generate($data, $headers);

        if (!$result['success']) {
            return $result;
        }

        $written = file_put_contents($filePath, $result['csv']);

        if ($written === false) {
            return ['success' => false, 'error' => 'Failed to write file'];
        }

        return [
            'success' => true,
            'file' => $filePath,
            'size' => $written,
            'rows' => $result['rows']
        ];
    }

    public function downloadCsv($data, $filename, $headers = null)
    {
        $result = $this->generate($data, $headers);

        if (!$result['success']) {
            return $result;
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . $result['size']);
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');

        echo $result['csv'];
        exit;
    }

    public function arrayToCsv($array)
    {
        return $this->generate($array);
    }

    public function objectsToCsv($objects)
    {
        $array = [];
        foreach ($objects as $object) {
            if (is_object($object)) {
                $array[] = (array)$object;
            } else {
                $array[] = $object;
            }
        }

        return $this->generate($array);
    }

    private function formatRow($fields)
    {
        $formatted = [];

        foreach ($fields as $field) {
            $field = (string)$field;

            if (
                strpos($field, $this->delimiter) !== false ||
                strpos($field, $this->enclosure) !== false ||
                strpos($field, "\n") !== false ||
                strpos($field, "\r") !== false
            ) {
                $field = str_replace($this->enclosure, $this->enclosure . $this->enclosure, $field);
                $field = $this->enclosure . $field . $this->enclosure;
            }

            $formatted[] = $field;
        }

        return implode($this->delimiter, $formatted);
    }

    public function mergeCsvFiles($files, $outputFile)
    {
        if (empty($files)) {
            return ['success' => false, 'error' => 'No files provided'];
        }

        $allData = [];
        $headers = null;

        foreach ($files as $file) {
            if (!file_exists($file)) {
                continue;
            }

            $parser = new CsvParser();
            $result = $parser->parseFile($file);

            if ($result['success']) {
                if ($headers === null) {
                    $headers = $result['headers'];
                }
                $allData = array_merge($allData, $result['rows']);
            }
        }

        if (empty($allData)) {
            return ['success' => false, 'error' => 'No valid data found in files'];
        }

        return $this->generateFile($allData, $outputFile, $headers);
    }
}
