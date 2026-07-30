<?php

declare(strict_types=1);

namespace App\Inventory\Domain\Enums;

/**
 * Tipo de evidencia adjunta a una reparación.
 *
 * photo — Fotografía del estado de la pieza o del proceso de reparación.
 * video — Grabación de video del proceso de reparación.
 */
enum EvidenceType: string
{
    case PHOTO = 'photo';
    case VIDEO = 'video';
}
