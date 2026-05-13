<?php

declare(strict_types=1);

namespace App\Client\Application\Command\ProvisionContactAccess;

/**
 * Crea una cuenta de usuario en Identity BC y la vincula al contacto de cliente.
 * Orquesta el flujo cross-BC para que el Controller no dependa de Identity.
 */
final readonly class ProvisionContactAccessCommand
{
    public function __construct(
        public readonly string $contactId,
        public readonly string $clientId,
        public readonly string $username,
        public readonly string $rawPassword,
        public readonly string $roleId,
    ) {
    }
}
