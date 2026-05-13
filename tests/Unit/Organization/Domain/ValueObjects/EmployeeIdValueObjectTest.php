<?php

declare(strict_types=1);

namespace App\Tests\Unit\Organization\Domain\ValueObjects;

use App\Organization\Domain\Exceptions\EmployeeIdException;
use App\Organization\Domain\ValueObjects\EmployeeIdValueObject;
use PHPUnit\Framework\TestCase;

final class EmployeeIdValueObjectTest extends TestCase
{
    public function testItCreatesWithValidUlid(): void
    {
        $valueObject = new EmployeeIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV');

        self::assertSame('01ARZ3NDEKTSV4RRFFQ69G5FAV', (string) $valueObject);
    }

    public function testFromStringNormalizesToUppercase(): void
    {
        $valueObject = EmployeeIdValueObject::fromString('01arz3ndektsv4rrffq69g5fav');

        self::assertSame('01ARZ3NDEKTSV4RRFFQ69G5FAV', (string) $valueObject);
    }

    public function testItThrowsWhenUlidIsInvalid(): void
    {
        $this->expectException(EmployeeIdException::class);
        $this->expectExceptionMessage('Formato de ULID inválido.');

        new EmployeeIdValueObject('invalid-ulid');
    }
}
