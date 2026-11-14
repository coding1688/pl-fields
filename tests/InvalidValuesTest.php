<?php

use Coding1688\Fields\Validators\NumberValidator;
use Coding1688\Fields\Validators\OptionValidator;
use PHPUnit\Framework\TestCase;

final class InvalidValuesTest extends TestCase
{
    public function test_non_finite_numbers_are_rejected(): void
    {
        $validator = new NumberValidator;
        foreach (['1e999', '-1e999', INF, NAN] as $value) {
            self::assertFalse($validator->validate(['value_number' => $value]));
        }
        self::assertTrue($validator->validate(['value_number' => '12.25']));
    }

    public function test_nested_options_are_rejected_without_conversion(): void
    {
        $validator = new OptionValidator(true);
        foreach ([[['nested']], [new stdClass], [true], [null]] as $value) {
            self::assertFalse($validator->validate(['value_json' => $value], ['a' => 'A']));
        }
        self::assertTrue($validator->validate(['value_json' => ['a']], ['a' => 'A']));
    }
}
