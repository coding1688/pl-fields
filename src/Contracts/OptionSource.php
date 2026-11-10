<?php

namespace Coding1688\Fields\Contracts;

use Coding1688\Fields\Options\OptionContext;

interface OptionSource
{
    /** @return array<string, string> */
    public function options(OptionContext $context): array;
}
