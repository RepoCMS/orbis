<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Security;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use OTPHP\TOTP;

/**
 * Servicio de autenticación de dos factores via TOTP (RFC 6238).
 * Compatible con Google Authenticator, Authy, Aegis, etc.
 */
final class TotpService
{
    private const ISSUER = 'Orbis CMS';
    private const DIGITS  = 6;
    private const PERIOD  = 30;
    private const WINDOW  = 29; // leeway en segundos (debe ser < period de 30 s)

    public function generateSecret(): string
    {
        $totp = TOTP::generate();

        return $totp->getSecret();
    }

    public function getQrCodeUri(string $username, string $secret): string
    {
        $totp = TOTP::createFromSecret($secret);
        $totp->setLabel($username);
        $totp->setIssuer(self::ISSUER);
        $totp->setDigits(self::DIGITS);
        $totp->setPeriod(self::PERIOD);

        return $totp->getProvisioningUri();
    }

    /**
     * Devuelve el contenido del PNG como data URI para incrustar directamente en HTML.
     */
    public function getQrCodeDataUri(string $username, string $secret): string
    {
        $uri = $this->getQrCodeUri($username, $secret);

        $builder = new Builder(
            writer: new PngWriter(),
            data: $uri,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 240,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
        );

        $result = $builder->build();

        return $result->getDataUri();
    }

    public function verify(string $secret, string $code): bool
    {
        $totp = TOTP::createFromSecret($secret);
        $totp->setDigits(self::DIGITS);
        $totp->setPeriod(self::PERIOD);

        return $totp->verify($code, null, self::WINDOW);
    }
}
