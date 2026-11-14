<?php

namespace Coding1688\Fields\Validators;

use Coding1688\Fields\Contracts\FieldValueValidator;

final class NumberValidator implements FieldValueValidator
{
    public function validate(array $value, array $options = [], array $validation = []): bool
    {
        $number = $value['value_number'] ?? null;
        if (! is_numeric($number)) {
            return false;
        }
        $number = (float) $number;
        if (! is_finite($number)) {
            return false;
        }
        $precision = $validation['precision'] ?? 6;
        if (($validation['min_value'] ?? null) !== null && $number < $validation['min_value']) {
            return false;
        }
        if (($validation['max_value'] ?? null) !== null && $number > $validation['max_value']) {
            return false;
        }

        return round($number, $precision) === $number;
    }

    public function filter(array $value, array $options = [], array $validation = []): array
    {
        return ['value_number' => round((float) $value['value_number'], $validation['precision'] ?? 6)];
    }

    public function scalar(array $value): string|int|float|null
    {
        return $value['value_number'] ?? null;
    }
}
