<?php
declare(strict_types=1);

namespace App\Core;

class Validator
{
    private array $data;
    private array $rules;
    private array $errors = [];
    private array $messages = [
        'required' => 'The :attribute field is required.',
        'email'    => 'The :attribute must be a valid email.',
        'unique'   => 'The :attribute has already been taken.',
        'min'      => 'The :attribute must be at least :param characters.',
        'max'      => 'The :attribute must not exceed :param characters.',
        'numeric'  => 'The :attribute must be a number.',
        'in'       => 'The :attribute must be one of: :param.',
        'date'     => 'The :attribute must be a valid date.',
        'confirmed' => 'The :attribute confirmation does not match.',
        'regex'    => 'The :attribute format is invalid.',
    ];

    public function __construct(array $data, array $rules)
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->validate();
    }

    private function validate(): void
    {
        foreach ($this->rules as $field => $ruleSet) {
            $rules = is_string($ruleSet) ? explode('|', $ruleSet) : $ruleSet;
            $value = $this->data[$field] ?? null;

            foreach ($rules as $rule) {
                $params = [];
                if (str_contains($rule, ':')) {
                    [$rule, $paramStr] = explode(':', $rule, 2);
                    $params = explode(',', $paramStr);
                }

                $this->validateRule($field, $value, $rule, $params);
                if (isset($this->errors[$field])) break;
            }
        }
    }

    private function validateRule(string $field, mixed $value, string $rule, array $params): void
    {
        $label = ucfirst(str_replace('_', ' ', $field));

        match ($rule) {
            'required' => $this->validateRequired($field, $value, $label),
            'email'    => $this->validateEmail($field, $value, $label),
            'min'      => $this->validateMin($field, $value, $label, (int)($params[0] ?? 0)),
            'max'      => $this->validateMax($field, $value, $label, (int)($params[0] ?? 255)),
            'numeric'  => $this->validateNumeric($field, $value, $label),
            'in'       => $this->validateIn($field, $value, $label, $params),
            'date'     => $this->validateDate($field, $value, $label),
            'regex'    => $this->validateRegex($field, $value, $label, $params[0] ?? ''),
            'confirmed' => $this->validateConfirmed($field, $value, $label),
            default => null,
        };
    }

    private function validateRequired(string $field, mixed $value, string $label): void
    {
        if ($value === null || $value === '' || $value === []) {
            $this->errors[$field] = str_replace(':attribute', $label, $this->messages['required']);
        }
    }

    private function validateEmail(string $field, mixed $value, string $label): void
    {
        if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = str_replace(':attribute', $label, $this->messages['email']);
        }
    }

    private function validateMin(string $field, mixed $value, string $label, int $min): void
    {
        if ($value && strlen((string)$value) < $min) {
            $msg = str_replace(':attribute', $label, $this->messages['min']);
            $this->errors[$field] = str_replace(':param', (string)$min, $msg);
        }
    }

    private function validateMax(string $field, mixed $value, string $label, int $max): void
    {
        if ($value && strlen((string)$value) > $max) {
            $msg = str_replace(':attribute', $label, $this->messages['max']);
            $this->errors[$field] = str_replace(':param', (string)$max, $msg);
        }
    }

    private function validateNumeric(string $field, mixed $value, string $label): void
    {
        if ($value !== null && $value !== '' && !is_numeric($value)) {
            $this->errors[$field] = str_replace(':attribute', $label, $this->messages['numeric']);
        }
    }

    private function validateIn(string $field, mixed $value, string $label, array $params): void
    {
        if ($value && !in_array($value, $params)) {
            $msg = str_replace(':attribute', $label, $this->messages['in']);
            $this->errors[$field] = str_replace(':param', implode(', ', $params), $msg);
        }
    }

    private function validateDate(string $field, mixed $value, string $label): void
    {
        if ($value && !strtotime((string)$value)) {
            $this->errors[$field] = str_replace(':attribute', $label, $this->messages['date']);
        }
    }

    private function validateRegex(string $field, mixed $value, string $label, string $pattern): void
    {
        if ($value && !preg_match($pattern, (string)$value)) {
            $this->errors[$field] = str_replace(':attribute', $label, $this->messages['regex']);
        }
    }

    private function validateConfirmed(string $field, mixed $value, string $label): void
    {
        $confirmField = $field . '_confirmation';
        if (($this->data[$confirmField] ?? null) !== $value) {
            $this->errors[$field] = str_replace(':attribute', $label, $this->messages['confirmed']);
        }
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function validated(): array
    {
        $validated = [];
        foreach ($this->rules as $field => $ruleSet) {
            if (isset($this->data[$field])) {
                $validated[$field] = $this->data[$field];
            }
        }
        return $validated;
    }
}
