<?php

declare(strict_types=1);

namespace Tests\Compatibility;

use Ds\Foundations\Validator\ValidationProvider;
use Ds\Foundations\Validator\ValidationResult;
use Ds\Foundations\Validator\ValidationRule;
use Ds\Foundations\Validator\Validator as LegacyValidator;
use Ds\Validation\ValidationException;
use PHPUnit\Framework\TestCase;

final class LegacyValidatorTest extends TestCase
{
    public function testLegacyBuiltInRulesExposeStructuredResultsWithoutTerminatingRequest(): void
    {
        $validator = LegacyValidator::make(
            ['name' => 'Ada', 'age' => '3', 'extra' => 'ignored'],
            ['name' => 'required|min:2', 'age' => 'min:5']
        );

        self::assertTrue($validator->fails());
        self::assertSame(['age' => 'age field cannot lower than 5'], $validator->errors());
        self::assertSame(['name' => 'Ada'], $validator->validated());
    }

    public function testLegacyCustomRulesCanBeRegistered(): void
    {
        ValidationProvider::register(new ValidationRule(
            'starts_with_ds_test',
            ' must start with DS',
            static fn (mixed $value, string $field, ?array $options, ValidationRule $rule): bool|ValidationResult =>
                is_string($value) && str_starts_with($value, 'DS')
                    ? true
                    : new ValidationResult(false, $field . $rule->message)
        ));

        $validator = LegacyValidator::make(
            ['code' => 'DS-001'],
            ['code' => 'required|starts_with_ds_test']
        );

        self::assertTrue($validator->passes());
        self::assertSame(['code' => 'DS-001'], $validator->validate());
    }

    public function testLegacyValidateThrowsStructuredExceptionInsteadOfRedirectingOrExiting(): void
    {
        $validator = LegacyValidator::make([], ['name' => 'required']);

        $this->expectException(ValidationException::class);
        $validator->validate();
    }

    public function testUnknownLegacyRuleFailsClosed(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        LegacyValidator::make(['name' => 'Ada'], ['name' => 'unknown_legacy_rule']);
    }
}
