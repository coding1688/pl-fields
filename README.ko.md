# pl-fields

[English](README.md) | [简体中文](README.zh_CN.md) | [繁體中文](README.zh_TW.md) | [日本語](README.ja.md) | [한국어](README.ko.md)

[Moonsea](https://moonsea.dev) · [GitHub](https://github.com/coding1688/pl-fields)

## 버전 0.5.0 기준

세 Moonsea 패키지는 `v0.5.0`부터 다시 시작합니다. 기존 태그와 GitHub Releases는 삭제하고 커밋 기록은 유지합니다. Composer 버전 제약과 잠금 파일을 명시적으로 갱신하세요. 이는 새로운 버전 기준이며 삭제된 릴리스에서 자동으로 업그레이드되지 않습니다.

## 제공 범위

Laravel용 타입이 지정된 필드 정의, 값 검증 및 설정 가능한 옵션 소스를 제공합니다. 필드 레지스트리, 검증 메타데이터, 값 검증기, 수동·상수·데이터베이스 옵션 소스를 포함합니다. 데이터베이스 테이블, HTTP 라우트, 폼 렌더러, 권한 규칙은 제공하지 않습니다.

기본 필드 키는 `text`, `localized_text`, `number`, `checkbox`, `radio`, `switch`, `select`입니다. 컴포넌트 식별자는 프런트엔드용 메타데이터이며 Vue 컴포넌트는 포함하지 않습니다. 현재 필드 목록은 내장되어 있으며 공개 `registerField()` API는 없습니다.

## 요구 사항 및 설치

선언된 지원 범위는 PHP `^8.2`, Laravel 구성 요소 `^11 | ^12 | ^13`입니다. 선택한 Laravel 버전은 더 높은 PHP 버전을 요구할 수 있습니다. 이 문서는 현재 소스를 설명하므로 일치하는 릴리스를 선택하세요.

```bash
composer require coding1688/pl-fields
php artisan vendor:publish --tag=fields-config
```

설정된 Composer 레지스트리에서 패키지를 찾을 수 없다면 VCS 저장소를 먼저 추가하세요.

```bash
composer config repositories.pl-fields vcs https://github.com/coding1688/pl-fields.git
```

Laravel은 `FieldsServiceProvider`를 자동 검색합니다. 마이그레이션, 라우트, 워커는 필요하지 않습니다.

## 설정 및 언어

`config/fields.php`를 사용합니다.

```php
return [
    'locales' => ['en', 'zh_CN', 'zh_TW', 'ja', 'ko'],
];
```

영어, 중국어 간체, 중국어 번체, 일본어, 한국어에 해당합니다. 호스트에서 추가 로케일을 명시적으로 설정할 수 있습니다.

우선순위는 **패키지 기본값 → 기존 `pl.fields` → `fields`**입니다. 맵은 병합하고 목록은 교체합니다. 새 런타임 재정의는 `fields.*`를 사용합니다. 패키지는 더 이상 `platform.locales`를 읽지 않습니다.

**기본 옵션 레지스트리를 사용할 때는 설정된 로케일 목록에 `en`을 유지하세요.** 옵션 소스는 영어를 기준 언어로 사용하며 영어가 없는 목록을 거부합니다. 개별 다국어 필드는 더 제한된 언어 집합을 사용할 수 있습니다.

지원 언어와 필수 번역은 서로 다른 설정입니다. 다국어 필드 메타데이터는 영어가 있으면 영어를, 없으면 첫 번째 설정 로케일을 필수로 합니다. 필드별 `required_locales`로 빈 목록을 포함하여 재정의할 수 있습니다. 필수 로케일은 지원 집합에 포함되어야 합니다.

## 값 검증 및 정규화

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

검증에 성공한 후에만 `filter()`를 호출하세요. 패키지는 `value_string`, `value_number`, `value_json`, `value_i18n` 등의 구조화된 값을 사용하며 데이터베이스 열을 추론하지 않습니다. 유한하지 않은 숫자와 잘못된 옵션 값은 거부합니다.

`$registry->metadata()`로 필드 설명을, `$registry->keys()`로 필드 키를 가져올 수 있습니다. 모든 필드가 다중 선택을 지원한다고 가정하지 말고 메타데이터를 확인하세요.

## 다국어 값

```php
$field = $registry->get('localized_text');
$rules = $registry->normalizeValidation($field, false, [
    'supported_locales' => ['en', 'ja'],
    'required_locales' => ['en'],
]);

$value = ['value_i18n' => ['en' => 'Name', 'ja' => '名前']];
$valid = $field->validator(false)->validate($value, [], $rules);
```

자동 번역은 수행하지 않습니다. 애플리케이션이 번역된 값을 제공하고 표시 및 저장 방식을 결정합니다.

## 업무용 옵션 소스 등록

서비스 프로바이더에서 기존 레지스트리를 확장하세요. 레지스트리를 교체할 필요는 없습니다.

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

옵션을 가져옵니다.

```php
$sources = app(OptionSourceRegistry::class);
$options = $sources->options('database', 'catalog.brand', new OptionContext('en'));
```

콜백을 업무 범위로 필터링한 데이터베이스 쿼리로 교체하세요. `registerDatabase()`는 모델을 자동 조회하지 않습니다. `registerConstant()`는 옵션 소스 구현, 콜백 또는 지원되는 enum 어댑터를 받습니다. 수동 옵션은 레지스트리의 수동 옵션 API로 처리합니다.

키는 유효하고 고유해야 합니다. 분류 및 소스 표시 이름에는 번역 사전 또는 Laravel 번역 키를 사용할 수 있습니다. 다국어 옵션 정의의 기준 언어는 영어입니다. 기본 옵션 제한은 10,000개입니다. 대규모 데이터는 레지스트리 외부에서 페이지를 나누세요.

## 구조 및 책임

| 구성 요소 | 역할 |
| --- | --- |
| `FieldRegistry` | 기본 필드 조회, 메타데이터 및 검증 설정 정규화 |
| `Types/*` | 필드 기능 및 검증기 선택 |
| `Validators/*` | 구조화된 값 검증, 필터링 및 스칼라 변환 |
| `OptionSourceRegistry` | 소스 등록, 옵션 정규화 및 로케일 처리 |
| `OptionContext` | 소스 콜백에 전달하는 컨텍스트 |
| `Contracts/*` | 필드 및 옵션 소스 계약 |

인증, 테넌트 필터링, 허용 소스, 저장 및 프런트엔드 렌더링은 호스트의 책임입니다. 소스 콜백이 `OptionContext`를 받았다고 해서 권한이 확인되었다고 판단하지 마세요.

## 개발 및 업그레이드

```bash
composer install
composer test
```

CI는 선언된 Laravel 세대의 호환성 작업을 정의합니다. 모든 의존성 조합을 로컬에서 테스트했다고 가정하지 말고 대상 릴리스의 CI 결과를 확인하세요.

기존 통합에서 이전할 때는 `pl.fields.*` 재정의를 `fields.*`로 이동하고 옵션 소스 로케일에 영어를 유지하세요. `platform.locales` 폴백에 의존하지 말고 호스트 언어를 명시적으로 전달하세요.

## 라이선스

MIT. [LICENSE](LICENSE)를 참고하세요.

Copyright © 2026 [coding1688 · Moonsea](https://moonsea.dev).
