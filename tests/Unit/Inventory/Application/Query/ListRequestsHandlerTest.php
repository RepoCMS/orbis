<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Query;

use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Inventory\Application\Query\ListRequests\ListRequestsHandler;
use App\Inventory\Application\Query\Response\RequestResponse;
use App\Inventory\Domain\Entities\Request;
use App\Inventory\Domain\Entities\RequestItem;
use App\Inventory\Domain\Enums\RequestStatus;
use App\Inventory\Domain\Repositories\RequestRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\RequestIdValueObject;
use App\Inventory\Domain\ValueObjects\RequestItemIdValueObject;
use PHPUnit\Framework\TestCase;

final class ListRequestsHandlerTest extends TestCase
{
    private const REQUEST_ID_1  = '01HZ0000000000000000000001';
    private const REQUEST_ID_2  = '01HZ0000000000000000000002';
    private const PRODUCT_ID    = '01HZ0000000000000000000010';
    private const LOCATION_A    = '01HZ0000000000000000000020';
    private const LOCATION_B    = '01HZ0000000000000000000021';
    private const USER_ID       = '01HZ0000000000000000000030';

    private function makeRequest(string $id, RequestStatus $status): Request
    {
        $item = RequestItem::create(
            id: new RequestItemIdValueObject('01HZ0000000000000000000003'),
            productId: new ProductIdValueObject(self::PRODUCT_ID),
            serializedStockId: null,
            quantity: 2,
        );

        return Request::submit(
            id:                   new RequestIdValueObject($id),
            reference:            'REQ-' . $id,
            requestedBy:          new UserIdValueObject(self::USER_ID),
            sourceLocationId:     new LocationIdValueObject(self::LOCATION_A),
            destinationLocationId: new LocationIdValueObject(self::LOCATION_B),
            notes:                null,
            items:                [$item],
        );
    }

    public function testReturnsAllRequestsWhenNoFilter(): void
    {
        $req1 = $this->makeRequest(self::REQUEST_ID_1, RequestStatus::APPROVED);
        $req2 = $this->makeRequest(self::REQUEST_ID_2, RequestStatus::FULFILLED);

        $repo = $this->createMock(RequestRepositoryInterface::class);
        $repo->expects(self::once())
            ->method('findAll')
            ->willReturn([$req1, $req2]);
        $repo->expects(self::never())
            ->method('findPending');

        $handler = new ListRequestsHandler($repo);
        $result  = ($handler)();

        self::assertCount(2, $result);
        self::assertInstanceOf(RequestResponse::class, $result[0]);
        self::assertInstanceOf(RequestResponse::class, $result[1]);
    }

    public function testReturnsPendingRequestsWhenStatusIsPending(): void
    {
        $pending = $this->makeRequest(self::REQUEST_ID_1, RequestStatus::PENDING);

        $repo = $this->createMock(RequestRepositoryInterface::class);
        $repo->expects(self::never())
            ->method('findAll');
        $repo->expects(self::once())
            ->method('findPending')
            ->willReturn([$pending]);

        $handler = new ListRequestsHandler($repo);
        $result  = ($handler)('pending');

        self::assertCount(1, $result);
        self::assertSame('pending', $result[0]->status);
        self::assertSame('Pendiente', $result[0]->statusLabel);
    }

    public function testReturnsEmptyArrayWhenNoRequests(): void
    {
        $repo = $this->createMock(RequestRepositoryInterface::class);
        $repo->method('findAll')->willReturn([]);

        $handler = new ListRequestsHandler($repo);
        $result  = ($handler)();

        self::assertIsArray($result);
        self::assertCount(0, $result);
    }

    public function testReturnsEmptyArrayWhenNoPendingRequests(): void
    {
        $repo = $this->createMock(RequestRepositoryInterface::class);
        $repo->method('findPending')->willReturn([]);

        $handler = new ListRequestsHandler($repo);
        $result  = ($handler)('pending');

        self::assertIsArray($result);
        self::assertCount(0, $result);
    }

    public function testResponseContainsExpectedFields(): void
    {
        $request = $this->makeRequest(self::REQUEST_ID_1, RequestStatus::PENDING);

        $repo = $this->createMock(RequestRepositoryInterface::class);
        $repo->method('findPending')->willReturn([$request]);

        $handler = new ListRequestsHandler($repo);
        $result  = ($handler)('pending');

        self::assertCount(1, $result);
        self::assertSame(self::REQUEST_ID_1, $result[0]->id);
        self::assertSame('REQ-' . self::REQUEST_ID_1, $result[0]->reference);
        self::assertSame(self::PRODUCT_ID, $result[0]->productId);
        self::assertSame(2, $result[0]->quantity);
        self::assertSame(self::LOCATION_A, $result[0]->sourceLocationId);
        self::assertSame(self::LOCATION_B, $result[0]->destinationLocationId);
        self::assertSame('pending', $result[0]->status);
        self::assertSame('Pendiente', $result[0]->statusLabel);
        self::assertNull($result[0]->serializedStockId);
        self::assertNull($result[0]->approvedBy);
        self::assertNull($result[0]->approvedAt);
        self::assertNull($result[0]->rejectedBy);
        self::assertNull($result[0]->rejectedAt);
        self::assertNull($result[0]->rejectionReason);
        self::assertNull($result[0]->fulfilledMovementId);
        self::assertNull($result[0]->cancelledBy);
    }

    public function testNonPendingFilterCallsFindAll(): void
    {
        $repo = $this->createMock(RequestRepositoryInterface::class);
        $repo->expects(self::once())
            ->method('findAll')
            ->willReturn([]);
        $repo->expects(self::never())
            ->method('findPending');

        $handler = new ListRequestsHandler($repo);
        $result  = ($handler)('approved');

        self::assertCount(0, $result);
    }
}
