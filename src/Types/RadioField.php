<?php

namespace Coding1688\Fields\Types;

use Coding1688\Fields\Contracts\FieldType;
use Coding1688\Fields\Contracts\FieldValueValidator;
use Coding1688\Fields\Validators\OptionValidator;

final class RadioField implements FieldType
{
    public function key(): string
    {
        return 'radio';
    }

    public function component(): string
    {
        return 'radio';
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
        return true;
    }

    public function dataType(bool $multiple): string
    {
        return 'select';
    }

    public function validationSchema(bool $multiple): array
    {
        return [];
    }

    public function validator(bool $multiple): FieldValueValidator
    {
        return new OptionValidator(false);
    }
}
