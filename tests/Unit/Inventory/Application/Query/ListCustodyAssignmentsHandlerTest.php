<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Query;

use App\Inventory\Application\Query\ListCustodyAssignments\ListCustodyAssignmentsHandler;
use App\Inventory\Application\Query\ListCustodyAssignments\ListCustodyAssignmentsQuery;
use App\Inventory\Application\Query\Response\CustodyAssignmentResponse;
use App\Inventory\Domain\Entities\Location;
use App\Inventory\Domain\Entities\SerializedStock;
use App\Inventory\Domain\Enums\LocationType;
use App\Inventory\Domain\Repositories\LocationRepositoryInterface;
use App\Inventory\Domain\Repositories\SerializedStockRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\LocationNameValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\SerializedStockIdValueObject;
use App\Inventory\Domain\ValueObjects\StockSerialNumberValueObject;
use PHPUnit\Framework\TestCase;

final class ListCustodyAssignmentsHandlerTest extends TestCase
{
    private const LOCATION_ID_1 = '01HZ0000000000000000000001';
    private const LOCATION_ID_2 = '01HZ0000000000000000000002';
    private const STOCK_ID_1    = '01HZ0000000000000000000010';
    private const STOCK_ID_2    = '01HZ0000000000000000000011';
    private const PRODUCT_ID    = '01HZ0000000000000000000020';
    private const EMPLOYEE_ID   = '01HZ0000000000000000000030';

    private function makeCustodyLocation(string $id, string $name, string $employeeId): Location
    {
        $location = Location::create(
            new LocationIdValueObject($id),
            new LocationNameValueObject($name),
            'Guayaquil',
            'Dirección ' . $name,
            LocationType::CUSTODY,
        );
        $location->update($name, 'Guayaquil', 'Dirección ' . $name, $employeeId);

        return $location;
    }

    private function makeSerializedStock(string $id, string $serial, string $locationId): SerializedStock
    {
        return SerializedStock::register(
            new SerializedStockIdValueObject($id),
            new ProductIdValueObject(self::PRODUCT_ID),
            new StockSerialNumberValueObject($serial),
            new LocationIdValueObject($locationId),
        );
    }

    public function testReturnsAssignmentsByEmployeeId(): void
    {
        $loc1   = $this->makeCustodyLocation(self::LOCATION_ID_1, 'Custodia 1', self::EMPLOYEE_ID);
        $stock1 = $this->makeSerializedStock(self::STOCK_ID_1, 'SN-001', self::LOCATION_ID_1);
        $stock2 = $this->makeSerializedStock(self::STOCK_ID_2, 'SN-002', self::LOCATION_ID_1);

        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $locationRepo->expects(self::once())
            ->method('findByEmployeeId')
            ->with(self::EMPLOYEE_ID)
            ->willReturn([$loc1]);
        $locationRepo->expects(self::never())
            ->method('findById');
        $locationRepo->expects(self::never())
            ->method('findByType');

        $stockRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $stockRepo->expects(self::once())
            ->method('findByLocation')
            ->willReturn([$stock1, $stock2]);

        $handler = new ListCustodyAssignmentsHandler($locationRepo, $stockRepo);
        $query   = new ListCustodyAssignmentsQuery(employeeId: self::EMPLOYEE_ID);
        $result  = ($handler)($query);

        self::assertCount(2, $result);
        self::assertInstanceOf(CustodyAssignmentResponse::class, $result[0]);
        self::assertInstanceOf(CustodyAssignmentResponse::class, $result[1]);
    }

    public function testReturnsAssignmentsByLocationId(): void
    {
        $loc1   = $this->makeCustodyLocation(self::LOCATION_ID_1, 'Custodia 1', self::EMPLOYEE_ID);
        $stock1 = $this->makeSerializedStock(self::STOCK_ID_1, 'SN-001', self::LOCATION_ID_1);

        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $locationRepo->expects(self::once())
            ->method('findById')
            ->willReturn($loc1);
        $locationRepo->expects(self::never())
            ->method('findByEmployeeId');
        $locationRepo->expects(self::never())
            ->method('findByType');

        $stockRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $stockRepo->expects(self::once())
            ->method('findByLocation')
            ->willReturn([$stock1]);

        $handler = new ListCustodyAssignmentsHandler($locationRepo, $stockRepo);
        $query   = new ListCustodyAssignmentsQuery(locationId: self::LOCATION_ID_1);
        $result  = ($handler)($query);

        self::assertCount(1, $result);
        self::assertInstanceOf(CustodyAssignmentResponse::class, $result[0]);
    }

