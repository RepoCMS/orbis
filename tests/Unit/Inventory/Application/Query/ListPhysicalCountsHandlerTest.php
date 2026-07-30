<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Query;

use App\Inventory\Application\Query\ListPhysicalCounts\ListPhysicalCountsHandler;
use App\Inventory\Application\Query\ListPhysicalCounts\ListPhysicalCountsQuery;
use App\Inventory\Application\Query\Response\PhysicalCountResponse;
use App\Inventory\Domain\Entities\PhysicalCount;
use App\Inventory\Domain\Entities\PhysicalCountItem;
use App\Inventory\Domain\Enums\PhysicalCountStatus;
use App\Inventory\Domain\Repositories\PhysicalCountRepositoryInterface;
use App\Inventory\Domain\ValueObjects\PhysicalCountIdValueObject;
use App\Inventory\Domain\ValueObjects\PhysicalCountItemIdValueObject;
use PHPUnit\Framework\TestCase;

final class ListPhysicalCountsHandlerTest extends TestCase
{
    private const COUNT_ID_1  = '01HZ0000000000000000000001';
    private const COUNT_ID_2  = '01HZ0000000000000000000002';
    private const COUNT_ID_3  = '01HZ0000000000000000000003';
    private const LOCATION_A  = '01HZ0000000000000000000010';
    private const LOCATION_B  = '01HZ0000000000000000000011';
    private const USER_ID     = '01HZ0000000000000000000020';
    private const APPROVED_BY = '01HZ0000000000000000000021';
    private const ITEM_ID     = '01HZ0000000000000000000090';
    private const REJECT_REASON = 'Discrepancia en conteo';

    private function makeDraftCount(string $id, string $locationId): PhysicalCount
    {
        return PhysicalCount::start(
            new PhysicalCountIdValueObject($id),
            $locationId,
            self::USER_ID,
            null,
        );
    }

    private function makeApprovedCount(string $id, string $locationId): PhysicalCount
    {
        $count = PhysicalCount::start(
            new PhysicalCountIdValueObject($id),
            $locationId,
            self::USER_ID,
            null,
        );

        $item = PhysicalCountItem::record(
            new PhysicalCountItemIdValueObject(self::ITEM_ID),
            $count,
            'PROD-001',
            10,
            10,
        );

        $count->addItem($item);
        $count->submit();
        $count->approve(self::APPROVED_BY);

        return $count;
    }

    private function makeRejectedCount(string $id, string $locationId): PhysicalCount
    {
        $count = PhysicalCount::start(
            new PhysicalCountIdValueObject($id),
            $locationId,
            self::USER_ID,
            null,
        );

        $item = PhysicalCountItem::record(
            new PhysicalCountItemIdValueObject(self::ITEM_ID),
            $count,
            'PROD-001',
            10,
            5,
        );

        $count->addItem($item);
        $count->submit();
        $count->reject(self::REJECT_REASON);

        return $count;
    }

    public function testReturnsAllCountsWhenNoFilter(): void
    {
        $count1 = $this->makeDraftCount(self::COUNT_ID_1, self::LOCATION_A);
        $count2 = $this->makeDraftCount(self::COUNT_ID_2, self::LOCATION_B);

        $repo = $this->createMock(PhysicalCountRepositoryInterface::class);
        $repo->expects(self::once())
            ->method('findAll')
            ->willReturn([$count1, $count2]);
        $repo->expects(self::never())
            ->method('findByLocationId');

        $handler = new ListPhysicalCountsHandler($repo);
        $query   = new ListPhysicalCountsQuery();
        $result  = ($handler)($query);

        self::assertCount(2, $result);
        self::assertInstanceOf(PhysicalCountResponse::class, $result[0]);
        self::assertInstanceOf(PhysicalCountResponse::class, $result[1]);
    }

    public function testReturnsCountsByLocationId(): void
    {
        $count = $this->makeDraftCount(self::COUNT_ID_1, self::LOCATION_A);

        $repo = $this->createMock(PhysicalCountRepositoryInterface::class);
        $repo->expects(self::once())
            ->method('findByLocationId')
            ->with(self::LOCATION_A)
            ->willReturn([$count]);
        $repo->expects(self::never())
            ->method('findAll');

        $handler = new ListPhysicalCountsHandler($repo);
        $query   = new ListPhysicalCountsQuery(locationId: self::LOCATION_A);
        $result  = ($handler)($query);

        self::assertCount(1, $result);
        self::assertInstanceOf(PhysicalCountResponse::class, $result[0]);
    }

    public function testFiltersByStatus(): void
    {
        $draft    = $this->makeDraftCount(self::COUNT_ID_1, self::LOCATION_A);
        $approved = $this->makeApprovedCount(self::COUNT_ID_2, self::LOCATION_A);

        $repo = $this->createMock(PhysicalCountRepositoryInterface::class);
        $repo->method('findByLocationId')
            ->with(self::LOCATION_A)
            ->willReturn([$draft, $approved]);

        $handler = new ListPhysicalCountsHandler($repo);
        $query   = new ListPhysicalCountsQuery(locationId: self::LOCATION_A, status: 'approved');
        $result  = ($handler)($query);

        self::assertCount(1, $result);
        self::assertSame('approved', $result[0]->status);
        self::assertSame('Aprobado', $result[0]->statusLabel);
    }

