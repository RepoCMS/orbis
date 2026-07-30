<?php

declare(strict_types=1);

namespace App\Tests\Unit\Location\Domain\ValueObjects;

use App\Location\Domain\Exceptions\CityIdException;
use App\Location\Domain\ValueObjects\CityIdValueObject;
use PHPUnit\Framework\TestCase;

final class CityIdValueObjectTest extends TestCase
{
    public function testAcceptsValidUlid(): void
    {
        $vo = new CityIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV');
        self::assertSame('01ARZ3NDEKTSV4RRFFQ69G5FAV', (string) $vo);
    }

    public function testRejectsInvalid(): void
    {
        $this->expectException(CityIdException::class);
        new CityIdValueObject('bad');
    }
}
