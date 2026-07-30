<?php

declare(strict_types=1);

namespace App\Tests\Unit\Location\Domain\ValueObjects;

use App\Location\Domain\Exceptions\ProvinceIdException;
use App\Location\Domain\ValueObjects\ProvinceIdValueObject;
use PHPUnit\Framework\TestCase;

final class ProvinceIdValueObjectTest extends TestCase
{
    public function testAcceptsValidUlid(): void
    {
        $vo = new ProvinceIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV');
        self::assertSame('01ARZ3NDEKTSV4RRFFQ69G5FAV', (string) $vo);
    }

    public function testRejectsInvalid(): void
    {
        $this->expectException(ProvinceIdException::class);
        new ProvinceIdValueObject('bad');
    }

    public function testFromString(): void
    {
        $vo = ProvinceIdValueObject::fromString('01ARZ3NDEKTSV4RRFFQ69G5FAV');
        self::assertSame('01ARZ3NDEKTSV4RRFFQ69G5FAV', (string) $vo);
    }
}
