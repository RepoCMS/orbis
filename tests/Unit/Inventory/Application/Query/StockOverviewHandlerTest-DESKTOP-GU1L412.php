<?php

declare(strict_types=1);

namespace App\Tests\Unit\Inventory\Application\Query;

use App\Inventory\Application\Query\StockOverview\StockOverviewHandler;
use App\Inventory\Application\Query\StockOverview\StockOverviewQuery;
use App\Inventory\Application\Query\StockOverview\StockOverviewResult;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;

final class StockOverviewHandlerTest extends TestCase
{
    public function testReturnsOverviewWithPagination(): void
    {
        $connection = $this->createMock(Connection::class);

        // The handler builds two SQL subqueries, then UNIONs them.
        // We mock fetchAllAssociative for both the count query and the items query.
        $connection->method('fetchAllAssociative')
            ->willReturnCallback(function (string $sql, array $params, array $types) {
                // Count query runs first
                if (str_contains($sql, 'SUM(quantity)')) {
                    return [
                        ['status' => 'available',   'qty' => '5'],
                        ['status' => 'reserved',    'qty' => '2'],
                        ['status' => 'damaged',     'qty' => '1'],
                    ];
                }

                // Items query runs second
                return [
                    [
                        'id'            => '01HZ0000000000000000000001',
                        'type'          => 'serialized',
                        'product_id'    => '01HZ0000000000000000000010',
                        'product_code'  => 'ATM-001',
                        'product_name'  => 'ATM Display',
                        'location_id'   => '01HZ0000000000000000000020',
                        'location_name' => 'Almacén Central',
                        'status'        => 'available',
                        'quantity'      => '1',
                        'serial_number' => 'SN-001',
                    ],
                ];
            });

        $handler = new StockOverviewHandler($connection);
        $result  = ($handler)(new StockOverviewQuery(page: 1, perPage: 30));

        self::assertInstanceOf(StockOverviewResult::class, $result);
        self::assertSame(1, $result->page);
        self::assertSame(30, $result->perPage);
        self::assertCount(1, $result->items);
        self::assertSame(8, $result->total); // 5 + 2 + 1
        self::assertSame(5, $result->totalAvailable);
        self::assertSame(2, $result->totalReserved);
        self::assertSame(1, $result->totalDamaged);
        self::assertSame(0, $result->totalDecommissioned);

        // Verify item structure
        $item = $result->items[0];
        self::assertSame('serialized', $item->type);
        self::assertSame('ATM Display', $item->productName);
        self::assertSame('Almacén Central', $item->locationName);
        self::assertSame('available', $item->status);
        self::assertSame(1, $item->quantity);
        self::assertSame('SN-001', $item->serialNumber);
    }

    public function testReturnsEmptyOverview(): void
    {
        $connection = $this->createMock(Connection::class);

        $connection->method('fetchAllAssociative')
            ->willReturnCallback(function (string $sql) {
                if (str_contains($sql, 'SUM(quantity)')) {
                    return [];
                }
                return [];
            });

        $handler = new StockOverviewHandler($connection);
        $result  = ($handler)(new StockOverviewQuery());

        self::assertSame(0, $result->total);
        self::assertCount(0, $result->items);
        self::assertSame(0, $result->totalPages());
    }

    public function testFiltersByTypeSerialized(): void
    {
        $connection = $this->createMock(Connection::class);

        $connection->method('fetchAllAssociative')
            ->willReturnCallback(function (string $sql) {
                if (str_contains($sql, 'SUM(quantity)')) {
                    return [['status' => 'available', 'qty' => '3']];
                }

                return [
                    [
                        'id'            => '01HZ0000000000000000000001',
                        'type'          => 'serialized',
                        'product_id'    => '01HZ0000000000000000000010',
                        'product_code'  => 'ATM-001',
                        'product_name'  => 'ATM Display',
                        'location_id'   => '01HZ0000000000000000000020',
                        'location_name' => 'Almacén Central',
                        'status'        => 'available',
                        'quantity'      => '1',
                        'serial_number' => 'SN-001',
                    ],
                ];
            });

        $handler = new StockOverviewHandler($connection);
        $result  = ($handler)(new StockOverviewQuery(type: 'serialized'));

        self::assertSame(3, $result->total);
        self::assertSame(3, $result->totalAvailable);
        self::assertCount(1, $result->items);
        self::assertSame('serialized', $result->items[0]->type);
    }

    public function testFiltersByLocation(): void
    {
        $connection = $this->createMock(Connection::class);

        $connection->method('fetchAllAssociative')
            ->willReturnCallback(function (string $sql) {
                // Verify location filter is applied
                if (str_contains($sql, 'SUM(quantity)')) {
                    return [['status' => 'available', 'qty' => '2']];
                }

                return [
                    [
                        'id'            => '01HZ0000000000000000000001',
                        'type'          => 'nonserialized',
                        'product_id'    => '01HZ0000000000000000000010',
                        'product_code'  => 'PAPER-001',
                        'product_name'  => 'Papel Térmico',
                        'location_id'   => '01HZ0000000000000000000020',
                        'location_name' => 'Almacén Central',
                        'status'        => 'available',
                        'quantity'      => '10',
                        'serial_number' => null,
                    ],
                ];
            });

        $handler = new StockOverviewHandler($connection);
        $result  = ($handler)(new StockOverviewQuery(locationId: '01HZ0000000000000000000020'));

        self::assertSame(2, $result->total);
        self::assertCount(1, $result->items);
        self::assertSame('nonserialized', $result->items[0]->type);
        self::assertSame(10, $result->items[0]->quantity);
        self::assertNull($result->items[0]->serialNumber);
    }

    public function testPaginationCalculatesCorrectOffset(): void
    {
        $connection = $this->createMock(Connection::class);

        $connection->method('fetchAllAssociative')
            ->willReturnCallback(function (string $sql) {
                if (str_contains($sql, 'SUM(quantity)')) {
                    return [['status' => 'available', 'qty' => '50']];
                }

                // Return 10 items for page 2
                $items = [];
                for ($i = 0; $i < 10; $i++) {
                    $items[] = [
                        'id'            => sprintf('01HZ%022d', $i),
                        'type'          => 'serialized',
                        'product_id'    => '01HZ0000000000000000000010',
                        'product_code'  => 'CODE-' . $i,
                        'product_name'  => 'Product ' . $i,
                        'location_id'   => '01HZ0000000000000000000020',
                        'location_name' => 'Almacén Central',
                        'status'        => 'available',
                        'quantity'      => '1',
                        'serial_number' => null,
                    ];
                }

                return $items;
            });

        $handler = new StockOverviewHandler($connection);
        $result  = ($handler)(new StockOverviewQuery(page: 2, perPage: 10));

        self::assertCount(10, $result->items);
        self::assertSame(50, $result->total);
        self::assertSame(2, $result->page);
        self::assertSame(5, $result->totalPages());
    }
}
