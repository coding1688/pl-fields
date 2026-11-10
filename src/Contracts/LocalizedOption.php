<?php

namespace Coding1688\Fields\Contracts;

interface LocalizedOption
{
    public function label(): string;

    /** @return list<string> */
    public static function values(): array;

    /** @return array<string, string> */
    public static function options(): array;
}
