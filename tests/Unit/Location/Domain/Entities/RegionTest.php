<?php

declare(strict_types=1);

namespace App\Tests\Unit\Location\Domain\Entities;

use App\Location\Domain\Entities\Region;
use PHPUnit\Framework\TestCase;

final class RegionTest extends TestCase
{
    private const ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';

    public function testReconstitute(): void
    {
        $region = Region::reconstitute(self::ID, 'Costa');
        self::assertSame(self::ID, (string) $region->id());
        self::assertSame('Costa', $region->name());
    }
}
