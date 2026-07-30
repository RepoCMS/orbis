<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Query;

use App\Inventory\Application\Query\ListSubLocationsByLocation\ListSubLocationsByLocationHandler;
use App\Inventory\Application\Query\ListSubLocationsByLocation\ListSubLocationsByLocationQuery;
use App\Inventory\Application\Query\Response\SubLocationResponse;
use App\Inventory\Domain\Entities\SubLocation;
use App\Inventory\Domain\Enums\SubLocationType;
use App\Inventory\Domain\Repositories\SubLocationRepositoryInterface;
use App\Inventory\Domain\ValueObjects\SubLocationIdValueObject;
use PHPUnit\Framework\TestCase;

final class ListSubLocationsByLocationHandlerTest extends TestCase
{
    private const LOCATION_ID   = '01HZ0000000000000000000010';
    private const RACK_ID       = '01HZ0000000000000000000001';
    private const SHELF_ID      = '01HZ0000000000000000000002';
    private const POSITION_ID   = '01HZ0000000000000000000003';
    private const SHELF_2_ID    = '01HZ0000000000000000000004';

    private function makeSubLocation(
        string $id,
        SubLocationType $type,
        string $name,
        string $code,
        ?SubLocation $parent = null,
    ): SubLocation {
        return SubLocation::create(
            new SubLocationIdValueObject($id),
            self::LOCATION_ID,
            $type,
            $name,
            $code,
            $parent,
        );
    }

    private function makeHandler(array $roots): ListSubLocationsByLocationHandler
    {
        $repo = $this->createMock(SubLocationRepositoryInterface::class);
        $repo->method('findRootsByLocationId')->willReturn($roots);

        return new ListSubLocationsByLocationHandler($repo);
    }

    public function testReturnsTreeWhenRootsExist(): void
    {
        $rack = $this->makeSubLocation(self::RACK_ID, SubLocationType::RACK, 'Rack A', 'RA-001');

        $handler = $this->makeHandler([$rack]);
        $query   = new ListSubLocationsByLocationQuery(self::LOCATION_ID);
        $result  = ($handler)($query);

        self::assertCount(1, $result);
        self::assertInstanceOf(SubLocationResponse::class, $result[0]);
    }

    public function testReturnsEmptyArrayWhenNoRoots(): void
    {
        $handler = $this->makeHandler([]);
        $query   = new ListSubLocationsByLocationQuery(self::LOCATION_ID);
        $result  = ($handler)($query);

        self::assertIsArray($result);
        self::assertCount(0, $result);
    }

    public function testResponseContainsExpectedFields(): void
    {
        $rack = $this->makeSubLocation(self::RACK_ID, SubLocationType::RACK, 'Rack Principal', 'RP-001');

        $handler = $this->makeHandler([$rack]);
        $query   = new ListSubLocationsByLocationQuery(self::LOCATION_ID);
        $result  = ($handler)($query);

        self::assertCount(1, $result);
        self::assertSame(self::RACK_ID, $result[0]->id);
        self::assertSame(self::LOCATION_ID, $result[0]->locationId);
        self::assertSame('rack', $result[0]->type);
        self::assertSame('Rack', $result[0]->typeLabel);
        self::assertSame('Rack Principal', $result[0]->name);
        self::assertSame('RP-001', $result[0]->code);
        self::assertNull($result[0]->parentId);
        self::assertSame(1, $result[0]->depth);
        self::assertEmpty($result[0]->children);
    }

    public function testBuildsNestedTree(): void
    {
        $rack   = $this->makeSubLocation(self::RACK_ID, SubLocationType::RACK, 'Rack A', 'RA-001');
        $shelf  = $this->makeSubLocation(self::SHELF_ID, SubLocationType::SHELF, 'Estante 1', 'E1', $rack);
        $shelf2 = $this->makeSubLocation(self::SHELF_2_ID, SubLocationType::SHELF, 'Estante 2', 'E2', $rack);
        $pos    = $this->makeSubLocation(self::POSITION_ID, SubLocationType::POSITION, 'Pos 1', 'P1', $shelf);

        $rack->addChild($shelf);
        $rack->addChild($shelf2);
        $shelf->addChild($pos);

        $handler = $this->makeHandler([$rack]);
        $query   = new ListSubLocationsByLocationQuery(self::LOCATION_ID);
        $result  = ($handler)($query);

        self::assertCount(1, $result);
        self::assertSame('Rack A', $result[0]->name);
        self::assertSame(1, $result[0]->depth);

        $children = $result[0]->children;
        self::assertCount(2, $children);
        self::assertSame('Estante 1', $children[0]->name);
        self::assertSame(2, $children[0]->depth);
        self::assertSame('Estante 2', $children[1]->name);
        self::assertSame(2, $children[1]->depth);

        $grandchildren = $children[0]->children;
        self::assertCount(1, $grandchildren);
        self::assertSame('Pos 1', $grandchildren[0]->name);
        self::assertSame(3, $grandchildren[0]->depth);
        self::assertSame('position', $grandchildren[0]->type);
        self::assertEmpty($grandchildren[0]->children);
    }

    public function testMapsAllSubLocationTypes(): void
    {
        $rack    = $this->makeSubLocation(self::RACK_ID, SubLocationType::RACK, 'Rack A', 'RA-001');
        $shelf   = $this->makeSubLocation(self::SHELF_ID, SubLocationType::SHELF, 'Estante 1', 'E1');
        $shelf2  = $this->makeSubLocation(self::SHELF_2_ID, SubLocationType::SHELF, 'Estante 2', 'E2', $shelf);
        $pos     = $this->makeSubLocation(self::POSITION_ID, SubLocationType::POSITION, 'Pos 1', 'P1', $shelf2);

        $shelf->addChild($shelf2);
        $shelf2->addChild($pos);

        $handler = $this->makeHandler([$rack, $shelf]);
        $query   = new ListSubLocationsByLocationQuery(self::LOCATION_ID);
        $result  = ($handler)($query);

        self::assertCount(2, $result);
        self::assertSame('rack', $result[0]->type);
        self::assertSame('Rack', $result[0]->typeLabel);
        self::assertSame('shelf', $result[1]->type);
        self::assertSame('Estante', $result[1]->typeLabel);
    }
}
