<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Command;

use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Inventory\Application\Command\RejectRequest\RejectRequestCommand;
use App\Inventory\Application\Command\RejectRequest\RejectRequestHandler;
use App\Inventory\Application\Exception\RequestNotFoundException;
use App\Inventory\Domain\Entities\Request;
use App\Inventory\Domain\Entities\RequestItem;
use App\Inventory\Domain\Enums\RequestStatus;
use App\Inventory\Domain\Repositories\RequestRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\RequestIdValueObject;
use App\Inventory\Domain\ValueObjects\RequestItemIdValueObject;
use PHPUnit\Framework\TestCase;

final class RejectRequestHandlerTest extends TestCase
{
    private const REQUEST_ID   = '01HZ0000000000000000000001';
    private const REJECTED_BY  = '01HZ0000000000000000000002';
    private const PRODUCT_ID   = '01HZ0000000000000000000003';
    private const SOURCE_LOC   = '01HZ0000000000000000000004';
    private const DEST_LOC     = '01HZ0000000000000000000005';
    private const REQUESTED_BY = '01HZ0000000000000000000006';

    private function makePendingRequest(): Request
    {
        $item = RequestItem::create(
            id: new RequestItemIdValueObject('01HZ0000000000000000000007'),
            productId: new ProductIdValueObject(self::PRODUCT_ID),
            serializedStockId: null,
            quantity: 1,
        );

        return Request::submit(
            id:                   new RequestIdValueObject(self::REQUEST_ID),
            reference:            'SOL-2026-000001',
            requestedBy:          new UserIdValueObject(self::REQUESTED_BY),
            sourceLocationId:     new LocationIdValueObject(self::SOURCE_LOC),
            destinationLocationId: new LocationIdValueObject(self::DEST_LOC),
            notes:                null,
            items:                [$item],
        );
    }

    public function testRejectsRequest(): void
    {
        $request = $this->makePendingRequest();

        $requestRepo = $this->createMock(RequestRepositoryInterface::class);
        $requestRepo->method('findById')->willReturn($request);
        $requestRepo->expects($this->once())->method('save')->with($request);

        $handler = new RejectRequestHandler($requestRepo);
        ($handler)(new RejectRequestCommand(
            requestId:  self::REQUEST_ID,
            rejectedBy: self::REJECTED_BY,
            reason:     'Stock insuficiente en bodega.',
        ));

        self::assertSame(RequestStatus::REJECTED, $request->status());
        self::assertSame(self::REJECTED_BY, (string) $request->rejectedBy());
        self::assertSame('Stock insuficiente en bodega.', $request->rejectionReason());
        self::assertNotNull($request->rejectedAt());
    }

    public function testThrowsWhenRequestNotFound(): void
    {
        $requestRepo = $this->createMock(RequestRepositoryInterface::class);
        $requestRepo->method('findById')->willReturn(null);
        $requestRepo->expects($this->never())->method('save');

        $handler = new RejectRequestHandler($requestRepo);

        $this->expectException(RequestNotFoundException::class);
        $this->expectExceptionMessage(self::REQUEST_ID);

        ($handler)(new RejectRequestCommand(
            requestId:  self::REQUEST_ID,
            rejectedBy: self::REJECTED_BY,
            reason:     'Sin stock.',
        ));
    }

    public function testRejectAlreadyRejectedThrowsException(): void
    {
        $request = $this->makePendingRequest();
        $request->reject(new UserIdValueObject(self::REJECTED_BY), 'Primera razón');

        $requestRepo = $this->createMock(RequestRepositoryInterface::class);
        $requestRepo->method('findById')->willReturn($request);
        $requestRepo->expects($this->never())->method('save');

        $handler = new RejectRequestHandler($requestRepo);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Solo se pueden rechazar solicitudes en estado PENDING');

        ($handler)(new RejectRequestCommand(
            requestId:  self::REQUEST_ID,
            rejectedBy: self::REJECTED_BY,
            reason:     'Segunda razón.',
        ));
    }

    public function testRejectApprovedRequestThrowsException(): void
    {
        $request = $this->makePendingRequest();
        $request->approve(new UserIdValueObject(self::REJECTED_BY));

        $requestRepo = $this->createMock(RequestRepositoryInterface::class);
        $requestRepo->method('findById')->willReturn($request);
        $requestRepo->expects($this->never())->method('save');

        $handler = new RejectRequestHandler($requestRepo);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Solo se pueden rechazar solicitudes en estado PENDING');

        ($handler)(new RejectRequestCommand(
            requestId:  self::REQUEST_ID,
            rejectedBy: self::REJECTED_BY,
            reason:     'Ya aprobada.',
        ));
    }
}
