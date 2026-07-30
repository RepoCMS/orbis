<?php

declare(strict_types=1);

namespace App\Identity\Domain\Service;

/**
 * Puerto cross-BC para consultar datos de un Contact (Client BC).
 * La implementación vive en Infrastructure y hace la consulta via DBAL.
 */
interface ContactFinderInterface
{
    /**
     * @return array{id: string, clientId: string, firstName: string, lastName: string, email: string}|null
     */
    public function findById(string $contactId): ?array;
}
