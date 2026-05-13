<?php

declare(strict_types=1);

namespace App\Organization\Application\Command\ProvisionEmployeeAccess;

use App\Identity\Application\Command\GenerateActivationToken\GenerateActivationTokenCommand;
use App\Identity\Application\Command\GenerateActivationToken\GenerateActivationTokenHandler;
use App\Identity\Application\Command\RegisterUser\RegisterUserCommand;
use App\Identity\Application\Command\RegisterUser\RegisterUserHandler;
use App\Organization\Application\Command\LinkUserToEmployee\LinkUserToEmployeeCommand;
use App\Organization\Application\Command\LinkUserToEmployee\LinkUserToEmployeeHandler;
use App\Organization\Domain\Events\EmployeeAccessProvisioned;
use App\Shared\Domain\Event\DomainEventPublisherInterface;

/**
 * Orquesta la creación de acceso al sistema para un empleado:
 *   1. Registra el User en Identity BC.
 *   2. Vincula el User al Employee en Organization BC.
 *   3. Genera y envía el token de activación por email.
 *   4. Publica el evento EmployeeAccessProvisioned.
 *
 * El Controller de Organization ya no necesita importar ningún handler de Identity.
 */
final readonly class ProvisionEmployeeAccessHandler
{
    public function __construct(
        private RegisterUserHandler           $registerUser,
        private LinkUserToEmployeeHandler     $linkUser,
        private GenerateActivationTokenHandler $generateToken,
        private DomainEventPublisherInterface $publisher,
    ) {
    }

    public function __invoke(ProvisionEmployeeAccessCommand $command): void
    {
        $userId = ($this->registerUser)(new RegisterUserCommand(
            username:    $command->username,
            rawPassword: $command->rawPassword,
            roleId:      $command->roleId,
        ));

        ($this->linkUser)(new LinkUserToEmployeeCommand(
            employeeId: $command->employeeId,
            userId:     $userId,
        ));

        try {
            ($this->generateToken)(new GenerateActivationTokenCommand(userId: $userId));
        } catch (\Throwable) {
            // No crítico: el token puede regenerarse después manualmente.
        }

        $this->publisher->publish(new EmployeeAccessProvisioned(
            employeeId: $command->employeeId,
            userId:     $userId,
        ));
    }
}
