<?php

declare(strict_types=1);

namespace App\Tests\Unit\Location\Domain\Entities;

use App\Location\Domain\Entities\Province;
use PHPUnit\Framework\TestCase;

final class ProvinceTest extends TestCase
{
    private const ID        = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    private const REGION_ID = '01HZ0000000000000000000001';

    public function testReconstitute(): void
    {
        $province = Province::reconstitute(self::ID, self::REGION_ID, 'Guayas');
        self::assertSame(self::ID, (string) $province->id());
        self::assertSame('Guayas', $province->name());
        self::assertSame(self::REGION_ID, (string) $province->regionId());
    }
}
