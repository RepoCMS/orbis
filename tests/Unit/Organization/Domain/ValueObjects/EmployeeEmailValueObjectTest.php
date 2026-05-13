<?php

declare(strict_types=1);

namespace App\Tests\Unit\Organization\Domain\ValueObjects;

use App\Organization\Domain\Exceptions\EmployeeEmailException;
use App\Organization\Domain\ValueObjects\EmployeeEmailValueObject;
use PHPUnit\Framework\TestCase;

final class EmployeeEmailValueObjectTest extends TestCase
{
    public function testItCreatesWithValidEmail(): void
    {
        $valueObject = new EmployeeEmailValueObject('test@mail.com');

        self::assertSame('test@mail.com', (string) $valueObject);
    }

    public function testItNormalizesEmail(): void
    {
        $valueObject = new EmployeeEmailValueObject('  TEST@MAIL.COM  ');

        self::assertSame('test@mail.com', (string) $valueObject);
    }

    public function testItThrowsWhenEmailIsEmpty(): void
    {
        $this->expectException(EmployeeEmailException::class);
        $this->expectExceptionMessage('El correo electrónico no puede estar vacío.');

        new EmployeeEmailValueObject('');
    }

    public function testItThrowsWhenEmailFormatIsInvalid(): void
    {
        $this->expectException(EmployeeEmailException::class);
        $this->expectExceptionMessage('Formato de correo electrónico inválido.');

        new EmployeeEmailValueObject('correo-invalido');
    }

    public function testFromStringCreatesTheSameValueObject(): void
    {
        $valueObject = EmployeeEmailValueObject::fromString('  TEST@MAIL.COM  ');

        self::assertSame('test@mail.com', (string) $valueObject);
    }
}
