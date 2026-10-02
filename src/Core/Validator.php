<?php
/**
 * Input Validator
 */

namespace App\Core;

class Validator
{
    private array $errors = [];
    private array $data = [];
    private array $rules = [];

    /**
     * Validate data against rules
     */
    public function validate(array $data, array $rules): array
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->errors = [];
        $validated = [];

        foreach ($rules as $field => $fieldRules) {
            $fieldRules = is_array($fieldRules) ? $fieldRules : explode('|', $fieldRules);
            $value = $data[$field] ?? null;

            foreach ($fieldRules as $rule) {
                $params = [];

                // Parse rule with parameters: min:3
                if (str_contains($rule, ':')) {
                    [$rule, $paramStr] = explode(':', $rule, 2);
                    $params = explode(',', $paramStr);
                }

                $methodName = 'rule' . ucfirst($rule);
                if (method_exists($this, $methodName)) {
                    $this->$methodName($field, $value, $params);
                }
            }

            // Add to validated data if no errors for this field
            if (!isset($this->errors[$field])) {
                $validated[$field] = $value;
            }
        }

        return $validated;
    }

    /**
     * Check if validation has errors
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Get all errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get first error for a field
     */
    public function firstError(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }

    /**
     * Add a custom error
     */
    public function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    // ─── Validation Rules ───

    private function ruleRequired(string $field, mixed $value, array $params): void
    {
        if ($value === null || $value === '' || (is_array($value) && empty($value))) {
            $this->addError($field, "The {$field} field is required.");
        }
    }

    private function ruleEmail(string $field, mixed $value, array $params): void
    {
        if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, "The {$field} must be a valid email address.");
        }
    }

    private function ruleMin(string $field, mixed $value, array $params): void
    {
        $min = (int) ($params[0] ?? 0);
        if (is_string($value) && strlen($value) < $min) {
            $this->addError($field, "The {$field} must be at least {$min} characters.");
        }
        if (is_numeric($value) && $value < $min) {
            $this->addError($field, "The {$field} must be at least {$min}.");
        }
    }

    private function ruleMax(string $field, mixed $value, array $params): void
    {
        $max = (int) ($params[0] ?? 0);
        if (is_string($value) && strlen($value) > $max) {
            $this->addError($field, "The {$field} must not exceed {$max} characters.");
        }
        if (is_numeric($value) && $value > $max) {
            $this->addError($field, "The {$field} must not exceed {$max}.");
        }
    }

    private function ruleNumeric(string $field, mixed $value, array $params): void
    {
        if ($value !== null && $value !== '' && !is_numeric($value)) {
            $this->addError($field, "The {$field} must be a number.");
        }
    }

    private function ruleInteger(string $field, mixed $value, array $params): void
    {
        if ($value !== null && $value !== '' && filter_var($value, FILTER_VALIDATE_INT) === false) {
            $this->addError($field, "The {$field} must be an integer.");
        }
    }

    private function ruleAlpha(string $field, mixed $value, array $params): void
    {
        if ($value && !ctype_alpha(str_replace(' ', '', $value))) {
            $this->addError($field, "The {$field} may only contain letters.");
        }
    }

    private function ruleAlphanumeric(string $field, mixed $value, array $params): void
    {
        if ($value && !ctype_alnum(str_replace(' ', '', $value))) {
            $this->addError($field, "The {$field} may only contain letters and numbers.");
        }
    }

    private function ruleUrl(string $field, mixed $value, array $params): void
    {
        if ($value && !filter_var($value, FILTER_VALIDATE_URL)) {
            $this->addError($field, "The {$field} must be a valid URL.");
        }
    }

    private function ruleIp(string $field, mixed $value, array $params): void
    {
        if ($value && !filter_var($value, FILTER_VALIDATE_IP)) {
            $this->addError($field, "The {$field} must be a valid IP address.");
        }
    }

    private function ruleMac(string $field, mixed $value, array $params): void
    {
        if ($value && !preg_match('/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/', $value)) {
            $this->addError($field, "The {$field} must be a valid MAC address.");
        }
    }

    private function ruleIn(string $field, mixed $value, array $params): void
    {
        if ($value !== null && $value !== '' && !in_array((string) $value, $params)) {
            $this->addError($field, "The {$field} must be one of: " . implode(', ', $params) . ".");
        }
    }

    private function ruleMatch(string $field, mixed $value, array $params): void
    {
        $otherField = $params[0] ?? '';
        $otherValue = $this->data[$otherField] ?? null;
        if ($value !== $otherValue) {
            $this->addError($field, "The {$field} does not match {$otherField}.");
        }
    }

    private function ruleUnique(string $field, mixed $value, array $params): void
    {
        if (!$value) return;
        
        $table = $params[0] ?? '';
        $column = $params[1] ?? $field;
        $exceptId = $params[2] ?? null;

        if ($table) {
            $db = Database::getInstance();
            $sql = "SELECT COUNT(*) as count FROM {$table} WHERE {$column} = ?";
            $bindParams = [$value];

            if ($exceptId) {
                $sql .= " AND id != ?";
                $bindParams[] = $exceptId;
            }

            $result = $db->fetch($sql, $bindParams);
            if ($result !== null && $result['count'] > 0) {
                $this->addError($field, "The {$field} already exists.");
            }
        }
    }

    private function ruleDate(string $field, mixed $value, array $params): void
    {
        if ($value && !strtotime($value)) {
            $this->addError($field, "The {$field} is not a valid date.");
        }
    }

    private function ruleBoolean(string $field, mixed $value, array $params): void
    {
        if ($value !== null && !in_array($value, [true, false, 0, 1, '0', '1'], true)) {
            $this->addError($field, "The {$field} must be a boolean.");
        }
    }
}
