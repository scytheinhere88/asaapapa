<?php

class CsvValidator
{
    private $errors = [];
    private $warnings = [];

    public function validateRow($row, $rules)
    {
        $this->errors = [];
        $this->warnings = [];

        foreach ($rules as $field => $fieldRules) {
            $value = $row[$field] ?? null;

            foreach ($fieldRules as $rule => $ruleValue) {
                $this->applyRule($field, $value, $rule, $ruleValue);
            }
        }

        return [
            'valid' => empty($this->errors),
            'errors' => $this->errors,
            'warnings' => $this->warnings
        ];
    }

    public function validateData($data, $rules)
    {
        $results = [
            'valid' => true,
            'total_rows' => count($data),
            'valid_rows' => 0,
            'invalid_rows' => 0,
            'row_errors' => []
        ];

        foreach ($data as $index => $row) {
            $validation = $this->validateRow($row, $rules);

            if ($validation['valid']) {
                $results['valid_rows']++;
            } else {
                $results['valid'] = false;
                $results['invalid_rows']++;
                $results['row_errors'][$index] = $validation['errors'];
            }
        }

        return $results;
    }

    private function applyRule($field, $value, $rule, $ruleValue)
    {
        switch ($rule) {
            case 'required':
                if ($ruleValue && ($value === null || $value === '')) {
                    $this->errors[] = "Field '{$field}' is required";
                }
                break;

            case 'email':
                if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->errors[] = "Field '{$field}' must be a valid email";
                }
                break;

            case 'url':
                if ($value && !filter_var($value, FILTER_VALIDATE_URL)) {
                    $this->errors[] = "Field '{$field}' must be a valid URL";
                }
                break;

            case 'numeric':
                if ($value !== '' && !is_numeric($value)) {
                    $this->errors[] = "Field '{$field}' must be numeric";
                }
                break;

            case 'integer':
                if ($value !== '' && !ctype_digit((string)$value)) {
                    $this->errors[] = "Field '{$field}' must be an integer";
                }
                break;

            case 'min_length':
                if ($value && strlen($value) < $ruleValue) {
                    $this->errors[] = "Field '{$field}' must be at least {$ruleValue} characters";
                }
                break;

            case 'max_length':
                if ($value && strlen($value) > $ruleValue) {
                    $this->errors[] = "Field '{$field}' must not exceed {$ruleValue} characters";
                }
                break;

            case 'min':
                if ($value !== '' && is_numeric($value) && $value < $ruleValue) {
                    $this->errors[] = "Field '{$field}' must be at least {$ruleValue}";
                }
                break;

            case 'max':
                if ($value !== '' && is_numeric($value) && $value > $ruleValue) {
                    $this->errors[] = "Field '{$field}' must not exceed {$ruleValue}";
                }
                break;

            case 'in':
                if ($value && !in_array($value, $ruleValue)) {
                    $allowed = implode(', ', $ruleValue);
                    $this->errors[] = "Field '{$field}' must be one of: {$allowed}";
                }
                break;

            case 'regex':
                if ($value && !preg_match($ruleValue, $value)) {
                    $this->errors[] = "Field '{$field}' has invalid format";
                }
                break;

            case 'date':
                if ($value && !strtotime($value)) {
                    $this->errors[] = "Field '{$field}' must be a valid date";
                }
                break;

            case 'unique':
                break;

            default:
                break;
        }
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function getWarnings()
    {
        return $this->warnings;
    }

    public function sanitizeValue($value, $type = 'string')
    {
        switch ($type) {
            case 'email':
                return filter_var($value, FILTER_SANITIZE_EMAIL);

            case 'url':
                return filter_var($value, FILTER_SANITIZE_URL);

            case 'int':
            case 'integer':
                return filter_var($value, FILTER_SANITIZE_NUMBER_INT);

            case 'float':
                return filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

            case 'html':
                return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

            case 'string':
            default:
                return trim($value);
        }
    }

    public function sanitizeRow($row, $schema)
    {
        $sanitized = [];

        foreach ($row as $key => $value) {
            $type = $schema[$key] ?? 'string';
            $sanitized[$key] = $this->sanitizeValue($value, $type);
        }

        return $sanitized;
    }
}
