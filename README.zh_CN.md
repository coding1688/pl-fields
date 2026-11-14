# pl-fields

[English](README.md) | [简体中文](README.zh_CN.md) | [繁體中文](README.zh_TW.md) | [日本語](README.ja.md) | [한국어](README.ko.md)

[Moonsea](https://moonsea.dev) · [GitHub](https://github.com/coding1688/pl-fields)

## 功能范围

为 Laravel 提供类型化字段定义、值校验及可配置选项来源，包括字段注册表、校验元数据、值校验器，以及手动、常量和数据库选项来源。不提供数据库表、HTTP 路由、表单渲染器或授权规则。

内置字段键为 `text`、`localized_text`、`number`、`checkbox`、`radio`、`switch`、`select`。组件标识仅是供前端使用的元数据，不包含 Vue 组件。目前字段目录内置，不提供公开的 `registerField()` API。

## 环境要求和安装

声明支持 PHP `^8.2`、Laravel 组件 `^11 | ^12 | ^13`。所选 Laravel 版本可能要求更高版本的 PHP。本文描述当前源码，请选择匹配的发布版本。

```bash
composer require coding1688/pl-fields
php artisan vendor:publish --tag=fields-config
```

如果 Composer 当前配置的软件源找不到此包，请先添加 VCS 仓库：

```bash
composer config repositories.pl-fields vcs https://github.com/coding1688/pl-fields.git
```

Laravel 自动发现 `FieldsServiceProvider`。不需要迁移、路由或队列进程。

## 配置和语言

使用 `config/fields.php`：

```php
return [
    'locales' => ['en', 'zh_CN', 'zh_TW', 'ja', 'ko'],
];
```

分别对应英语、简体中文、繁体中文、日文和韩文。宿主可显式配置其他语言。

优先级为 **包默认值 → 旧配置 `pl.fields` → `fields`**。映射合并，列表替换。新的运行时覆盖使用 `fields.*`；包不再读取 `platform.locales`。

**使用默认选项注册表时，配置的语言列表必须保留 `en`。** 选项来源以英语为基准语言，不接受缺少英语的语言列表。单个本地化字段可使用更小的支持语言集合。

支持的语言与必填翻译是不同设置。本地化字段元数据默认要求英语；不支持英语时要求配置中的第一种语言。字段级 `required_locales` 可覆盖该规则，包括空列表。必填语言必须属于支持的语言集合。

## 校验和规范化值

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

只有校验成功后才调用 `filter()`。包使用 `value_string`、`value_number`、`value_json`、`value_i18n` 等结构化值，不会推断数据库列。校验器会拒绝非有限数值和格式错误的选项值。

通过 `$registry->metadata()` 获取字段描述，通过 `$registry->keys()` 获取字段键。请使用字段元数据判断能力，不要假设所有字段都支持多选。

## 多语言值

```php
$field = $registry->get('localized_text');
$rules = $registry->normalizeValidation($field, false, [
    'supported_locales' => ['en', 'ja'],
    'required_locales' => ['en'],
]);

$value = ['value_i18n' => ['en' => 'Name', 'ja' => '名前']];
$valid = $field->validator(false)->validate($value, [], $rules);
```

包不执行自动翻译。应用提供翻译后的值，并决定显示和持久化方式。

## 注册业务选项来源

在服务提供者中扩展现有注册表，无需替换注册表：

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

解析选项：

```php
$sources = app(OptionSourceRegistry::class);
$options = $sources->options('database', 'catalog.brand', new OptionContext('en'));
```

将回调替换为按业务范围过滤的数据库查询。`registerDatabase()` 不会自动查询模型。`registerConstant()` 接受选项来源实现、回调或受支持的枚举适配器。手动选项使用注册表的手动选项 API。

键必须合法且唯一。分类和来源的显示名称可使用翻译字典或 Laravel 翻译键。多语言选项定义以英语为基准语言。默认选项数量上限为 10,000，大型数据集应在注册表外分页。

## 架构和职责

| 组件 | 职责 |
| --- | --- |
| `FieldRegistry` | 内置字段查询、元数据及校验设置规范化 |
| `Types/*` | 字段能力和校验器选择 |
| `Validators/*` | 结构化值校验、过滤及标量转换 |
| `OptionSourceRegistry` | 来源注册、选项规范化及语言处理 |
| `OptionContext` | 传递给来源回调的上下文 |
| `Contracts/*` | 字段和选项来源契约 |

认证、租户过滤、允许的来源、持久化和前端渲染由宿主负责。来源回调收到 `OptionContext` 并不表示已经获得授权。

## 开发和升级

```bash
composer install
composer test
```

CI 定义了所声明 Laravel 版本的兼容性任务。请检查目标发布版本的 CI 结果，不要假设所有依赖组合均已在本地测试。

从旧集成迁移时，将 `pl.fields.*` 覆盖迁移到 `fields.*`，在选项来源语言列表中保留英语，并显式传入宿主语言，不再依赖 `platform.locales` 回退。

## 许可证

MIT。参见 [LICENSE](LICENSE)。

Copyright © 2026 [coding1688 · Moonsea](https://moonsea.dev).
