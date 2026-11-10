<?php

namespace Coding1688\Fields\Contracts;

interface FieldValueValidator
{
    /** @param array<string, mixed> $value
     * @param  array<string, string>  $options
     */
    public function validate(array $value, array $options = [], array $validation = []): bool;

    /** @param array<string, mixed> $value
     * @param  array<string, string>  $options
     * @param  array<string, mixed>  $validation
     * @return array<string, mixed>
     */
    public function filter(array $value, array $options = [], array $validation = []): array;

    /** @param array<string, mixed> $value */
    public function scalar(array $value): string|int|float|bool|null;
}
