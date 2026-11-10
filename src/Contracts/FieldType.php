<?php

namespace Coding1688\Fields\Contracts;

interface FieldType
{
    public function key(): string;

    public function component(): string;

    public function supportsMultiple(): bool;

    public function defaultMultiple(): bool;

    public function usesOptions(bool $multiple): bool;

    public function dataType(bool $multiple): string;

    /** @return list<array<string, mixed>> */
    public function validationSchema(bool $multiple): array;

    public function validator(bool $multiple): FieldValueValidator;
}
