<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Application\Query;

use App\Client\Application\Query\FindWarrantiesByTerminal\FindWarrantiesByTerminalHandler;
use App\Client\Application\Query\FindWarrantiesByTerminal\FindWarrantiesByTerminalQuery;
use App\Client\Application\Query\Response\WarrantyResponse;
use App\Client\Domain\Entities\TerminalWarranty;
use App\Client\Domain\Enums\WarrantyType;
use App\Client\Domain\Repositories\TerminalWarrantyRepositoryInterface;
use App\Client\Domain\ValueObjects\TerminalIdValueObject;
use App\Client\Domain\ValueObjects\TerminalWarrantyIdValueObject;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class FindWarrantiesByTerminalHandlerTest extends TestCase
{
    private const TERMINAL_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';

    public function testReturnsWarrantyResponses(): void
    {
        $warranty = TerminalWarranty::register(
            id: new TerminalWarrantyIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAW'),
            terminalId: new TerminalIdValueObject(self::TERMINAL_ID),
            type: WarrantyType::MANUFACTURER,
            startDate: new DateTimeImmutable('2026-01-01'),
            endDate: new DateTimeImmutable('2027-01-01'),
        );

        $repo = $this->createMock(TerminalWarrantyRepositoryInterface::class);
        $repo->method('findByTerminalId')->willReturn([$warranty]);

        $handler = new FindWarrantiesByTerminalHandler($repo);
        $result = ($handler)(new FindWarrantiesByTerminalQuery(terminalId: self::TERMINAL_ID));

        self::assertCount(1, $result);
        self::assertInstanceOf(WarrantyResponse::class, $result[0]);
    }

    public function testReturnsEmptyArray(): void
    {
        $repo = $this->createMock(TerminalWarrantyRepositoryInterface::class);
        $repo->method('findByTerminalId')->willReturn([]);

        $handler = new FindWarrantiesByTerminalHandler($repo);
        $result = ($handler)(new FindWarrantiesByTerminalQuery(terminalId: self::TERMINAL_ID));

        self::assertCount(0, $result);
    }
}
