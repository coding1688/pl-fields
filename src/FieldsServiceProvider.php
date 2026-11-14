<?php

namespace Coding1688\Fields;

use Coding1688\Fields\Options\OptionSourceRegistry;
use Illuminate\Support\ServiceProvider;

class FieldsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $defaults = require __DIR__.'/../config/fields.php';
        config(['fields' => $this->merge($this->merge($defaults, (array) config('pl.fields', [])), (array) config('fields', []))]);

        // Hosts may register their own registry with extra sources; only
        // provide a working default when nothing is bound yet.
        if (! $this->app->bound(OptionSourceRegistry::class)) {
            $this->app->singleton(OptionSourceRegistry::class, fn (): OptionSourceRegistry => new OptionSourceRegistry($this->locales()));
        }

        if (! $this->app->bound(FieldRegistry::class)) {
            $this->app->singleton(FieldRegistry::class, fn ($app): FieldRegistry => new FieldRegistry(
                $this->locales(),
                $app->make(OptionSourceRegistry::class),
            ));
        }
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__.'/../config/fields.php' => config_path('fields.php')], 'fields-config');
        }
    }

    /** @return list<string> */
    private function locales(): array
    {
        $configured = config('fields.locales');

        if (is_array($configured) && $configured !== []) {
            return array_values(array_map('strval', $configured));
        }

        return ['en', 'zh_CN', 'zh_TW', 'ja', 'ko'];
    }

    private function merge(array $defaults, array $overrides): array
    {
        foreach ($overrides as $key => $value) {
            $current = $defaults[$key] ?? null;
            $defaults[$key] = is_array($value) && is_array($current) && ! array_is_list($value) && ! array_is_list($current)
                ? $this->merge($current, $value)
                : $value;
        }

        return $defaults;
    }
}
