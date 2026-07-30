<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Query;

use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Inventory\Application\Exception\RequestNotFoundException;
use App\Inventory\Application\Query\FindRequestById\FindRequestByIdHandler;
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

final class FindRequestByIdHandlerTest extends TestCase
{
    private const REQUEST_ID   = '01HZ0000000000000000000060';
    private const REQUESTED_BY = '01HZ0000000000000000000040';
    private const PRODUCT_ID   = '01HZ0000000000000000000001';
    private const SOURCE_LOC   = '01HZ0000000000000000000002';
    private const DEST_LOC     = '01HZ0000000000000000000003';

    private function makeRequest(RequestStatus $status = RequestStatus::PENDING): Request
    {
        $item = RequestItem::create(
            id: new RequestItemIdValueObject('01HZ0000000000000000000004'),
            productId: new ProductIdValueObject(self::PRODUCT_ID),
            serializedStockId: null,
            quantity: 10,
        );

        return Request::submit(
            new RequestIdValueObject(self::REQUEST_ID),
            'REQ-001',
            new UserIdValueObject(self::REQUESTED_BY),
            new LocationIdValueObject(self::SOURCE_LOC),
            new LocationIdValueObject(self::DEST_LOC),
            'Solicitud de repuestos',
            [$item],
        );
    }

    private function makeHandler(?Request $request = null): FindRequestByIdHandler
    {
        $repo = $this->createMock(RequestRepositoryInterface::class);
        $repo->method('findByIdWithItems')->willReturn($request);

        return new FindRequestByIdHandler($repo);
    }

    public function testReturnsRequestResponseWhenFound(): void
    {
        $handler = $this->makeHandler($this->makeRequest());

        $response = ($handler)(self::REQUEST_ID);

        self::assertInstanceOf(RequestResponse::class, $response);
        self::assertSame(self::REQUEST_ID, $response->id);
        self::assertSame('REQ-001', $response->reference);
        self::assertSame(self::REQUESTED_BY, $response->requestedBy);
        self::assertSame(self::PRODUCT_ID, $response->productId);
        self::assertNull($response->serializedStockId);
        self::assertSame(10, $response->quantity);
        self::assertSame(self::SOURCE_LOC, $response->sourceLocationId);
        self::assertSame(self::DEST_LOC, $response->destinationLocationId);
        self::assertSame(RequestStatus::PENDING->value, $response->status);
        self::assertSame('Pendiente', $response->statusLabel);
        self::assertSame('Solicitud de repuestos', $response->notes);
        self::assertNull($response->approvedBy);
        self::assertNull($response->approvedAt);
        self::assertNull($response->rejectedBy);
        self::assertNull($response->rejectedAt);
        self::assertNull($response->rejectionReason);
        self::assertNull($response->fulfilledMovementId);
        self::assertNull($response->cancelledBy);
        self::assertNotNull($response->createdAt);
    }

    public function testThrowsExceptionWhenNotFound(): void
    {
        $handler = $this->makeHandler(null);

        $this->expectException(RequestNotFoundException::class);
        $this->expectExceptionMessage(sprintf('Solicitud con ID "%s" no encontrada.', self::REQUEST_ID));

        ($handler)(self::REQUEST_ID);
    }
}
