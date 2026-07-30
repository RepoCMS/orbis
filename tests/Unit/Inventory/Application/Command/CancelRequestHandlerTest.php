<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Command;

use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Inventory\Application\Command\CancelRequest\CancelRequestCommand;
use App\Inventory\Application\Command\CancelRequest\CancelRequestHandler;
use App\Inventory\Application\Exception\RequestNotFoundException;
use App\Inventory\Domain\Entities\Request;
use App\Inventory\Domain\Entities\RequestItem;
use App\Inventory\Domain\Enums\RequestStatus;
use App\Inventory\Domain\Repositories\RequestRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\RequestIdValueObject;
use App\Inventory\Domain\ValueObjects\RequestItemIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use PHPUnit\Framework\TestCase;

final class CancelRequestHandlerTest extends TestCase
{
    private const REQUEST_ID    = '01HZ0000000000000000000001';
    private const CANCELLED_BY  = '01HZ0000000000000000000002';
    private const PRODUCT_ID    = '01HZ0000000000000000000003';
    private const SOURCE_LOC    = '01HZ0000000000000000000004';
    private const DEST_LOC      = '01HZ0000000000000000000005';
    private const REQUESTED_BY  = '01HZ0000000000000000000006';

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

    public function testCancelsRequest(): void
    {
        $request = $this->makePendingRequest();

        $requestRepo = $this->createMock(RequestRepositoryInterface::class);
        $requestRepo->method('findById')->willReturn($request);
        $requestRepo->expects($this->once())->method('save')->with($request);

        $handler = new CancelRequestHandler($requestRepo, $this->createMock(DomainEventPublisherInterface::class));
        ($handler)(new CancelRequestCommand(
            requestId:   self::REQUEST_ID,
            cancelledBy: self::CANCELLED_BY,
        ));

        self::assertSame(RequestStatus::CANCELLED, $request->status());
        self::assertSame(self::CANCELLED_BY, (string) $request->cancelledBy());
    }

    public function testThrowsWhenRequestNotFound(): void
    {
        $requestRepo = $this->createMock(RequestRepositoryInterface::class);
        $requestRepo->method('findById')->willReturn(null);
        $requestRepo->expects($this->never())->method('save');

        $handler = new CancelRequestHandler($requestRepo, $this->createMock(DomainEventPublisherInterface::class));

        $this->expectException(RequestNotFoundException::class);
        $this->expectExceptionMessage(self::REQUEST_ID);

        ($handler)(new CancelRequestCommand(
            requestId:   self::REQUEST_ID,
            cancelledBy: self::CANCELLED_BY,
        ));
    }

    public function testCancelAlreadyCancelledThrowsException(): void
    {
        $request = $this->makePendingRequest();
        $request->cancel(new UserIdValueObject(self::CANCELLED_BY));

        $requestRepo = $this->createMock(RequestRepositoryInterface::class);
        $requestRepo->method('findById')->willReturn($request);
        $requestRepo->expects($this->never())->method('save');

        $handler = new CancelRequestHandler($requestRepo, $this->createMock(DomainEventPublisherInterface::class));

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Solo se pueden cancelar solicitudes en estado PENDING');

        ($handler)(new CancelRequestCommand(
            requestId:   self::REQUEST_ID,
            cancelledBy: self::CANCELLED_BY,
        ));
    }

    public function testCancelApprovedRequestThrowsException(): void
    {
        $request = $this->makePendingRequest();
        $request->approve(new UserIdValueObject(self::CANCELLED_BY));

        $requestRepo = $this->createMock(RequestRepositoryInterface::class);
        $requestRepo->method('findById')->willReturn($request);
        $requestRepo->expects($this->never())->method('save');

        $handler = new CancelRequestHandler($requestRepo, $this->createMock(DomainEventPublisherInterface::class));

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Solo se pueden cancelar solicitudes en estado PENDING');

        ($handler)(new CancelRequestCommand(
            requestId:   self::REQUEST_ID,
            cancelledBy: self::CANCELLED_BY,
        ));
    }
}
