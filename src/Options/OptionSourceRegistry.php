<?php

namespace Coding1688\Fields\Options;

use Coding1688\Fields\Contracts\OptionSource;
use Illuminate\Container\Container;
use Illuminate\Support\Traits\Localizable;
use InvalidArgumentException;

final class OptionSourceRegistry
{
    use Localizable;

    /** @var array<string, array<string, string>|string> */
    private array $categories = [];

    /** @var array<string, array{type: string, key: string, category: string, name: array<string, string>|string, provider: OptionSource}> */
    private array $sources = [];

    /** @param list<string> $locales */
    public function __construct(private readonly array $locales = ['en'], private readonly int $maxOptions = 10000)
    {
        if (! in_array('en', $locales, true)) {
            throw new InvalidArgumentException('Option sources require English as their canonical locale.');
        }
        foreach ($locales as $locale) {
            if (! is_string($locale) || preg_match('/^[a-zA-Z][a-zA-Z0-9_-]{0,31}$/D', $locale) !== 1) {
                throw new InvalidArgumentException('Invalid supported option locale.');
            }
        }
        if ($maxOptions < 1 || $maxOptions > 10000) {
            throw new InvalidArgumentException('Option source limit must be between 1 and 10000.');
        }
    }

    /** @param array<string, string>|string $name Translation dictionary or translation key. */
    public function registerCategory(string $key, array|string $name): self
    {
        $this->validateKey($key);
        $this->validateName($name);
        if (array_key_exists($key, $this->categories)) {
            throw new InvalidArgumentException('Option source category is already registered.');
        }
        $this->categories[$key] = $name;

        return $this;
    }

    /**
     * @param  array<string, string>|string  $name
     * @param  class-string|OptionSource|callable(OptionContext): array<string, string>  $provider
     */
    public function registerConstant(string $key, string $category, array|string $name, string|OptionSource|callable $provider): self
    {
        return $this->register('constant', $key, $category, $name, is_string($provider) ? new EnumOptionSource($provider) : $provider);
    }

    /**
     * @param  array<string, string>|string  $name
     * @param  OptionSource|callable(OptionContext): array<string, string>  $provider
     */
    public function registerDatabase(string $key, string $category, array|string $name, OptionSource|callable $provider): self
    {
        return $this->register('database', $key, $category, $name, $provider);
    }

    public function has(string $type, string $key): bool
    {
        return in_array($type, ['constant', 'database'], true) && isset($this->sources[$type.':'.$key]);
    }

    /** @return list<string> */
    public function types(): array
    {
        return ['manual', 'constant', 'database'];
    }

    /** @return list<array{key: string, label: string}> */
    public function categories(?OptionContext $context = null): array
    {
        $context ??= $this->defaultContext();
        $categories = [];
        foreach ($this->categories as $key => $name) {
            $categories[] = ['key' => $key, 'label' => $this->label($name, $context)];
        }

        return $categories;
    }

    /** @return list<array{type: string, key: string, label: string, category: string, category_label: string}> */
    public function sources(?OptionContext $context = null): array
    {
        $context ??= $this->defaultContext();

        return array_values(array_map(fn (array $source): array => [
            'type' => $source['type'],
            'key' => $source['key'],
            'label' => $this->label($source['name'], $context),
            'category' => $source['category'],
            'category_label' => $this->label($this->categories[$source['category']], $context),
        ], $this->sources));
    }

    /** @return array<string, string> */
    public function options(string $type, string $key, ?OptionContext $context = null): array
    {
        $source = $this->sources[$type.':'.$key] ?? null;
        if (! $this->has($type, $key) || $source === null) {
            throw new InvalidArgumentException('Unknown option source.');
        }
        $context ??= $this->defaultContext();
        $options = $this->withLocale($context->locale, fn (): array => $source['provider']->options($context));

        return $this->normalizeOptions($options);
    }

