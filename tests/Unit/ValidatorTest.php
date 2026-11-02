<?php

declare(strict_types=1);

namespace Tests\Unit;

use Ds\Http\Request;
use Ds\Validation\ValidationException;
use Ds\Validation\Validator;
use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    public function testValidatesAndReturnsOnlyValidatedFields(): void
    {
        $validator = Validator::make(
            ['name' => 'Deva', 'email' => 'deva@example.com', 'extra' => 'ignored'],
            ['name' => 'required|min:3|max:10', 'email' => 'required|email']
        );

        self::assertTrue($validator->passes());
        self::assertSame([
            'name' => 'Deva',
            'email' => 'deva@example.com',
        ], $validator->validated());
    }

    public function testCollectsFieldErrorsAndSupportsCustomMessages(): void
    {
        $validator = Validator::make(
            ['email' => 'not-an-email'],
            ['email' => 'required|email'],
            ['email.email' => 'Please enter a valid email.']
        );

        self::assertTrue($validator->fails());
        self::assertSame('Please enter a valid email.', $validator->first('email'));
    }

    public function testValidateThrowsStructuredException(): void
    {
        $this->expectException(ValidationException::class);
        Validator::make([], ['name' => 'required'])->validate();
    }

    public function testRequestCanValidateItsOwnInput(): void
    {
        $request = new Request(['age' => '21']);

        self::assertSame(
            ['age' => '21'],
            $request->validate(['age' => 'required|integer|min:18'])
        );
    }
}
