<?php

namespace Coding1688\Fields;

use Coding1688\Fields\Contracts\FieldType;
use Coding1688\Fields\Options\OptionSourceRegistry;
use Coding1688\Fields\Types\CheckboxField;
use Coding1688\Fields\Types\LocalizedTextField;
use Coding1688\Fields\Types\NumberField;
use Coding1688\Fields\Types\RadioField;
use Coding1688\Fields\Types\SelectField;
use Coding1688\Fields\Types\SwitchField;
use Coding1688\Fields\Types\TextField;
use InvalidArgumentException;

final class FieldRegistry
{
    /** @var array<string, FieldType> */
    private array $fields = [];

    /** @param list<string> $locales */
    public function __construct(array $locales = ['en'], private readonly OptionSourceRegistry $optionSources = new OptionSourceRegistry)
    {
        $fields = [new TextField, new LocalizedTextField($locales), new NumberField, new CheckboxField, new RadioField, new SwitchField, new SelectField];
        foreach ($fields as $field) {
            $this->fields[$field->key()] = $field;
        }
    }

    public function optionSources(): OptionSourceRegistry
    {
        return $this->optionSources;
    }

    public function get(string $key): FieldType
    {
        return $this->fields[$key] ?? throw new InvalidArgumentException('Unknown field type: '.$key);
    }

    public function has(string $key): bool
    {
        return isset($this->fields[$key]);
    }

    /** @return list<string> */
    public function keys(): array
    {
        return array_keys($this->fields);
    }

    /** @return list<array<string, mixed>> */
    public function metadata(): array
    {
        return array_values(array_map(fn (FieldType $field): array => [
            'key' => $field->key(),
            'component' => $field->component(),
            'supports_multiple' => $field->supportsMultiple(),
            'default_multiple' => $field->defaultMultiple(),
            'options_mode' => $field->usesOptions(false) ? 'always' : ($field->usesOptions(true) ? 'multiple' : 'never'),
            'data_type' => $field->dataType(false),
            'multiple_data_type' => $field->dataType(true),
            'validation_schema' => $field->validationSchema(false),
            'multiple_validation_schema' => $field->validationSchema(true),
            'validation_defaults' => $this->normalizeValidation($field, false, []),
            'multiple_validation_defaults' => $this->normalizeValidation($field, true, []),
        ], $this->fields));
    }

    /** @param array<string, mixed> $validation
     * @return array<string, mixed>
     */
    public function normalizeValidation(FieldType|string $field, bool $multiple, array $validation): array
    {
        $field = is_string($field) ? $this->get($field) : $field;
        $normalized = [];
        foreach ($field->validationSchema($multiple) as $setting) {
            $value = $validation[$setting['key']] ?? $setting['default'];
            $normalized[$setting['key']] = $this->normalizeSetting($setting, $value);
        }
        foreach ([['min_length', 'max_length'], ['min_selected', 'max_selected'], ['min_value', 'max_value']] as [$minimum, $maximum]) {
            if (isset($normalized[$minimum], $normalized[$maximum]) && $normalized[$minimum] > $normalized[$maximum]) {
                throw new InvalidArgumentException($minimum.' must not exceed '.$maximum);
            }
        }
        if (isset($normalized['required_locales'], $normalized['supported_locales']) && array_diff($normalized['required_locales'], $normalized['supported_locales']) !== []) {
            throw new InvalidArgumentException('Required locales must be supported locales.');
        }

        return $normalized;
    }

    /** @param array<string, mixed> $setting */
    private function normalizeSetting(array $setting, mixed $value): mixed
    {
        if ($value === null && ($setting['nullable'] ?? false)) {
            return null;
        }
        if ($setting['type'] === 'locales') {
            if (! is_array($value) || $value === [] || array_diff($value, $setting['options']) !== []) {
                throw new InvalidArgumentException('Invalid locale selection.');
            }

            return array_values(array_unique($value));
        }
        if ($setting['type'] === 'integer') {
            if (filter_var($value, FILTER_VALIDATE_INT) === false) {
                throw new InvalidArgumentException('Invalid integer validation setting.');
            }
            $value = (int) $value;
        } elseif ($setting['type'] === 'decimal') {
            if (! is_numeric($value)) {
                throw new InvalidArgumentException('Invalid decimal validation setting.');
            }
            $value = (float) $value;
        }
        if (isset($setting['min']) && $value < $setting['min'] || isset($setting['max']) && $value > $setting['max']) {
            throw new InvalidArgumentException('Validation setting is outside its allowed range.');
        }

        return $value;
    }
}