    /**
     * @param  array<string, array<string, string|null>>  $manualI18n
     * @return array<string, string>
     */
    public function resolve(string $type, ?string $key = null, array $manualI18n = [], ?OptionContext $context = null): array
    {
        if ($type !== 'manual') {
            return $this->options($type, $key ?? '', $context);
        }
        if ($key !== null && $key !== '') {
            throw new InvalidArgumentException('Manual options cannot reference a registered source.');
        }
        if ($manualI18n === []) {
            return [];
        }
        if (! is_array($manualI18n['en'] ?? null)) {
            throw new InvalidArgumentException('Manual options require canonical English keys.');
        }
        $canonical = $this->normalizeOptions($manualI18n['en']);
        foreach ($manualI18n as $locale => $localized) {
            if (! in_array($locale, $this->locales, true) || ! is_array($localized)) {
                throw new InvalidArgumentException('Invalid manual option translations.');
            }
            if (array_diff(array_keys($localized), array_keys($canonical)) !== []) {
                throw new InvalidArgumentException('Translated option keys must match canonical English keys.');
            }
            foreach ($localized as $label) {
                if ($label !== null && ! is_string($label)) {
                    throw new InvalidArgumentException('Option labels must be strings.');
                }
            }
        }
        $context ??= $this->defaultContext();
        $options = [];
        foreach ($canonical as $optionKey => $english) {
            $names = ['en' => $english];
            foreach ($manualI18n as $locale => $localized) {
                if (is_string($locale) && is_array($localized) && array_key_exists($optionKey, $localized)) {
                    if ($localized[$optionKey] !== null) {
                        $names[$locale] = $localized[$optionKey];
                    }
                }
            }
            $options[$optionKey] = $this->label($names, $context);
        }

        return $options;
    }

    /**
     * @param  array<string, string>|string  $name
     * @param  OptionSource|callable(OptionContext): array<string, string>  $provider
     */
    private function register(string $type, string $key, string $category, array|string $name, OptionSource|callable $provider): self
    {
        $this->validateKey($key);
        $this->validateName($name);
        if (! array_key_exists($category, $this->categories)) {
            throw new InvalidArgumentException('Unknown option source category.');
        }
        $sourceKey = $type.':'.$key;
        if (array_key_exists($sourceKey, $this->sources)) {
            throw new InvalidArgumentException('Option source is already registered.');
        }
        $this->sources[$sourceKey] = [
            'type' => $type, 'key' => $key, 'category' => $category, 'name' => $name,
            'provider' => $provider instanceof OptionSource ? $provider : new CallbackOptionSource($provider),
        ];

        return $this;
    }

    private function validateKey(string $key): void
    {
        if (preg_match('/^[a-zA-Z][a-zA-Z0-9._-]{0,63}$/D', $key) !== 1) {
            throw new InvalidArgumentException('Invalid option source key.');
        }
    }

    /** @param array<string, string>|string $name */
    private function validateName(array|string $name): void
    {
        if (is_string($name)) {
            if (trim($name) === '') {
                throw new InvalidArgumentException('Option source label is required.');
            }

            return;
        }
        if (! is_string($name['en'] ?? null) || trim($name['en']) === '') {
            throw new InvalidArgumentException('Option source label requires English.');
        }
        foreach ($name as $locale => $label) {
            if (! in_array($locale, $this->locales, true) || ! is_string($label)) {
                throw new InvalidArgumentException('Invalid option source translations.');
            }
        }
    }

    /** @param array<string, string>|string $names */
    private function label(array|string $names, OptionContext $context): string
    {
        if (is_string($names)) {
            return (string) __($names, [], $context->locale);
        }
        $fallback = Container::getInstance()->bound('config')
            ? (string) Container::getInstance()->make('config')->get('app.fallback_locale', 'en') : 'en';
        foreach (array_unique([$context->locale, 'en', $fallback, 'zh_CN', ...array_keys($names)]) as $locale) {
            $value = $names[$locale] ?? null;
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        throw new InvalidArgumentException('Option label is required.');
    }

    /** @param array<string, mixed> $options @return array<string, string> */
    private function normalizeOptions(array $options): array
    {
        if (count($options) > $this->maxOptions) {
            throw new InvalidArgumentException('Option source exceeds its option limit.');
        }
        $normalized = [];
        foreach ($options as $key => $label) {
            $key = (string) $key;
            if ($key === '' || trim($key) !== $key || mb_strlen($key) > 128 || preg_match('/[\x00-\x1f\x7f]/', $key) === 1) {
                throw new InvalidArgumentException('Invalid option value key.');
            }
            if (! is_string($label) || trim($label) === '') {
                throw new InvalidArgumentException('Option labels must be nonempty strings.');
            }
            if (array_key_exists($key, $normalized)) {
                throw new InvalidArgumentException('Option value is duplicated.');
            }
            $normalized[$key] = trim($label);
        }

        return $normalized;
    }

    private function defaultContext(): OptionContext
    {
        $container = Container::getInstance();
        $locale = $container->bound('translator') ? $container->make('translator')->getLocale() : 'en';

        return new OptionContext($locale);
    }
}
