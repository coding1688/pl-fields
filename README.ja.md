# pl-fields

[English](README.md) | [简体中文](README.zh_CN.md) | [繁體中文](README.zh_TW.md) | [日本語](README.ja.md) | [한국어](README.ko.md)

[Moonsea](https://moonsea.dev) · [GitHub](https://github.com/coding1688/pl-fields)

## 提供範囲

Laravel 向けに、型付きフィールド定義、値の検証、設定可能な選択肢ソースを提供します。フィールドレジストリ、検証メタデータ、値バリデーター、手動・定数・データベースの選択肢ソースを含みます。テーブル、HTTP ルート、フォームレンダラー、認可規則は提供しません。

組み込みフィールドキーは `text`、`localized_text`、`number`、`checkbox`、`radio`、`switch`、`select` です。コンポーネント識別子はフロントエンド用メタデータであり、Vue コンポーネントは同梱しません。フィールド一覧は組み込みで、公開された `registerField()` API はありません。

## 要件とインストール

宣言上の対応範囲は PHP `^8.2`、Laravel コンポーネント `^11 | ^12 | ^13` です。Laravel のバージョンによっては、より新しい PHP が必要です。この文書は現在のソースを説明しています。対応するリリースを選んでください。

```bash
composer require coding1688/pl-fields
php artisan vendor:publish --tag=fields-config
```

設定済み Composer レジストリで見つからない場合は、先に VCS リポジトリを追加します。

```bash
composer config repositories.pl-fields vcs https://github.com/coding1688/pl-fields.git
```

Laravel は `FieldsServiceProvider` を自動検出します。マイグレーション、ルート、ワーカーは不要です。

## 設定と言語

`config/fields.php` を使用します。

```php
return [
    'locales' => ['en', 'zh_CN', 'zh_TW', 'ja', 'ko'],
];
```

英語、簡体字中国語、繁体字中国語、日本語、韓国語に対応します。ホストは追加のロケールを明示的に設定できます。

優先順位は **パッケージ既定値 → 旧 `pl.fields` → `fields`** です。マップはマージし、リストは置換します。実行時の新しい上書きには `fields.*` を使います。`platform.locales` は読み込みません。

**既定の選択肢レジストリを使う場合、設定ロケールに `en` を残してください。** 選択肢ソースは英語を基準とし、英語を含まないロケール一覧は拒否します。個別の多言語フィールドには、より限定した言語集合を指定できます。

対応言語と必須翻訳は別の設定です。多言語フィールドのメタデータは、英語があれば英語、なければ設定の最初のロケールを必須にします。フィールドごとの `required_locales` で空リストも含めて上書きできます。必須ロケールは対応言語集合に含まれている必要があります。

## 値の検証と正規化

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

`filter()` は検証成功後にのみ呼び出してください。`value_string`、`value_number`、`value_json`、`value_i18n` などの構造化された値を使い、DB カラムは推測しません。有限でない数値や不正な選択肢値は拒否します。

`$registry->metadata()` でフィールド説明、`$registry->keys()` でキーを取得できます。すべての型が複数選択に対応すると仮定せず、メタデータで確認してください。

## 多言語の値

```php
$field = $registry->get('localized_text');
$rules = $registry->normalizeValidation($field, false, [
    'supported_locales' => ['en', 'ja'],
    'required_locales' => ['en'],
]);

$value = ['value_i18n' => ['en' => 'Name', 'ja' => '名前']];
$valid = $field->validator(false)->validate($value, [], $rules);
```

自動翻訳は行いません。翻訳済みの値の提供、表示方法、永続化はアプリケーションが担当します。

## 業務用選択肢ソースの登録

サービスプロバイダーで既存レジストリを拡張します。レジストリ自体を置き換える必要はありません。

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

選択肢を解決します。

```php
$sources = app(OptionSourceRegistry::class);
$options = $sources->options('database', 'catalog.brand', new OptionContext('en'));
```

コールバックを、業務範囲で絞り込んだ DB クエリに置き換えてください。`registerDatabase()` はモデルを自動検索しません。`registerConstant()` は選択肢ソースの実装、コールバック、対応する enum アダプターを受け取ります。手動選択肢はレジストリの手動選択肢 API で扱います。

キーは有効かつ一意である必要があります。カテゴリとソースの表示名には翻訳辞書または Laravel 翻訳キーを使えます。多言語選択肢定義の基準言語は英語です。既定の選択肢上限は 10,000 件です。大きなデータセットのページネーションはレジストリの外で行ってください。

## 構成と責任範囲

| コンポーネント | 役割 |
| --- | --- |
| `FieldRegistry` | 組み込みフィールドの検索、メタデータ、検証設定の正規化 |
| `Types/*` | フィールドの機能とバリデーター選択 |
| `Validators/*` | 構造化値の検証、フィルタリング、スカラー変換 |
| `OptionSourceRegistry` | ソース登録、選択肢の正規化、ロケール処理 |
| `OptionContext` | ソースコールバックに渡すコンテキスト |
| `Contracts/*` | フィールドと選択肢ソースの契約 |

認証、テナント絞り込み、許可するソース、永続化、フロントエンド表示はホストが担当します。コールバックが `OptionContext` を受け取っただけで認可済みとは判断しないでください。

## 開発とアップグレード

```bash
composer install
composer test
```

CI には宣言した Laravel 世代の互換性ジョブがあります。すべての依存組み合わせをローカルで検証したと考えず、対象リリースの CI 結果を確認してください。

旧統合から移行する場合は、`pl.fields.*` の上書きを `fields.*` に移し、選択肢ソースのロケールに英語を残してください。`platform.locales` のフォールバックに頼らず、ホストの言語を明示的に渡します。

## ライセンス

MIT。[LICENSE](LICENSE) を参照してください。

Copyright © 2026 [coding1688 · Moonsea](https://moonsea.dev).
