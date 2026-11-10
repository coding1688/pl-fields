<?php

namespace Coding1688\Fields\Validators;

use Coding1688\Fields\Contracts\FieldValueValidator;

final class BooleanValidator implements FieldValueValidator
{
    public function validate(array $value, array $options = [], array $validation = []): bool
    {
        return array_key_exists('value_boolean', $value) && is_bool($value['value_boolean']);
    }

    public function filter(array $value, array $options = [], array $validation = []): array
    {
        return ['value_boolean' => (bool) $value['value_boolean']];
    }

    public function scalar(array $value): ?bool
    {
        return $value['value_boolean'] ?? null;
    }
}
