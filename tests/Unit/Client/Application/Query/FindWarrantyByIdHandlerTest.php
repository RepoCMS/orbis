<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Application\Query;

use App\Client\Application\Exception\TerminalWarrantyNotFoundException;
use App\Client\Application\Query\FindWarrantyById\FindWarrantyByIdHandler;
use App\Client\Application\Query\FindWarrantyById\FindWarrantyByIdQuery;
use App\Client\Application\Query\Response\WarrantyResponse;
use App\Client\Domain\Entities\TerminalWarranty;
use App\Client\Domain\Enums\WarrantyType;
use App\Client\Domain\Repositories\TerminalWarrantyRepositoryInterface;
use App\Client\Domain\ValueObjects\TerminalIdValueObject;
use App\Client\Domain\ValueObjects\TerminalWarrantyIdValueObject;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class FindWarrantyByIdHandlerTest extends TestCase
{
    private const WARRANTY_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
    private const TERMINAL_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAW';

    public function testReturnsWarrantyResponse(): void
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

        $handler = new FindWarrantyByIdHandler($repo);
        $response = ($handler)(new FindWarrantyByIdQuery(warrantyId: self::WARRANTY_ID));

        self::assertInstanceOf(WarrantyResponse::class, $response);
        self::assertSame(self::WARRANTY_ID, $response->id);
    }

    public function testThrowsWhenNotFound(): void
    {
        $repo = $this->createMock(TerminalWarrantyRepositoryInterface::class);
        $repo->method('findById')->willReturn(null);

        $handler = new FindWarrantyByIdHandler($repo);

        $this->expectException(TerminalWarrantyNotFoundException::class);
        ($handler)(new FindWarrantyByIdQuery(warrantyId: self::WARRANTY_ID));
    }
}
