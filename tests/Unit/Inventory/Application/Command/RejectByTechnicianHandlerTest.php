<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Command;

use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Inventory\Application\Command\RejectByTechnician\RejectByTechnicianCommand;
use App\Inventory\Application\Command\RejectByTechnician\RejectByTechnicianHandler;
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

final class RejectByTechnicianHandlerTest extends TestCase
{
    private const REQUEST_ID  = '01HZ0000000000000000000001';

    public function testRejectsRequest(): void
    {
        $item = RequestItem::create(
            id: new RequestItemIdValueObject('01HZ0000000000000000000008'),
            productId: ProductIdValueObject::fromString('01HZ0000000000000000000002'),
            serializedStockId: null,
            quantity: 5,
        );

        $request = Request::submit(
            id: RequestIdValueObject::fromString(self::REQUEST_ID),
            reference: 'RQ-001',
            requestedBy: new UserIdValueObject('01HZ0000000000000000000005'),
            sourceLocationId: LocationIdValueObject::fromString('01HZ0000000000000000000003'),
            destinationLocationId: LocationIdValueObject::fromString('01HZ0000000000000000000004'),
            items: [$item],
        );

        $request->approve(new UserIdValueObject('01HZ0000000000000000000006'));
        $request->notifyTechnicianAcceptance();

        $repo = $this->createMock(RequestRepositoryInterface::class);
        $repo->method('findById')->willReturn($request);
        $repo->expects(self::once())->method('save')->with($request);

        $handler = new RejectByTechnicianHandler($repo);
        ($handler)(new RejectByTechnicianCommand(
            requestId: self::REQUEST_ID,
            rejectedBy: '01HZ0000000000000000000007',
            reason: 'No necesito este repuesto',
        ));

        self::assertSame(RequestStatus::REJECTED, $request->status());
    }

    public function testThrowsWhenRequestNotFound(): void
    {
        $repo = $this->createMock(RequestRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $handler = new RejectByTechnicianHandler($repo);

        $this->expectException(RequestNotFoundException::class);
        ($handler)(new RejectByTechnicianCommand(
            requestId: '01HZ0000000000000000000099',
            rejectedBy: '01HZ0000000000000000000006',
            reason: 'Test',
        ));
    }
}
