<?php

namespace Coding1688\Fields\Validators;

use Coding1688\Fields\Contracts\FieldValueValidator;

final class OptionValidator implements FieldValueValidator
{
    public function __construct(private readonly bool $multiple) {}

    public function validate(array $value, array $options = [], array $validation = []): bool
    {
        if ($this->multiple && (! array_key_exists('value_json', $value) || ! is_array($value['value_json']))) {
            return false;
        }
        $selected = $this->multiple
            ? $value['value_json']
            : array_filter([$value['value_string'] ?? null], fn ($item): bool => $item !== null && $item !== '');
        foreach ($selected as $item) {
            if (! is_string($item) && ! is_int($item)) {
                return false;
            }
        }
        if ($this->multiple && $selected === []) {
            return ($validation['min_selected'] ?? 0) === 0 && ($validation['max_selected'] ?? 64) >= 0;
        }

        if ($selected === [] || array_diff(array_map('strval', $selected), array_map('strval', array_keys($options))) !== []) {
            return false;
        }
        $count = count(array_unique(array_map('strval', $selected)));

        return ! $this->multiple || ($count >= ($validation['min_selected'] ?? 0) && $count <= ($validation['max_selected'] ?? 64));
    }

    public function filter(array $value, array $options = [], array $validation = []): array
    {
        if (! $this->multiple) {
            return ['value_string' => (string) $value['value_string']];
        }

        return ['value_json' => array_values(array_unique(array_map('strval', $value['value_json'] ?? [])))];
    }

    public function scalar(array $value): ?string
    {
        $selected = $this->multiple ? ($value['value_json'][0] ?? null) : ($value['value_string'] ?? null);

        return $selected === null ? null : (string) $selected;
    }
}
