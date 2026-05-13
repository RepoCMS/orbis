<?php

declare(strict_types=1);

namespace App\Client\Application\Command\ProvisionContactAccess;

use App\Client\Application\Command\LinkUserToContact\LinkUserToContactCommand;
use App\Client\Application\Command\LinkUserToContact\LinkUserToContactHandler;
use App\Client\Domain\Events\ContactAccessProvisioned;
use App\Identity\Application\Command\GenerateActivationToken\GenerateActivationTokenCommand;
use App\Identity\Application\Command\GenerateActivationToken\GenerateActivationTokenHandler;
use App\Identity\Application\Command\RegisterUser\RegisterUserCommand;
use App\Identity\Application\Command\RegisterUser\RegisterUserHandler;
use App\Shared\Domain\Event\DomainEventPublisherInterface;

/**
 * Orquesta la creación de acceso al portal para un contacto de cliente:
 *   1. Registra el User en Identity BC.
 *   2. Vincula el User al Contact en Client BC.
 *   3. Genera y envía el token de activación por email.
 *   4. Publica el evento ContactAccessProvisioned.
 *
 * El Controller de Client ya no necesita importar ningún handler de Identity.
 */
final readonly class ProvisionContactAccessHandler
{
    public function __construct(
        private RegisterUserHandler            $registerUser,
        private LinkUserToContactHandler       $linkUser,
        private GenerateActivationTokenHandler $generateToken,
        private DomainEventPublisherInterface  $publisher,
    ) {
    }

    public function __invoke(ProvisionContactAccessCommand $command): void
    {
        $userId = ($this->registerUser)(new RegisterUserCommand(
            username:    $command->username,
            rawPassword: $command->rawPassword,
            roleId:      $command->roleId,
        ));

        ($this->linkUser)(new LinkUserToContactCommand(
            contactId: $command->contactId,
            userId:    $userId,
        ));

        try {
            ($this->generateToken)(new GenerateActivationTokenCommand(userId: $userId));
        } catch (\Throwable) {
            // No crítico: el token puede regenerarse después manualmente.
        }

        $this->publisher->publish(new ContactAccessProvisioned(
            contactId: $command->contactId,
            clientId:  $command->clientId,
            userId:    $userId,
        ));
    }
}
