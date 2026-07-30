<?php

declare(strict_types=1);

namespace App\Notification\Domain\Services;

/**
 * Resuelve datos de contacto (email, teléfono) desde otros BCs.
 * Al momento de enviar una notificación, se consulta el BC origen
 * para obtener los datos actualizados del destinatario.
 */
interface ContactResolverInterface
{
    /**
     * @param string $recipientType employee | contact | user | location
     * @param string $recipientId ULID del destinatario
     * @return array{email: ?string, phone: ?string, name: ?string}
     */
    public function resolve(string $recipientType, string $recipientId): array;
}
