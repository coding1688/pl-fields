<?php

namespace Coding1688\Fields\Types;

use Coding1688\Fields\Contracts\FieldType;
use Coding1688\Fields\Contracts\FieldValueValidator;
use Coding1688\Fields\Validators\TextValidator;

final class TextField implements FieldType
{
    public function key(): string
    {
        return 'text';
    }

    public function component(): string
    {
        return 'input';
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
        return 'text';
    }

    public function validationSchema(bool $multiple): array
    {
        return [
            ['key' => 'min_length', 'type' => 'integer', 'default' => 0, 'min' => 0, 'max' => 512],
            ['key' => 'max_length', 'type' => 'integer', 'default' => 512, 'min' => 1, 'max' => 512],
        ];
    }

    public function validator(bool $multiple): FieldValueValidator
    {
        return new TextValidator;
    }
}
