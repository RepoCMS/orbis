<?php

declare(strict_types=1);

namespace App\Tests\Unit\Client\Domain\ValueObjects;

use App\Client\Domain\Exceptions\ClientRucException;
use App\Client\Domain\ValueObjects\ClientRucValueObject;
use PHPUnit\Framework\TestCase;

final class ClientRucValueObjectTest extends TestCase
{
    // RUC persona natural: prov 01, tipo 1, secuencia 234567, verificador 3, establecimiento 001
    // Coeficientes: [2,1,2,1,2,1,2,1,2] → suma=27 → 27%10=7 → 10-7=3 ✓
    private const NATURAL_RUC = '0112345673001';

    // RUC entidad pública: prov 01, tipo 6, secuencia 000000, verificador 0, establecimiento 000
    // Coeficientes: [3,2,7,6,5,4,3,2] → suma=44 → 44%11=0 → verifier=0 ✓
    private const PUBLIC_RUC = '0160000000000';

    // RUC sociedad privada: prov 01, tipo 9, secuencia 000000, verificador 1, establecimiento 001
    // Coeficientes: [4,3,2,7,6,5,4,3,2] → suma=21 → 21%11=10 → 11-10=1 ✓
    private const COMPANY_RUC = '0190000001001';

    public function testAcceptsNaturalPersonRuc(): void
    {
        $vo = new ClientRucValueObject(self::NATURAL_RUC);
        self::assertSame(self::NATURAL_RUC, (string) $vo);
    }

    public function testAcceptsPublicEntityRuc(): void
    {
        $vo = new ClientRucValueObject(self::PUBLIC_RUC);
        self::assertSame(self::PUBLIC_RUC, (string) $vo);
    }

    public function testAcceptsCompanyRuc(): void
    {
        $vo = new ClientRucValueObject(self::COMPANY_RUC);
        self::assertSame(self::COMPANY_RUC, (string) $vo);
    }

    public function testNormalizesTrim(): void
    {
        $vo = new ClientRucValueObject('  ' . self::NATURAL_RUC . '  ');
        self::assertSame(self::NATURAL_RUC, (string) $vo);
    }

    public function testRejectsEmpty(): void
    {
        $this->expectException(ClientRucException::class);
        new ClientRucValueObject('');
    }

    public function testRejectsNonNumeric(): void
    {
        $this->expectException(ClientRucException::class);
        new ClientRucValueObject('01A2345670003');
    }

    public function testRejectsWrongLength(): void
    {
        $this->expectException(ClientRucException::class);
        new ClientRucValueObject('011234567000');
    }

    public function testRejectsInvalidProvince(): void
    {
        $this->expectException(ClientRucException::class);
        new ClientRucValueObject('9912345670003');
    }

    public function testRejectsInvalidVerifierDigit(): void
    {
        $this->expectException(ClientRucException::class);
        new ClientRucValueObject('0112345670001');
    }

    public function testRejectsInvalidThirdDigit(): void
    {
        $this->expectException(ClientRucException::class);
        new ClientRucValueObject('0172345670003');
    }

    public function testFromStringFactory(): void
    {
        $vo = ClientRucValueObject::fromString(self::NATURAL_RUC);
        self::assertSame(self::NATURAL_RUC, (string) $vo);
    }
}
