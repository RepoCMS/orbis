<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Application\Query;

use App\Identity\Application\Query\ListAuthLog\ListAuthLogHandler;
use App\Identity\Application\Query\ListAuthLog\ListAuthLogQuery;
use App\Identity\Domain\Entities\AuthLog;
use App\Identity\Domain\Enums\AuthEvent;
use App\Identity\Domain\Repositories\AuthLogRepositoryInterface;
use App\Identity\Domain\ValueObjects\AuthLogIdValueObject;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use PHPUnit\Framework\TestCase;

final class ListAuthLogHandlerTest extends TestCase
{
    private const USER_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';

    public function testListsAuthLogsWithFilters(): void
    {
        $authLog = AuthLog::record(
            AuthLogIdValueObject::fromString('01ARZ3NDEKTSV4RRFFQ69G5FA1'),
            new UserIdValueObject(self::USER_ID),
            AuthEvent::LOGIN_SUCCESS,
            '192.168.1.1',
            'Mozilla/5.0',
        );

        $repo = $this->createMock(AuthLogRepositoryInterface::class);
        $repo->method('findByFilters')->willReturn([$authLog]);
        $repo->method('countByFilters')->willReturn(1);

        $handler = new ListAuthLogHandler($repo);
        $result = ($handler)(new ListAuthLogQuery(
            userId: self::USER_ID,
            dateFrom: '2024-01-01',
            dateTo: '2024-12-31',
            page: 1,
            limit: 20,
        ));

        self::assertCount(1, $result->items);
        self::assertSame(1, $result->total);
        self::assertSame(1, $result->page);
        self::assertSame(20, $result->limit);
    }

    public function testReturnsEmptyWhenNoLogs(): void
    {
        $repo = $this->createMock(AuthLogRepositoryInterface::class);
        $repo->method('findByFilters')->willReturn([]);
        $repo->method('countByFilters')->willReturn(0);

        $handler = new ListAuthLogHandler($repo);
        $result = ($handler)(new ListAuthLogQuery());

        self::assertEmpty($result->items);
        self::assertSame(0, $result->total);
    }
}
