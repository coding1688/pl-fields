<?php

namespace Coding1688\Fields\Types;

use Coding1688\Fields\Contracts\FieldType;
use Coding1688\Fields\Contracts\FieldValueValidator;
use Coding1688\Fields\Validators\OptionValidator;

final class SelectField implements FieldType
{
    public function key(): string
    {
        return 'select';
    }

    public function component(): string
    {
        return 'select';
    }

    public function supportsMultiple(): bool
    {
        return true;
    }

    public function defaultMultiple(): bool
    {
        return false;
    }

    public function usesOptions(bool $multiple): bool
    {
        return true;
    }

    public function dataType(bool $multiple): string
    {
        return $multiple ? 'multi_select' : 'select';
    }

    public function validationSchema(bool $multiple): array
    {
        if (! $multiple) {
            return [];
        }

        return [
            ['key' => 'min_selected', 'type' => 'integer', 'default' => 0, 'min' => 0, 'max' => 64],
            ['key' => 'max_selected', 'type' => 'integer', 'default' => 64, 'min' => 1, 'max' => 64],
        ];
    }

    public function validator(bool $multiple): FieldValueValidator
    {
        return new OptionValidator($multiple);
    }
}
