<?php

namespace Coding1688\Fields\Options;

use Coding1688\Fields\Contracts\LocalizedOption;
use Coding1688\Fields\Contracts\OptionSource;
use BackedEnum;
use Illuminate\Support\Traits\Localizable;
use InvalidArgumentException;

final class EnumOptionSource implements OptionSource
{
    use Localizable;

    /** @param class-string<BackedEnum&LocalizedOption> $enum */
    public function __construct(private readonly string $enum)
    {
        if (! enum_exists($enum) || ! is_subclass_of($enum, BackedEnum::class) || ! is_subclass_of($enum, LocalizedOption::class)) {
            throw new InvalidArgumentException('Constant option source must be a localized backed enum.');
        }
    }

    public function options(OptionContext $context): array
    {
        return $this->withLocale($context->locale, function (): array {
            $options = [];
            foreach ($this->enum::cases() as $case) {
                $options[(string) $case->value] = $case->label();
            }

            return $options;
        });
    }
}
