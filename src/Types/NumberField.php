<?php

namespace Coding1688\Fields\Types;

use Coding1688\Fields\Contracts\FieldType;
use Coding1688\Fields\Contracts\FieldValueValidator;
use Coding1688\Fields\Validators\NumberValidator;

final class NumberField implements FieldType
{
    public function key(): string
    {
        return 'number';
    }

    public function component(): string
    {
        return 'input-number';
    }

    public function supportsMultiple(): bool
    {
        return false;
    }

    public function defaultMultiple(): bool
    {
        return false;
    }

    public function usesOptions(bool $multiple): bool
    {
        return false;
    }

    public function dataType(bool $multiple): string
    {
        return 'number';
    }

    public function validationSchema(bool $multiple): array
    {
        return [
            ['key' => 'min_value', 'type' => 'decimal', 'default' => null, 'nullable' => true, 'min' => -99999999999999.999999, 'max' => 99999999999999.999999],
            ['key' => 'max_value', 'type' => 'decimal', 'default' => null, 'nullable' => true, 'min' => -99999999999999.999999, 'max' => 99999999999999.999999],
            ['key' => 'precision', 'type' => 'integer', 'default' => 6, 'min' => 0, 'max' => 6],
        ];
    }

    public function validator(bool $multiple): FieldValueValidator
    {
        return new NumberValidator;
    }
}
