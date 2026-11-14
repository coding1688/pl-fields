<?php

use Coding1688\Fields\FieldsServiceProvider;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use PHPUnit\Framework\TestCase;

final class ConfigurationTest extends TestCase
{
    protected function tearDown(): void
    {
        Container::setInstance(null);
    }

    public function test_defaults_register_without_host_configuration(): void
    {
        $app = new Application;
        $app->instance('config', new Repository);
        (new FieldsServiceProvider($app))->register();
        self::assertIsArray(config('fields'));
        self::assertNotEmpty(config('fields'));
    }

    public function test_canonical_config_wins_and_empty_lists_replace_defaults(): void
    {
        $app = new Application;
        $app->instance('config', new Repository([
            'pl' => ['fields' => ['test' => ['legacy' => true, 'value' => 'old'], 'locales' => ['ja']]],
            'fields' => ['test' => ['value' => 'new'], 'locales' => []],
        ]));
        (new FieldsServiceProvider($app))->register();
        self::assertSame('new', config('fields.test.value'));
        self::assertTrue(config('fields.test.legacy'));
        self::assertSame([], config('fields.locales'));
    }

    public function test_legacy_configuration_is_accepted(): void
    {
        $app = new Application;
        $app->instance('config', new Repository(['pl' => ['fields' => ['locales' => ['ko']]]]));
        (new FieldsServiceProvider($app))->register();
        self::assertSame(['ko'], config('fields.locales'));
    }

    public function test_config_can_be_published(): void
    {
        $app = new Application;
        $app->instance('config', new Repository);
        $provider = new FieldsServiceProvider($app);
        $provider->register();
        $provider->boot();
        self::assertNotEmpty(ServiceProvider::pathsToPublish(FieldsServiceProvider::class, 'fields-config'));
    }
}
