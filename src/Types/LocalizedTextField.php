<?php

namespace Coding1688\Fields\Types;

use Coding1688\Fields\Contracts\FieldType;
use Coding1688\Fields\Contracts\FieldValueValidator;
use Coding1688\Fields\Validators\LocalizedTextValidator;

final class LocalizedTextField implements FieldType
{
    /** @param list<string> $locales */
    public function __construct(private readonly array $locales = ['en', 'zh_CN', 'zh_TW', 'ja', 'ko']) {}

    public function key(): string
    {
        return 'localized_text';
    }

    public function component(): string
    {
        return 'localized-input';
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
        return 'localized_text';
    }

    public function validationSchema(bool $multiple): array
    {
        return [
            ['key' => 'min_length', 'type' => 'integer', 'default' => 0, 'min' => 0, 'max' => 65536],
            ['key' => 'max_length', 'type' => 'integer', 'default' => 512, 'min' => 1, 'max' => 65536],
            ['key' => 'supported_locales', 'type' => 'locales', 'default' => $this->locales, 'options' => $this->locales],
            ['key' => 'required_locales', 'type' => 'locales', 'default' => in_array('en', $this->locales, true) ? ['en'] : array_slice($this->locales, 0, 1), 'options' => $this->locales],
        ];
    }

    public function validator(bool $multiple): FieldValueValidator
    {
        return new LocalizedTextValidator;
    }
}
