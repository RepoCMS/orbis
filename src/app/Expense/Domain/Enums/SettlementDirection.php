<?php

declare(strict_types=1);

namespace App\Expense\Domain\Enums;

/**
 * Dirección de la liquidación de un adelanto de efectivo.
 *
 * - TECH_TO_COMPANY: El técnico devolvió el saldo no gastado.
 * - COMPANY_TO_TECH: La empresa pagó un reembolso al técnico.
 */
enum SettlementDirection: string
{
    case TECH_TO_COMPANY = 'tech_to_company';
    case COMPANY_TO_TECH = 'company_to_tech';

    public function label(): string
    {
        return match ($this) {
            self::TECH_TO_COMPANY => 'Técnico devolvió saldo',
            self::COMPANY_TO_TECH => 'Empresa pagó reembolso',
        };
    }

    public function isTechToCompany(): bool
    {
        return $this === self::TECH_TO_COMPANY;
    }

    public function isCompanyToTech(): bool
    {
        return $this === self::COMPANY_TO_TECH;
    }
}
