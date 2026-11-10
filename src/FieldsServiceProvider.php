<?php

namespace Coding1688\Fields;

use Coding1688\Fields\Options\OptionSourceRegistry;
use Illuminate\Support\ServiceProvider;

class FieldsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/fields.php', 'pl.fields');

        $this->app->singleton(OptionSourceRegistry::class, fn (): OptionSourceRegistry => new OptionSourceRegistry($this->locales()));

        $this->app->singleton(FieldRegistry::class, fn ($app): FieldRegistry => new FieldRegistry(
            $this->locales(),
            $app->make(OptionSourceRegistry::class),
        ));
    }

    /** @return list<string> */
    private function locales(): array
    {
        $configured = config('pl.fields.locales');

        if (is_array($configured) && $configured !== []) {
            return array_values(array_map('strval', $configured));
        }

        $platform = config('platform.locales');

        return is_array($platform) && $platform !== [] ? array_values(array_keys($platform)) : ['en'];
    }
}
