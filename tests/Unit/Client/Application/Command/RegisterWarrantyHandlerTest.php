<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Application\Command;

use App\Client\Application\Command\RegisterWarranty\RegisterWarrantyCommand;
use App\Client\Application\Command\RegisterWarranty\RegisterWarrantyHandler;
use App\Client\Application\Exception\TerminalNotFoundException;
use App\Client\Domain\Entities\Terminal;
use App\Client\Domain\Enums\TerminalFunctionality;
use App\Client\Domain\Enums\TerminalStatus;
use App\Client\Domain\Enums\TerminalType;
use App\Client\Domain\Enums\TerminalVendor;
use App\Client\Domain\Repositories\TerminalRepositoryInterface;
use App\Client\Domain\Repositories\TerminalWarrantyRepositoryInterface;
use App\Client\Domain\ValueObjects\TerminalIdValueObject;
use App\Client\Domain\ValueObjects\SerialNumberValueObject;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Shared\Domain\Event\DomainEventPublisherInterface;
use App\Shared\Domain\Service\IdGeneratorInterface;
use PHPUnit\Framework\TestCase;

final class RegisterWarrantyHandlerTest extends TestCase
{
    private const TERMINAL_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    private const GENERATED_WARRANTY_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAW';
    private const CLIENT_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAX';

    private function makeTerminal(): Terminal
    {
        return Terminal::add(
            id: new TerminalIdValueObject(self::TERMINAL_ID),
            clientId: new ClientIdValueObject(self::CLIENT_ID),
            serial: new SerialNumberValueObject('ATM0000001'),
            vendor: TerminalVendor::Hyosung,
            funcionality: TerminalFunctionality::Dispenser,
            type: TerminalType::Lobby,
            status: TerminalStatus::ACTIVE,
        );
    }

    public function testRegistersWarrantySuccessfully(): void
    {
        $terminalRepo = $this->createMock(TerminalRepositoryInterface::class);
        $terminalRepo->method('findById')->willReturn($this->makeTerminal());

        $warrantyRepo = $this->createMock(TerminalWarrantyRepositoryInterface::class);
        $saved = null;
        $warrantyRepo->expects($this->once())
            ->method('save')
            ->willReturnCallback(function ($w) use (&$saved): void {
                $saved = $w;
            });

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::GENERATED_WARRANTY_ID);

        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);
        $eventPublisher->expects($this->once())->method('publish');

        $handler = new RegisterWarrantyHandler($terminalRepo, $warrantyRepo, $idGenerator, $eventPublisher);
        ($handler)(new RegisterWarrantyCommand(
            terminalId: self::TERMINAL_ID,
            type: 'manufacturer',
            startDate: '2026-01-01',
            endDate: '2027-01-01',
            terms: 'Standard warranty terms',
        ));

        self::assertNotNull($saved);
        self::assertSame(self::GENERATED_WARRANTY_ID, (string) $saved->id());
        self::assertSame(self::TERMINAL_ID, (string) $saved->terminalId());
    }

    public function testRegistersWarrantyWithoutTerms(): void
    {
        $terminalRepo = $this->createMock(TerminalRepositoryInterface::class);
        $terminalRepo->method('findById')->willReturn($this->makeTerminal());

        $warrantyRepo = $this->createMock(TerminalWarrantyRepositoryInterface::class);
        $warrantyRepo->expects($this->once())->method('save');

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::GENERATED_WARRANTY_ID);

        $eventPublisher = $this->createMock(DomainEventPublisherInterface::class);
        $eventPublisher->expects($this->once())->method('publish');

        $handler = new RegisterWarrantyHandler($terminalRepo, $warrantyRepo, $idGenerator, $eventPublisher);
        ($handler)(new RegisterWarrantyCommand(
            terminalId: self::TERMINAL_ID,
            type: 'extended',
            startDate: '2026-06-01',
            endDate: '2028-06-01',
        ));
    }

    public function testThrowsWhenTerminalNotFound(): void
    {
        $terminalRepo = $this->createMock(TerminalRepositoryInterface::class);
        $terminalRepo->method('findById')->willReturn(null);

        $handler = new RegisterWarrantyHandler(
            $terminalRepo,
            $this->createMock(TerminalWarrantyRepositoryInterface::class),
            $this->createMock(IdGeneratorInterface::class),
            $this->createMock(DomainEventPublisherInterface::class),
        );

        $this->expectException(TerminalNotFoundException::class);
        $this->expectExceptionMessage('Terminal con ID "' . self::TERMINAL_ID . '" no encontrado.');

        ($handler)(new RegisterWarrantyCommand(
            terminalId: self::TERMINAL_ID,
            type: 'manufacturer',
            startDate: '2026-01-01',
            endDate: '2027-01-01',
        ));
    }

    public function testThrowsOnInvalidWarrantyType(): void
    {
        $terminalRepo = $this->createMock(TerminalRepositoryInterface::class);
        $terminalRepo->method('findById')->willReturn($this->makeTerminal());

        $idGenerator = $this->createMock(IdGeneratorInterface::class);
        $idGenerator->method('generate')->willReturn(self::GENERATED_WARRANTY_ID);

        $handler = new RegisterWarrantyHandler(
            $terminalRepo,
            $this->createMock(TerminalWarrantyRepositoryInterface::class),
            $idGenerator,
            $this->createMock(DomainEventPublisherInterface::class),
        );

        $this->expectException(\ValueError::class);

        ($handler)(new RegisterWarrantyCommand(
            terminalId: self::TERMINAL_ID,
            type: 'invalid_type',
            startDate: '2026-01-01',
            endDate: '2027-01-01',
        ));
    }
}
