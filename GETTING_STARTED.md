# Getting started

Maintained by [coding1688 / Moonsea](https://moonsea.dev). MIT licensed.

Install with Composer. If the package is not available from your configured registry, add its GitHub VCS repository to your application's composer.json first. The existing package name and PHP namespace are unchanged. Laravel discovers the service provider automatically.

Configuration precedence is: package defaults < legacy `pl.fields` < `fields`. New applications should use only `config/fields.php`. Old `pl.*` values are accepted at provider registration time; runtime overrides must use the canonical key. Maps merge and lists replace (including an empty list). After changing cached configuration, rebuild your application's configuration cache.

## Default fields

```bash
composer require coding1688/pl-fields
php artisan vendor:publish --tag=fields-config
```

No migrations, routes or queue workers are required.

```php
$registry = app(\Coding1688\Fields\FieldRegistry::class);
$number = $registry->get('number');
$valid = $number->validator(false)->validate(['value_number' => '12.25']);
```

The value protocol uses keys such as `value_number`, `value_string`, `value_json` and `value_i18n`; pass validated values to persistence only after validation succeeds. Field component names describe metadata; this package does not ship a Vue form renderer.

## Languages

The default supported locales are `en`, `zh_CN`, `zh_TW`, `ja`, `ko`. Supported languages are not all mandatory. Localized fields require English by default when available, otherwise the first configured locale. Set a field's `required_locales` explicitly when your business rules differ; an empty list makes every translation optional. `supported_locales` constrains accepted translation keys.

## Extend without replacing registries

In your application's service provider, register business option sources after the registry is resolved:

```php
$this->app->afterResolving(\Coding1688\Fields\Options\OptionSourceRegistry::class,
    function ($sources) {
        $sources->registerCategory('catalog', 'Catalog');
        $sources->registerDatabase('catalog.brand', 'catalog', 'Brands',
            fn ($context) => ['a' => 'Brand A', 'b' => 'Brand B']);
    });
```

Replace the callback with a scoped query in your application. Authorization, tenant filtering and database ownership stay in the host application.
