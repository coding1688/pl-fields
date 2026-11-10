<?php

namespace Coding1688\Fields\Options;

use Coding1688\Fields\Contracts\OptionSource;
use Closure;
use InvalidArgumentException;

final readonly class CallbackOptionSource implements OptionSource
{
    private Closure $callback;

    /** @param callable(OptionContext): array<string, string> $callback */
    public function __construct(callable $callback)
    {
        $this->callback = Closure::fromCallable($callback);
    }

    public function options(OptionContext $context): array
    {
        $options = ($this->callback)($context);
        if (! is_array($options)) {
            throw new InvalidArgumentException('Option source must return an option map.');
        }

        return $options;
    }
}
