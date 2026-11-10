<?php

namespace Coding1688\Fields\Options;

use InvalidArgumentException;

final readonly class OptionContext
{
    /** @param array<string, mixed> $parameters Trusted server parameters, never raw request input. */
    public function __construct(
        public string $locale = 'en',
        public ?string $scope = null,
        public ?int $actorId = null,
        public array $parameters = [],
    ) {
        if (preg_match('/^[a-zA-Z][a-zA-Z0-9_-]{0,31}$/D', $locale) !== 1) {
            throw new InvalidArgumentException('Invalid option locale.');
        }
        if ($scope !== null && (trim($scope) === '' || mb_strlen($scope) > 128)) {
            throw new InvalidArgumentException('Invalid option scope.');
        }
        if ($actorId !== null && $actorId < 1) {
            throw new InvalidArgumentException('Invalid option actor.');
        }
    }
}
