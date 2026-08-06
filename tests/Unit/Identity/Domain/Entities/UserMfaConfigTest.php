<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Domain\Entities;

use App\Identity\Domain\Entities\UserMfaConfig;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Identity\Domain\ValueObjects\UserMfaConfigIdValueObject;
use PHPUnit\Framework\TestCase;

final class UserMfaConfigTest extends TestCase
{
    private function createConfig(): UserMfaConfig
    {
        return UserMfaConfig::setup(
            new UserMfaConfigIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FAV'),
            new UserIdValueObject('01ARZ3NDEKTSV4RRFFQ69G5FA1'),
            'JBSWY3DPEHPK3PXP',
        );
    }

    public function testSetupCreatesDisabledConfig(): void
    {
        $config = $this->createConfig();

        self::assertFalse($config->isEnabled());
        self::assertNull($config->confirmedAt());
        self::assertSame('01ARZ3NDEKTSV4RRFFQ69G5FAV', (string) $config->id());
        self::assertSame('01ARZ3NDEKTSV4RRFFQ69G5FA1', (string) $config->userId());
        self::assertSame('JBSWY3DPEHPK3PXP', $config->secret());
        self::assertNotNull($config->createdAt());
    }

    public function testConfirmEnablesMfa(): void
    {
        $config = $this->createConfig();
        $config->confirm();

        self::assertTrue($config->isEnabled());
        self::assertNotNull($config->confirmedAt());
    }

    public function testConfirmThrowsWhenAlreadyEnabled(): void
    {
        $config = $this->createConfig();
        $config->confirm();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('MFA ya está habilitado');
        $config->confirm();
    }

    public function testDisableResetsToPending(): void
    {
        $config = $this->createConfig();
        $config->confirm();
        $config->disable();

        self::assertFalse($config->isEnabled());
        self::assertNull($config->confirmedAt());
    }

    public function testUpdateSecretResetsToPending(): void
    {
        $config = $this->createConfig();
        $config->confirm();
        $config->updateSecret('NEWSECRET12345678');

        self::assertFalse($config->isEnabled());
        self::assertNull($config->confirmedAt());
        self::assertSame('NEWSECRET12345678', $config->secret());
    }
}
