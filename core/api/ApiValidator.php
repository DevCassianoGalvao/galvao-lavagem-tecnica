<?php

final class ApiValidator
{
    public static function validate(array $data, array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $ruleSet) {
            $value = $data[$field] ?? null;
            $ruleSet = is_array($ruleSet) ? $ruleSet : explode('|', (string) $ruleSet);

            foreach ($ruleSet as $rule) {
                if ($rule === 'required' && self::blank($value)) {
                    $errors[$field][] = 'Campo obrigatorio.';
                }

                if ($rule === 'email' && !self::blank($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field][] = 'E-mail invalido.';
                }

                if (str_starts_with($rule, 'in:') && !self::blank($value)) {
                    $allowed = explode(',', substr($rule, 3));
                    if (!in_array((string) $value, $allowed, true)) {
                        $errors[$field][] = 'Valor nao permitido.';
                    }
                }

                if (str_starts_with($rule, 'max:') && is_string($value) && mb_strlen($value) > (int) substr($rule, 4)) {
                    $errors[$field][] = 'Tamanho maximo excedido.';
                }
            }
        }

        return $errors;
    }

    public static function assert(array $data, array $rules): void
    {
        $errors = self::validate($data, $rules);

        if ($errors) {
            ApiResponse::validation($errors);
        }
    }

    private static function blank(mixed $value): bool
    {
        return $value === null || (is_string($value) && trim($value) === '') || $value === [];
    }
}
