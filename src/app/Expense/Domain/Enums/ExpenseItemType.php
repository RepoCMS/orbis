<?php

declare(strict_types=1);

namespace App\Expense\Domain\Enums;

/**
 * Tipos de gasto operativo que un técnico puede reportar.
 *
 * Cada tipo define si requiere o no evidencia (comprobante) y su categoría.
 */
enum ExpenseItemType: string
{
    // Transporte
    case UBER_EMPRESARIAL = 'uber_empresarial';
    case UBER_EFECTIVO = 'uber_efectivo';
    case TAXI = 'taxi';
    case COLECTIVO = 'colectivo';
    case BUS = 'bus';
    case BUS_INTERPROVINCIAL = 'bus_interprovincial';
    case MUNICIPAL = 'municipal';
    case AVION = 'avion';

    // Hospedaje
    case HOTEL = 'hotel';

    // Alimentación
    case ALIMENTACION = 'alimentacion';

    // Servicios
    case ENCOMIENDAS = 'encomiendas';

    // Transporte adicional
    case TREN = 'tren';

    // Compras
    case COMPRA_EXPRESS = 'compra_express';

    // Otros
    case INDUMENTARIA = 'indumentaria';
    case INSUMOS = 'insumos';
    case OTRO = 'otro';

    public function label(): string
    {
        return match ($this) {
            self::UBER_EMPRESARIAL => 'Uber Empresarial',
            self::UBER_EFECTIVO => 'Uber Efectivo',
            self::TAXI => 'Taxi',
            self::COLECTIVO => 'Colectivo',
            self::BUS => 'Bus',
            self::BUS_INTERPROVINCIAL => 'Bus Interprovincial',
            self::MUNICIPAL => 'Municipal',
            self::AVION => 'Avión',
            self::HOTEL => 'Hotel',
            self::ALIMENTACION => 'Alimentación',
            self::ENCOMIENDAS => 'Encomiendas',
            self::TREN => 'Tren',
            self::COMPRA_EXPRESS => 'Compra Express',
            self::INDUMENTARIA => 'Indumentaria',
            self::INSUMOS => 'Insumos',
            self::OTRO => 'Otro',
        };
    }

    /**
     * Indica si este tipo de gasto requiere obligatoriamente
     * un comprobante/evidencia para ser válido.
     */
    public function evidenceRequired(): bool
    {
        return match ($this) {
            self::TAXI, self::COLECTIVO, self::BUS, self::OTRO => false,
            default => true,
        };
    }

    /**
     * Categoría funcional del gasto.
     *
     * @return string transporte|hospedaje|alimentacion|servicios|otros
     */
    public function category(): string
    {
        return match ($this) {
            self::UBER_EMPRESARIAL,
            self::UBER_EFECTIVO,
            self::TAXI,
            self::COLECTIVO,
            self::BUS,
            self::BUS_INTERPROVINCIAL,
            self::MUNICIPAL,
            self::AVION,
            self::TREN => 'transporte',

            self::HOTEL => 'hospedaje',

            self::ALIMENTACION => 'alimentacion',

            self::ENCOMIENDAS => 'servicios',

            self::INDUMENTARIA,
            self::INSUMOS,
            self::COMPRA_EXPRESS,
            self::OTRO => 'otros',
        };
    }
}
