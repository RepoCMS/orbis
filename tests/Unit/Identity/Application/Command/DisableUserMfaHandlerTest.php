<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Application\Command;

use App\Identity\Application\Command\DisableUserMfa\DisableUserMfaCommand;
use App\Identity\Application\Command\DisableUserMfa\DisableUserMfaHandler;
use App\Identity\Domain\Entities\UserMfaConfig;
use App\Identity\Domain\Repositories\UserMfaConfigRepositoryInterface;
use App\Identity\Domain\ValueObjects\UserMfaConfigIdValueObject;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use PHPUnit\Framework\TestCase;

final class DisableUserMfaHandlerTest extends TestCase
{
    private const USER_ID = '01ARZ3NDEKTSV4RRFFQ69G5FAV';

    public function testDisablesMfaSuccessfully(): void
    {
        $config = UserMfaConfig::setup(
            new UserMfaConfigIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FA1'),
            new UserIdValueObject(self::USER_ID),
            'JBSWY3DPEHPK3PXP',
        );
        $config->confirm();

        $repo = $this->createMock(UserMfaConfigRepositoryInterface::class);
        $repo->method('findByUserId')->willReturn($config);
        $repo->expects($this->once())->method('deleteByUserId');

        $handler = new DisableUserMfaHandler($repo);
        ($handler)(new DisableUserMfaCommand(self::USER_ID));

        self::assertTrue(true);
    }

    public function testIsIdempotentWhenNoConfig(): void
    {
        $repo = $this->createMock(UserMfaConfigRepositoryInterface::class);
        $repo->method('findByUserId')->willReturn(null);
        $repo->expects($this->never())->method('deleteByUserId');

        $handler = new DisableUserMfaHandler($repo);
        ($handler)(new DisableUserMfaCommand(self::USER_ID));

        self::assertTrue(true);
    }

    public function testIsIdempotentWhenAlreadyDisabled(): void
    {
        $config = UserMfaConfig::setup(
            new UserMfaConfigIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FA1'),
            new UserIdValueObject(self::USER_ID),
            'JBSWY3DPEHPK3PXP',
        );

        $repo = $this->createMock(UserMfaConfigRepositoryInterface::class);
        $repo->method('findByUserId')->willReturn($config);
        $repo->expects($this->never())->method('deleteByUserId');

        $handler = new DisableUserMfaHandler($repo);
        ($handler)(new DisableUserMfaCommand(self::USER_ID));

        self::assertTrue(true);
    }
}
