<?php

declare(strict_types=1);

namespace App\Tests\Unit\Location\Domain\Entities;

use App\Location\Domain\Entities\City;
use PHPUnit\Framework\TestCase;

final class CityTest extends TestCase
{
    private const ID          = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    private const PROVINCE_ID = '01HZ0000000000000000000001';

    public function testReconstitute(): void
    {
        $city = City::reconstitute(self::ID, self::PROVINCE_ID, 'Guayaquil');
        self::assertSame(self::ID, (string) $city->id());
        self::assertSame('Guayaquil', $city->name());
        self::assertSame(self::PROVINCE_ID, (string) $city->provinceId());
    }
}
