# pl-fields

[English](README.md) | [简体中文](README.zh_CN.md) | [繁體中文](README.zh_TW.md) | [日本語](README.ja.md) | [한국어](README.ko.md)

Typed field definitions, value validation and configurable option sources for Laravel.

[Moonsea](https://moonsea.dev) · [Source](https://github.com/coding1688/pl-fields) · [Issues](https://github.com/coding1688/pl-fields/issues) · [Getting started](GETTING_STARTED.md)

## Scope

The package provides a field registry, validation metadata, value validators and manual/constant/database option sources. It does not provide database tables, HTTP routes, a form renderer or authorization rules.

Built-in field keys are `text`, `localized_text`, `number`, `checkbox`, `radio`, `switch` and `select`. Component identifiers are metadata for your frontend; no Vue components are bundled. The current field catalog is built in; there is no public `registerField()` API.

## Requirements and installation

Declared support: PHP `^8.2`, Laravel components `^11 | ^12 | ^13`. The chosen Laravel version may require newer PHP. This document describes current source; select a matching release.

```bash
composer require coding1688/pl-fields
php artisan vendor:publish --tag=fields-config
```

If the package is unavailable through your Composer registry, configure its VCS repository first:

```bash
composer config repositories.pl-fields vcs https://github.com/coding1688/pl-fields.git
```

Laravel automatically discovers `FieldsServiceProvider`. No migrations, routes or workers are needed.

## Configuration and languages

Use `config/fields.php`:

```php
return [
    'locales' => ['en', 'zh_CN', 'zh_TW', 'ja', 'ko'],
];
```

These are English, Simplified Chinese, Traditional Chinese, Japanese and Korean. A host can explicitly configure additional locales.

Precedence is **package defaults → legacy `pl.fields` → `fields`**. Maps merge and lists replace. New runtime overrides use `fields.*`; the package no longer reads `platform.locales`.

**Keep `en` in the configured locale list when using the default option registry.** Option sources use English as their canonical language and reject a locale list without it. Individual localized fields can have a narrower supported-language set.

Supported languages and mandatory translations are separate settings. Localized-field metadata requires English when available, otherwise its first configured locale. Explicit per-field `required_locales` can override this, including an empty list. Required locales must belong to the supported set.

## Validate and normalize values

```php
use Coding1688\Fields\FieldRegistry;

$registry = app(FieldRegistry::class);
$field = $registry->get('number');
$rules = $registry->normalizeValidation($field, false, [
    'min_value' => 0,
    'max_value' => 100,
    'precision' => 2,
]);

$value = ['value_number' => '12.25'];
$validator = $field->validator(false);

if ($validator->validate($value, [], $rules)) {
    $normalized = $validator->filter($value, [], $rules);
    // Persist $normalized through your own application layer.
}
```

Call `filter()` only after validation succeeds. The package uses structured values such as `value_string`, `value_number`, `value_json` and `value_i18n`; it does not infer your database columns. Validators reject non-finite numbers and malformed option values.

Obtain available field descriptions with `$registry->metadata()` and field keys with `$registry->keys()`. Use the field's metadata rather than assuming all field types support multiple selections.

## Localized values

```php
$field = $registry->get('localized_text');
$rules = $registry->normalizeValidation($field, false, [
    'supported_locales' => ['en', 'ja'],
    'required_locales' => ['en'],
]);

$value = ['value_i18n' => ['en' => 'Name', 'ja' => '名前']];
$valid = $field->validator(false)->validate($value, [], $rules);
```

No automatic translation is performed. Your application supplies translated values and decides how they are displayed and persisted.

## Register business option sources

Extend the existing registry in your service provider; replacing the registry is unnecessary:

```php
use Coding1688\Fields\Options\OptionContext;
use Coding1688\Fields\Options\OptionSourceRegistry;

$this->app->afterResolving(OptionSourceRegistry::class,
    function (OptionSourceRegistry $sources): void {
        $sources->registerCategory('catalog', ['en' => 'Catalog', 'zh_CN' => '目录']);
        $sources->registerDatabase(
            'catalog.brand',
            'catalog',
            ['en' => 'Brands', 'zh_CN' => '品牌'],
            fn (OptionContext $context): array => ['a' => 'Brand A', 'b' => 'Brand B'],
        );
    });
```

Resolve the options:

```php
$sources = app(OptionSourceRegistry::class);
$options = $sources->options('database', 'catalog.brand', new OptionContext('en'));
```

Replace the callback with your own scoped database query. `registerDatabase()` does not query a model automatically. `registerConstant()` accepts an option-source implementation, callback or supported enum adapter. Manual options are handled through the registry's manual-option APIs.

Keys must be valid and unique. Category/source display names may be translation dictionaries or Laravel translation keys. English is the canonical locale for multilingual option definitions. The default option limit is 10,000; paginate large datasets outside this registry.

## Architecture and responsibilities

| Component | Responsibility |
| --- | --- |
| `FieldRegistry` | Built-in field lookup, metadata and validation-setting normalization |
| `Types/*` | Field capabilities and value-validator selection |
| `Validators/*` | Structured value validation, filtering and scalar conversion |
| `OptionSourceRegistry` | Source registration, option normalization and locale handling |
| `OptionContext` | Context passed to source callbacks |
| `Contracts/*` | Field and option-source contracts |

Authentication, tenant filters, allowed sources, persistence and frontend rendering remain host responsibilities. Never treat a source callback as automatically authorized because it received an `OptionContext`.

## Development and upgrades

```bash
composer install
composer test
```

CI defines compatibility jobs for the declared Laravel generations. Check the target release's CI results rather than assuming every dependency combination has been tested locally.

When migrating from older integration code, move `pl.fields.*` overrides to `fields.*`, preserve English in the option-source locale list, and pass the host's languages explicitly instead of relying on `platform.locales` fallback.

## License

MIT. See [LICENSE](LICENSE).

Copyright © 2026 [coding1688 · Moonsea](https://moonsea.dev).
