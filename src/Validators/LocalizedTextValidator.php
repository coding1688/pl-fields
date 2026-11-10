<?php

namespace Coding1688\Fields\Validators;

use Coding1688\Fields\Contracts\FieldValueValidator;

final class LocalizedTextValidator implements FieldValueValidator
{
    public function validate(array $value, array $options = [], array $validation = []): bool
    {
        $translations = $value['value_i18n'] ?? null;
        $supported = $validation['supported_locales'] ?? ['en'];
        $required = $validation['required_locales'] ?? ['en'];
        if (! is_array($translations) || array_diff(array_keys($translations), $supported) !== []) {
            return false;
        }
        foreach ($required as $locale) {
            if (! is_string($translations[$locale] ?? null) || trim($translations[$locale]) === '') {
                return false;
            }
        }
        foreach ($translations as $translation) {
            if ($translation === null || $translation === '') {
                continue;
            }
            if (! is_string($translation)) {
                return false;
            }
            $length = mb_strlen(trim($translation));
            if ($length < ($validation['min_length'] ?? 0) || $length > ($validation['max_length'] ?? 512)) {
                return false;
            }
        }

        return true;
    }

    public function filter(array $value, array $options = [], array $validation = []): array
    {
        $supported = $validation['supported_locales'] ?? ['en'];
        $translations = array_intersect_key($value['value_i18n'] ?? [], array_flip($supported));

        return ['value_i18n' => array_map(fn ($translation): string => trim((string) $translation), $translations)];
    }

    public function scalar(array $value): ?string
    {
        $translations = $value['value_i18n'] ?? [];
        if (isset($translations['en']) && $translations['en'] !== '') {
            return $translations['en'];
        }
        foreach ($translations as $translation) {
            if (is_string($translation) && $translation !== '') {
                return $translation;
            }
        }

        return null;
    }
}
