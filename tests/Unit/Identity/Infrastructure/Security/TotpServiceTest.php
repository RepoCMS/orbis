<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Infrastructure\Security;

use App\Identity\Infrastructure\Security\TotpService;
use OTPHP\TOTP;
use PHPUnit\Framework\TestCase;

final class TotpServiceTest extends TestCase
{
    public function testGenerateSecretReturnsBase32String(): void
    {
        $service = new TotpService();
        $secret = $service->generateSecret();

        self::assertGreaterThanOrEqual(32, strlen($secret));
        self::assertMatchesRegularExpression('/^[A-Z2-7]+=*$/', $secret);
    }

    public function testGetQrCodeUriReturnsValidOtpauthUri(): void
    {
        $service = new TotpService();
        $uri = $service->getQrCodeUri('testuser', 'JBSWY3DPEHPK3PXP');

        self::assertStringStartsWith('otpauth://', $uri);
        self::assertStringContainsString('testuser', $uri);
        self::assertStringContainsString('Orbis%20CMS', $uri);
    }

    public function testVerifyAcceptsValidCode(): void
    {
        $service = new TotpService();
        $secret = $service->generateSecret();

        $totp = TOTP::createFromSecret($secret);
        $totp->setDigits(6);
        $totp->setPeriod(30);
        $code = $totp->now();

        self::assertTrue($service->verify($secret, $code));
    }
}
