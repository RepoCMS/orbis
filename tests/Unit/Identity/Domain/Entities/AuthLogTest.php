<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Domain\Entities;

use App\Identity\Domain\Enums\AuthEvent;
use App\Identity\Domain\ValueObjects\AuthLogIdValueObject;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use PHPUnit\Framework\TestCase;

final class AuthLogTest extends TestCase
{
    public function testRecordStoresAllFields(): void
    {
        $log = \App\Identity\Domain\Entities\AuthLog::record(
            new AuthLogIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV'),
            new UserIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FA1'),
            AuthEvent::LOGIN_SUCCESS,
            '192.168.1.1',
            'Mozilla/5.0',
        );

        self::assertSame('01ARZ3NDEKTSV4RRFFQ69G5FAV', (string) $log->id());
        self::assertSame('01ARZ3NDEKTSV4RRFFQ69G5FA1', (string) $log->userId());
        self::assertSame(AuthEvent::LOGIN_SUCCESS, $log->event());
        self::assertSame('192.168.1.1', $log->ipAddress());
        self::assertSame('Mozilla/5.0', $log->userAgent());
        self::assertNotNull($log->occurredAt());
    }

    public function testRecordWithDifferentEventTypes(): void
    {
        $log = \App\Identity\Domain\Entities\AuthLog::record(
            new AuthLogIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV'),
            new UserIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FA1'),
            AuthEvent::LOGIN_FAILED,
            '10.0.0.1',
            'curl/7.68',
        );

        self::assertSame(AuthEvent::LOGIN_FAILED, $log->event());
    }
}
