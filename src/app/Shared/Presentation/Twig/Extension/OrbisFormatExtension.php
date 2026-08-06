<?php

declare(strict_types=1);

namespace App\Shared\Presentation\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Filtros Twig para formateo de números, monedas y fechas.
 */
final class OrbisFormatExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('orbis_format_number', $this->formatNumber(...)),
            new TwigFilter('orbis_format_currency', $this->formatCurrency(...)),
            new TwigFilter('orbis_format_percent', $this->formatPercent(...)),
            new TwigFilter('orbis_format_duration', $this->formatDuration(...)),
        ];
    }

    /**
     * Formatea un número con separadores de miles.
     * Ej: 1234567 → "1,234,567"
     */
    public function formatNumber(int|float|null $value, int $decimals = 0): string
    {
        if ($value === null) {
            return '—';
        }

        return number_format($value, $decimals, '.', ',');
    }

    /**
     * Formatea un valor como moneda USD.
     * Ej: 1234567.89 → "$1,234,567.89"
     */
    public function formatCurrency(int|float|null $value, int $decimals = 2): string
    {
        if ($value === null) {
            return '—';
        }

        return '$' . number_format($value, $decimals, '.', ',');
    }

    /**
     * Formatea un porcentaje.
     * Ej: 0.956 → "95.6%"
     */
    public function formatPercent(float|null $value, int $decimals = 1): string
    {
        if ($value === null) {
            return '—';
        }

        return number_format($value, $decimals, '.', ',') . '%';
    }

    /**
     * Convierte horas a formato legible.
     * Ej: 72.5 → "72 h 30 min"
     */
    public function formatDuration(float|null $hours): string
    {
        if ($hours === null || $hours <= 0) {
            return '—';
        }
        $h = floor($hours);
        $m = round(($hours - $h) * 60);
        if ($m === 0) {
            return sprintf('%d h', (int) $h);
        }

        return sprintf('%d h %d min', (int) $h, $m);
    }
}
