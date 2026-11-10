# coding1688/pl-fields

Laravel 字段注册与选项来源：把「字段类型 + 校验 + 可选值」从业务模型里抽出来，后台表单、筛选器和 API 校验共用一套定义。

- PHP `^8.2`，Laravel `^11 | ^12 | ^13`
- 无业务模型依赖，无数据库表，无迁移
- 内置 7 种字段类型、5 个校验器、3 种选项来源

## 安装

```json
{
    "repositories": [
        { "type": "vcs", "url": "https://github.com/coding1688/pl-fields.git" }
    ],
    "require": {
        "coding1688/pl-fields": "^0.1"
    }
}
```

```bash
composer update coding1688/pl-fields
```

包自动注册 `Coding1688\Fields\FieldsServiceProvider`，默认绑定：

- `OptionSourceRegistry`（单例，locales 取 `pl.fields.locales` → `platform.locales` → `['en']`）
- `FieldRegistry`（单例，注册 7 种字段类型）

宿主可以覆盖这两个绑定来追加自己的字段类型或选项来源。

## 用法

### 字段类型

```php
use Coding1688\Fields\FieldRegistry;

$fields = app(FieldRegistry::class);

$fields->has('text');            // true
$fields->get('select');          // FieldType 实例
$fields->metadata();             // 后台渲染用的元数据（类型、组件、是否多选、校验 schema）
```

内置：`text`、`localized_text`、`number`、`checkbox`、`radio`、`switch`、`select`。

### 选项来源

```php
use Coding1688\Fields\Options\OptionContext;
use Coding1688\Fields\Options\OptionSourceRegistry;

$sources = app(OptionSourceRegistry::class);

// 静态数组
$sources->registerCallback('store.status', 'store', 'store/status', fn (OptionContext $context): array => [
    'open' => 'Open',
    'closed' => 'Closed',
]);

// 枚举
$sources->registerConstant('product.marketTier', 'product', 'product/marketTier', ProductTier::class);

// 取值（带上下文：locale、scope、actor id）
$options = $sources->options('store.status', new OptionContext('zh_CN', 'backend', 1));
```

`OptionContext` 由宿主解析（后台/前台/运行时），包不读 `request()`。

### 校验

每个 `FieldType` 自带 `validationSchema()` 与 `validator(bool $multiple)`，可以据此生成 Laravel 校验规则：

```php
$type = app(FieldRegistry::class)->get('select');
$rules = $type->validationSchema($type->defaultMultiple());
$validator = $type->validator($type->defaultMultiple());
$validator->validate($value, $definition);
```

### 追加自定义字段类型

```php
use Coding1688\Fields\Contracts\FieldType;

final class RichTextField implements FieldType { /* ... */ }

$this->app->resolved(FieldRegistry::class, function (FieldRegistry $registry): void {
    $registry->register(new RichTextField);
});
```

（`FieldRegistry::register()` 见源码；如果你需要不同的扩展方式，可以在宿主 Provider 里直接构造并绑定 `FieldRegistry`。）

## 配置

| 键 | 默认 | 说明 |
| --- | --- | --- |
| `pl.fields.locales` | `null` | 本地化字段使用的语言；null 时取宿主的 `platform.locales`，再退回 `['en']` |

## pl-* 系列

- [pl-files](https://github.com/coding1688/pl-files)：上传、引用追踪、多磁盘存储、多云复制
- pl-fields（本包）
- [pl-debug](https://github.com/coding1688/pl-debug)：请求级 SQL 性能分析与工具栏