    public function testReturnsEmptyWhenLocationIdNotFound(): void
    {
        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $locationRepo->expects(self::once())
            ->method('findById')
            ->willReturn(null);
        $locationRepo->expects(self::never())
            ->method('findByEmployeeId');
        $locationRepo->expects(self::never())
            ->method('findByType');

        $stockRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $stockRepo->expects(self::never())
            ->method('findByLocation');

        $handler = new ListCustodyAssignmentsHandler($locationRepo, $stockRepo);
        $query   = new ListCustodyAssignmentsQuery(locationId: self::LOCATION_ID_1);
        $result  = ($handler)($query);

        self::assertIsArray($result);
        self::assertCount(0, $result);
    }

    public function testReturnsAllCustodyLocationsWhenNoFilter(): void
    {
        $loc1 = $this->makeCustodyLocation(self::LOCATION_ID_1, 'Custodia 1', self::EMPLOYEE_ID);
        $loc2 = $this->makeCustodyLocation(self::LOCATION_ID_2, 'Custodia 2', '01HZ0000000000000000000031');

        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $locationRepo->expects(self::once())
            ->method('findByType')
            ->with(LocationType::CUSTODY)
            ->willReturn([$loc1, $loc2]);
        $locationRepo->expects(self::never())
            ->method('findById');
        $locationRepo->expects(self::never())
            ->method('findByEmployeeId');

        $stockRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $stockRepo->expects(self::exactly(2))
            ->method('findByLocation')
            ->willReturn([]);

        $handler = new ListCustodyAssignmentsHandler($locationRepo, $stockRepo);
        $query   = new ListCustodyAssignmentsQuery();
        $result  = ($handler)($query);

        self::assertIsArray($result);
        self::assertCount(0, $result);
    }

    public function testSkipsNonCustodyLocations(): void
    {
        $warehouse = Location::create(
            new LocationIdValueObject(self::LOCATION_ID_1),
            new LocationNameValueObject('Almacén'),
            'Guayaquil',
            'Av. Principal',
            LocationType::WAREHOUSE,
        );

        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $locationRepo->expects(self::once())
            ->method('findByEmployeeId')
            ->with(self::EMPLOYEE_ID)
            ->willReturn([$warehouse]);
        $locationRepo->expects(self::never())
            ->method('findById');
        $locationRepo->expects(self::never())
            ->method('findByType');

        $stockRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $stockRepo->expects(self::never())
            ->method('findByLocation');

        $handler = new ListCustodyAssignmentsHandler($locationRepo, $stockRepo);
        $query   = new ListCustodyAssignmentsQuery(employeeId: self::EMPLOYEE_ID);
        $result  = ($handler)($query);

        self::assertIsArray($result);
        self::assertCount(0, $result);
    }

    public function testReturnsEmptyArrayWhenNoLocations(): void
    {
        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $locationRepo->method('findByType')->willReturn([]);

        $stockRepo = $this->createMock(SerializedStockRepositoryInterface::class);

        $handler = new ListCustodyAssignmentsHandler($locationRepo, $stockRepo);
        $query   = new ListCustodyAssignmentsQuery();
        $result  = ($handler)($query);

        self::assertIsArray($result);
        self::assertCount(0, $result);
    }

    public function testResponseContainsExpectedFields(): void
    {
        $location = $this->makeCustodyLocation(self::LOCATION_ID_1, 'Custodia Test', self::EMPLOYEE_ID);
        $stock    = $this->makeSerializedStock(self::STOCK_ID_1, 'SN-001', self::LOCATION_ID_1);

        $locationRepo = $this->createMock(LocationRepositoryInterface::class);
        $locationRepo->method('findByType')->willReturn([$location]);

        $stockRepo = $this->createMock(SerializedStockRepositoryInterface::class);
        $stockRepo->method('findByLocation')->willReturn([$stock]);

        $handler = new ListCustodyAssignmentsHandler($locationRepo, $stockRepo);
        $query   = new ListCustodyAssignmentsQuery();
        $result  = ($handler)($query);

        self::assertCount(1, $result);
        self::assertSame(self::LOCATION_ID_1, $result[0]->custodyLocationId);
        self::assertSame('Custodia Test', $result[0]->custodyLocationName);
        self::assertSame(self::EMPLOYEE_ID, $result[0]->employeeId);
        self::assertSame(self::STOCK_ID_1, $result[0]->stockItemId);
        self::assertSame('SN-001', $result[0]->serialNumber);
        self::assertSame(self::PRODUCT_ID, $result[0]->productId);
        self::assertTrue($result[0]->isAvailable);
    }
}
