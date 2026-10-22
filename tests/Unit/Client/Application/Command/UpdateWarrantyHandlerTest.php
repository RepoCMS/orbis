<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Application\Command;

use App\Client\Application\Command\UpdateWarranty\UpdateWarrantyCommand;
use App\Client\Application\Command\UpdateWarranty\UpdateWarrantyHandler;
use App\Client\Application\Exception\TerminalWarrantyNotFoundException;
use App\Client\Domain\Entities\Terminal;
use App\Client\Domain\Entities\TerminalWarranty;
use App\Client\Domain\Enums\TerminalFunctionality;
use App\Client\Domain\Enums\TerminalStatus;
use App\Client\Domain\Enums\TerminalType;
use App\Client\Domain\Enums\TerminalVendor;
use App\Client\Domain\Enums\WarrantyType;
use App\Client\Domain\Events\WarrantyUpdated;
use App\Client\Domain\Repositories\TerminalRepositoryInterface;
use App\Client\Domain\Repositories\TerminalWarrantyRepositoryInterface;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Client\Domain\ValueObjects\SerialNumberValueObject;
use App\Client\Domain\ValueObjects\TerminalIdValueObject;
use App\Client\Domain\ValueObjects\TerminalWarrantyIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class UpdateWarrantyHandlerTest extends TestCase
{
    private const WARRANTY_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    private const TERMINAL_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAW';

    public function testUpdatesWarrantySuccessfully(): void
    {
        $warranty = TerminalWarranty::register(
            id: new TerminalWarrantyIdValueObject(self::WARRANTY_ID),
            terminalId: new TerminalIdValueObject(self::TERMINAL_ID),
            type: WarrantyType::MANUFACTURER,
            startDate: new DateTimeImmutable('2026-01-01'),
            endDate: new DateTimeImmutable('2027-01-01'),
        );

        $repo = $this->createMock(TerminalWarrantyRepositoryInterface::class);
        $repo->method('findById')->willReturn($warranty);
        $repo->expects($this->once())->method('save');

        $terminal = Terminal::add(
            id: new TerminalIdValueObject(self::TERMINAL_ID),
            clientId: new ClientIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAY'),
            serial: new SerialNumberValueObject('ATM0000001'),
            vendor: TerminalVendor::Hyosung,
            funcionality: TerminalFunctionality::Dispenser,
            type: TerminalType::Lobby,
            status: TerminalStatus::ACTIVE,
        );

        $terminalRepo = $this->createMock(TerminalRepositoryInterface::class);
        $terminalRepo->method('findById')->willReturn($terminal);

        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);
        $eventPublisher->expects($this->once())->method('publish')
            ->with($this->isInstanceOf(WarrantyUpdated::class));

        $handler = new UpdateWarrantyHandler($repo, $terminalRepo, $eventPublisher);
        ($handler)(new UpdateWarrantyCommand(
            warrantyId: self::WARRANTY_ID,
            type: 'extended',
            endDate: '2028-01-01',
            terms: 'Updated terms',
        ));
    }

    public function testThrowsWhenNotFound(): void
    {
        $repo = $this->createMock(TerminalWarrantyRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $terminalRepo = $this->createMock(TerminalRepositoryInterface::class);

        $handler = new UpdateWarrantyHandler(
            $repo,
            $terminalRepo,
            $this->createMock(DomainEventPublisherInterface::class),
        );

        $this->expectException(TerminalWarrantyNotFoundException::class);
        ($handler)(new UpdateWarrantyCommand(
            warrantyId: self::WARRANTY_ID,
            endDate: '2028-01-01',
        ));
    }
}
