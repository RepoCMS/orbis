<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Enums;

/**
 * Estado de una orden de compra de productos.
 *
 * OPEN      — Orden creada, sin artículos recibidos.
 * PARTIAL   — Algunos artículos han llegado; la orden sigue abierta.
 * COMPLETED — Todos los artículos fueron recibidos en una o más entradas.
 * CANCELLED — Orden cancelada sin recepción total.
 */
enum PurchaseOrderStatus: string
{
    case OPEN      = 'open';
    case PARTIAL   = 'partial';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}
