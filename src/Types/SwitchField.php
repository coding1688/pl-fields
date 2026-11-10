<?php

namespace Coding1688\Fields\Types;

use Coding1688\Fields\Contracts\FieldType;
use Coding1688\Fields\Contracts\FieldValueValidator;
use Coding1688\Fields\Validators\BooleanValidator;

final class SwitchField implements FieldType
{
    public function key(): string
    {
        return 'switch';
    }

    public function component(): string
    {
        return 'switch';
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
        return 'boolean';
    }

    public function validationSchema(bool $multiple): array
    {
        return [];
    }

    public function validator(bool $multiple): FieldValueValidator
    {
        return new BooleanValidator;
    }
}
