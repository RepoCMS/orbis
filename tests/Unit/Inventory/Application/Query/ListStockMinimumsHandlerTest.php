<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Query;

use App\Inventory\Application\Query\ListStockMinimums\ListStockMinimumsHandler;
use App\Inventory\Application\Query\ListStockMinimums\ListStockMinimumsQuery;
use App\Inventory\Application\Query\Response\StockMinimumResponse;
use App\Inventory\Domain\Entities\StockMinimum;
use App\Inventory\Domain\Repositories\StockMinimumRepositoryInterface;
use App\Inventory\Domain\ValueObjects\LocationIdValueObject;
use App\Inventory\Domain\ValueObjects\ProductIdValueObject;
use App\Inventory\Domain\ValueObjects\StockMinimumIdValueObject;
use App\Shared\Application\Query\PaginatedResult;
use PHPUnit\Framework\TestCase;

final class ListStockMinimumsHandlerTest extends TestCase
{
    private function makeMinimum(string $id, int $ideal = 10, int $threshold = 3): StockMinimum
    {
        return StockMinimum::define(
            id:             new StockMinimumIdValueObject($id),
            productId:      new ProductIdValueObject('01HZ0000000000000000000002'),
            locationId:     new LocationIdValueObject('01HZ0000000000000000000003'),
            idealQuantity:  $ideal,
            alertThreshold: $threshold,
        );
    }

    // ─── Returns all when no locationId filter ────────────────────────────────

    public function testReturnsAllMinimumsWithoutFilter(): void
    {
        $minimums = [
            $this->makeMinimum('01HZ0000000000000000000010'),
            $this->makeMinimum('01HZ0000000000000000000011'),
            $this->makeMinimum('01HZ0000000000000000000012'),
        ];

        $repo = $this->createMock(StockMinimumRepositoryInterface::class);
        $repo->method('findAll')->willReturn($minimums);
        $repo->expects($this->never())->method('findByLocation');

        $handler = new ListStockMinimumsHandler($repo);
        $result  = ($handler)(new ListStockMinimumsQuery(page: 1, perPage: 20));

        self::assertInstanceOf(PaginatedResult::class, $result);
        self::assertCount(3, $result->items);
        self::assertSame(3, $result->total);
        self::assertSame(1, $result->page);
        self::assertContainsOnlyInstancesOf(StockMinimumResponse::class, $result->items);
    }

    // ─── Filters by locationId ────────────────────────────────────────────────

    public function testFiltersByLocationId(): void
    {
        $minimums = [$this->makeMinimum('01HZ0000000000000000000010')];

        $repo = $this->createMock(StockMinimumRepositoryInterface::class);
        $repo->method('findByLocation')->willReturn($minimums);
        $repo->expects($this->never())->method('findAll');

        $handler = new ListStockMinimumsHandler($repo);
        $result  = ($handler)(new ListStockMinimumsQuery(
            locationId: '01HZ0000000000000000000003',
            page:       1,
            perPage:    20,
        ));

        self::assertCount(1, $result->items);
        self::assertSame(1, $result->total);
    }

    // ─── Pagination ───────────────────────────────────────────────────────────

    public function testPaginatesResults(): void
    {
        $minimums = array_map(
            fn (int $i) => $this->makeMinimum(sprintf('01HZ%022d', $i)),
            range(1, 25),
        );

        $repo = $this->createMock(StockMinimumRepositoryInterface::class);
        $repo->method('findAll')->willReturn($minimums);

        $handler = new ListStockMinimumsHandler($repo);
        $result  = ($handler)(new ListStockMinimumsQuery(page: 2, perPage: 10));

        self::assertCount(10, $result->items);
        self::assertSame(25, $result->total);
        self::assertSame(2, $result->page);
        self::assertSame(3, $result->totalPages());
        self::assertTrue($result->hasPreviousPage());
        self::assertTrue($result->hasNextPage());
    }

    public function testLastPageHasRemainder(): void
    {
        $minimums = array_map(
            fn (int $i) => $this->makeMinimum(sprintf('01HZ%022d', $i)),
            range(1, 25),
        );

        $repo = $this->createMock(StockMinimumRepositoryInterface::class);
        $repo->method('findAll')->willReturn($minimums);

        $handler = new ListStockMinimumsHandler($repo);
        $result  = ($handler)(new ListStockMinimumsQuery(page: 3, perPage: 10));

        self::assertCount(5, $result->items);
        self::assertFalse($result->hasNextPage());
    }

    // ─── Empty result ─────────────────────────────────────────────────────────

    public function testReturnsEmptyResultWhenNoMinimums(): void
    {
        $repo = $this->createMock(StockMinimumRepositoryInterface::class);
        $repo->method('findAll')->willReturn([]);

        $handler = new ListStockMinimumsHandler($repo);
        $result  = ($handler)(new ListStockMinimumsQuery());

        self::assertCount(0, $result->items);
        self::assertSame(0, $result->total);
        self::assertSame(1, $result->totalPages());
    }
}
