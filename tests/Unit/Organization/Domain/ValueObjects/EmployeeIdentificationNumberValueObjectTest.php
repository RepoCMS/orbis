<?php

declare(strict_types=1);

namespace App\Tests\Unit\Organization\Domain\ValueObjects;

use App\Organization\Domain\Exceptions\EmployeeIdentificationNumberException;
use App\Organization\Domain\ValueObjects\EmployeeIdentificationNumberValueObject;
use PHPUnit\Framework\TestCase;

final class EmployeeIdentificationNumberValueObjectTest extends TestCase
{
    private const VALID_ID = '1710034065';

    public function testItCreatesWithValidIdentificationNumber(): void
    {
        $valueObject = new EmployeeIdentificationNumberValueObject(self::VALID_ID);

        self::assertSame(self::VALID_ID, (string) $valueObject);
    }

    public function testItThrowsWhenIdentificationNumberIsEmpty(): void
    {
        $this->expectException(EmployeeIdentificationNumberException::class);
        $this->expectExceptionMessage('El número de identificación no puede estar vacío.');

        new EmployeeIdentificationNumberValueObject('');
    }

    public function testItThrowsWhenIdentificationNumberHasNonDigits(): void
    {
        $this->expectException(EmployeeIdentificationNumberException::class);
        $this->expectExceptionMessage('El número de identificación debe contener solo dígitos del 0 al 9.');

        new EmployeeIdentificationNumberValueObject('ABC123');
    }

    public function testItThrowsWhenIdentificationNumberIsTooLong(): void
    {
        $this->expectException(EmployeeIdentificationNumberException::class);
        $this->expectExceptionMessage('El número de identificación no puede exceder los 10 caracteres.');

        new EmployeeIdentificationNumberValueObject('12345678901234');
    }
}
