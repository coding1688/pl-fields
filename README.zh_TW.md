# pl-fields

[English](README.md) | [简体中文](README.zh_CN.md) | [繁體中文](README.zh_TW.md) | [日本語](README.ja.md) | [한국어](README.ko.md)

[Moonsea](https://moonsea.dev) · [GitHub](https://github.com/coding1688/pl-fields)

## 功能範圍

為 Laravel 提供具型別的欄位定義、值驗證及可設定的選項來源，包含欄位註冊表、驗證中繼資料、值驗證器，以及手動、常數和資料庫選項來源。不提供資料表、HTTP 路由、表單呈現器或授權規則。

內建欄位鍵為 `text`、`localized_text`、`number`、`checkbox`、`radio`、`switch`、`select`。元件識別碼僅供前端作為中繼資料，不含 Vue 元件。目前欄位目錄為內建，不提供公開的 `registerField()` API。

## 環境需求及安裝

宣告支援 PHP `^8.2`、Laravel 元件 `^11 | ^12 | ^13`。選用的 Laravel 版本可能要求更新的 PHP。本文描述目前原始碼，請選擇相符的發行版本。

```bash
composer require coding1688/pl-fields
php artisan vendor:publish --tag=fields-config
```

若 Composer 設定的套件來源找不到此套件，請先加入 VCS 儲存庫：

```bash
composer config repositories.pl-fields vcs https://github.com/coding1688/pl-fields.git
```

Laravel 自動探索 `FieldsServiceProvider`。不需要遷移、路由或佇列處理程序。

## 設定及語言

使用 `config/fields.php`：

```php
return [
    'locales' => ['en', 'zh_CN', 'zh_TW', 'ja', 'ko'],
];
```

分別對應英文、簡體中文、繁體中文、日文及韓文。宿主可明確設定其他語言。

優先順序為 **套件預設值 → 舊設定 `pl.fields` → `fields`**。對應表合併，清單取代。新的執行階段覆寫使用 `fields.*`；套件不再讀取 `platform.locales`。

**使用預設選項註冊表時，設定的語言清單必須保留 `en`。** 選項來源以英文為基準語言，不接受缺少英文的語言清單。個別本地化欄位可使用較小的支援語言集合。

支援語言與必填翻譯是不同設定。本地化欄位中繼資料預設要求英文；不支援英文時要求設定中的第一種語言。欄位層級的 `required_locales` 可覆寫此規則，包含空清單。必填語言必須屬於支援集合。

## 驗證及正規化值

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

僅在驗證成功後呼叫 `filter()`。套件使用 `value_string`、`value_number`、`value_json`、`value_i18n` 等結構化值，不會推斷資料庫欄位。驗證器會拒絕非有限數值及格式錯誤的選項值。

透過 `$registry->metadata()` 取得欄位描述，透過 `$registry->keys()` 取得欄位鍵。請使用欄位中繼資料判斷能力，不要假設所有欄位都支援多選。

## 多語言值

```php
$field = $registry->get('localized_text');
$rules = $registry->normalizeValidation($field, false, [
    'supported_locales' => ['en', 'ja'],
    'required_locales' => ['en'],
]);

$value = ['value_i18n' => ['en' => 'Name', 'ja' => '名前']];
$valid = $field->validator(false)->validate($value, [], $rules);
```

套件不會自動翻譯。應用程式提供翻譯後的值，並決定顯示及持久化方式。

## 註冊業務選項來源

在服務提供者中擴充現有註冊表，不需替換註冊表：

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

解析選項：

```php
$sources = app(OptionSourceRegistry::class);
$options = $sources->options('database', 'catalog.brand', new OptionContext('en'));
```

將回呼替換為依業務範圍篩選的資料庫查詢。`registerDatabase()` 不會自動查詢模型。`registerConstant()` 接受選項來源實作、回呼或支援的列舉介接器。手動選項透過註冊表的手動選項 API 處理。

鍵必須合法且唯一。分類及來源顯示名稱可使用翻譯字典或 Laravel 翻譯鍵。多語言選項定義以英文為基準語言。預設選項上限為 10,000，大型資料集應在註冊表外分頁。

## 架構及職責

| 元件 | 職責 |
| --- | --- |
| `FieldRegistry` | 內建欄位查詢、中繼資料及驗證設定正規化 |
| `Types/*` | 欄位能力及驗證器選擇 |
| `Validators/*` | 結構化值驗證、篩選及純量轉換 |
| `OptionSourceRegistry` | 來源註冊、選項正規化及語言處理 |
| `OptionContext` | 傳遞給來源回呼的上下文 |
| `Contracts/*` | 欄位及選項來源契約 |

身分驗證、租戶篩選、允許的來源、持久化及前端呈現由宿主負責。來源回呼收到 `OptionContext` 並不表示已獲授權。

## 開發及升級

```bash
composer install
composer test
```

CI 定義了宣告支援之 Laravel 版本的相容性工作。請查看目標發行版本的 CI 結果，不要假設所有相依組合都已在本機測試。

從舊整合遷移時，將 `pl.fields.*` 覆寫移至 `fields.*`，在選項來源語言清單中保留英文，並明確傳入宿主語言，不再依賴 `platform.locales` 後備設定。

## 授權條款

MIT。請見 [LICENSE](LICENSE)。

Copyright © 2026 [coding1688 · Moonsea](https://moonsea.dev).