    public function testReturnsAllStatusesWhenStatusFilterIsNull(): void
    {
        $draft    = $this->makeDraftCount(self::COUNT_ID_1, self::LOCATION_A);
        $approved = $this->makeApprovedCount(self::COUNT_ID_2, self::LOCATION_A);
        $rejected = $this->makeRejectedCount(self::COUNT_ID_3, self::LOCATION_A);

        $repo = $this->createMock(PhysicalCountRepositoryInterface::class);
        $repo->method('findByLocationId')
            ->with(self::LOCATION_A)
            ->willReturn([$draft, $approved, $rejected]);

        $handler = new ListPhysicalCountsHandler($repo);
        $query   = new ListPhysicalCountsQuery(locationId: self::LOCATION_A);
        $result  = ($handler)($query);

        self::assertCount(3, $result);
    }

    public function testReturnsEmptyArrayWhenNoCounts(): void
    {
        $repo = $this->createMock(PhysicalCountRepositoryInterface::class);
        $repo->method('findAll')->willReturn([]);

        $handler = new ListPhysicalCountsHandler($repo);
        $query   = new ListPhysicalCountsQuery();
        $result  = ($handler)($query);

        self::assertIsArray($result);
        self::assertCount(0, $result);
    }

    public function testReturnsEmptyArrayWhenStatusDoesNotMatch(): void
    {
        $draft = $this->makeDraftCount(self::COUNT_ID_1, self::LOCATION_A);

        $repo = $this->createMock(PhysicalCountRepositoryInterface::class);
        $repo->method('findAll')->willReturn([$draft]);

        $handler = new ListPhysicalCountsHandler($repo);
        $query   = new ListPhysicalCountsQuery(status: 'approved');
        $result  = ($handler)($query);

        self::assertIsArray($result);
        self::assertCount(0, $result);
    }

    public function testResponseContainsExpectedFieldsForDraftCount(): void
    {
        $count = $this->makeDraftCount(self::COUNT_ID_1, self::LOCATION_A);

        $repo = $this->createMock(PhysicalCountRepositoryInterface::class);
        $repo->method('findAll')->willReturn([$count]);

        $handler = new ListPhysicalCountsHandler($repo);
        $query   = new ListPhysicalCountsQuery();
        $result  = ($handler)($query);

        self::assertCount(1, $result);
        self::assertSame(self::COUNT_ID_1, $result[0]->id);
        self::assertSame(self::LOCATION_A, $result[0]->locationId);
        self::assertSame(self::USER_ID, $result[0]->countedBy);
        self::assertNull($result[0]->notes);
        self::assertSame('draft', $result[0]->status);
        self::assertSame('Borrador', $result[0]->statusLabel);
        self::assertNull($result[0]->approvedBy);
        self::assertNull($result[0]->rejectionReason);
        self::assertNull($result[0]->approvedAt);
        self::assertNull($result[0]->rejectedAt);
        self::assertIsArray($result[0]->items);
        self::assertCount(0, $result[0]->items);
    }

    public function testResponseContainsExpectedFieldsForApprovedCount(): void
    {
        $count = $this->makeApprovedCount(self::COUNT_ID_1, self::LOCATION_A);

        $repo = $this->createMock(PhysicalCountRepositoryInterface::class);
        $repo->method('findAll')->willReturn([$count]);

        $handler = new ListPhysicalCountsHandler($repo);
        $query   = new ListPhysicalCountsQuery();
        $result  = ($handler)($query);

        self::assertCount(1, $result);
        self::assertSame(self::COUNT_ID_1, $result[0]->id);
        self::assertSame(self::LOCATION_A, $result[0]->locationId);
        self::assertSame(self::USER_ID, $result[0]->countedBy);
        self::assertSame('approved', $result[0]->status);
        self::assertSame('Aprobado', $result[0]->statusLabel);
        self::assertSame(self::APPROVED_BY, $result[0]->approvedBy);
        self::assertNull($result[0]->rejectionReason);
        self::assertNotNull($result[0]->approvedAt);
        self::assertNull($result[0]->rejectedAt);
    }

    public function testResponseContainsExpectedFieldsForRejectedCount(): void
    {
        $count = $this->makeRejectedCount(self::COUNT_ID_1, self::LOCATION_A);

        $repo = $this->createMock(PhysicalCountRepositoryInterface::class);
        $repo->method('findAll')->willReturn([$count]);

        $handler = new ListPhysicalCountsHandler($repo);
        $query   = new ListPhysicalCountsQuery();
        $result  = ($handler)($query);

        self::assertCount(1, $result);
        self::assertSame(self::COUNT_ID_1, $result[0]->id);
        self::assertSame(self::LOCATION_A, $result[0]->locationId);
        self::assertSame(self::USER_ID, $result[0]->countedBy);
        self::assertSame('rejected', $result[0]->status);
        self::assertSame('Rechazado', $result[0]->statusLabel);
        self::assertNull($result[0]->approvedBy);
        self::assertSame(self::REJECT_REASON, $result[0]->rejectionReason);
        self::assertNull($result[0]->approvedAt);
        self::assertNotNull($result[0]->rejectedAt);
    }
}
