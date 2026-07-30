<?php

declare(strict_types=1);

namespace App\Tests\Unit\Location\Domain\ValueObjects;

use App\Location\Domain\Exceptions\RegionIdException;
use App\Location\Domain\ValueObjects\RegionIdValueObject;
use PHPUnit\Framework\TestCase;

final class RegionIdValueObjectTest extends TestCase
{
    public function testAcceptsValidUlid(): void
    {
        $vo = new RegionIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV');
        self::assertSame('01ARZ3NDEKTSV4RRFFQ69G5FAV', (string) $vo);
    }

    public function testNormalizesUppercase(): void
    {
        $vo = new RegionIdValueObject('01arz3ndektsv4rrffq69g5fav');
        self::assertSame('01ARZ3NDEKTSV4RRFFQ69G5FAV', (string) $vo);
    }

    public function testRejectsInvalidUlid(): void
    {
        $this->expectException(RegionIdException::class);
        new RegionIdValueObject('invalid');
    }

    public function testRejectsEmpty(): void
    {
        $this->expectException(RegionIdException::class);
        new RegionIdValueObject('');
    }
}
