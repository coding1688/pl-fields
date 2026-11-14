<?php

use Coding1688\Fields\FieldRegistry;
use Coding1688\Fields\FieldsServiceProvider;
use Coding1688\Fields\Validators\LocalizedTextValidator;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use PHPUnit\Framework\TestCase;

final class LocalesTest extends TestCase
{
    protected function setUp(): void
    {
        Container::setInstance(new Container);
        app()->instance('config', new Repository);
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);
    }

    public function test_host_platform_config_is_not_implicitly_used(): void
    {
        config(['platform.locales' => ['ja' => 'Japanese']]);
        $method = new ReflectionMethod(FieldsServiceProvider::class, 'locales');
        self::assertSame(['en', 'zh_CN', 'zh_TW', 'ja', 'ko'], $method->invoke(new FieldsServiceProvider(app())));
        config(['fields.locales' => ['zh_CN', 'en']]);
        self::assertSame(['zh_CN', 'en'], $method->invoke(new FieldsServiceProvider(app())));
    }

    public function test_default_validator_accepts_and_preserves_all_five_languages(): void
    {
        $value = ['value_i18n' => ['en' => 'Name', 'zh_CN' => '名称', 'zh_TW' => '名稱', 'ja' => '名前', 'ko' => '이름']];
        $validator = new LocalizedTextValidator;
        self::assertTrue($validator->validate($value));
        self::assertSame($value, $validator->filter($value));
    }

    public function test_non_english_configuration_has_valid_required_locale(): void
    {
        $registry = new FieldRegistry(['ja', 'ko']);
        $settings = $registry->normalizeValidation('localized_text', false, []);
        self::assertSame(['ja', 'ko'], $settings['supported_locales']);
        self::assertSame(['ja'], $settings['required_locales']);
    }
}
