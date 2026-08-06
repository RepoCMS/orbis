<?php

declare(strict_types=1);

namespace App\Client\Application\Command\DeactivateClient;

use App\Client\Application\Exception\ClientNotFoundException;
use App\Client\Domain\Repositories\ClientRepositoryInterface;
use App\Client\Domain\Repositories\ContactRepositoryInterface;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Identity\Application\Command\DeactivateUser\DeactivateUserCommand;
use App\Identity\Application\Command\DeactivateUser\DeactivateUserHandler;

final readonly class DeactivateClientHandler
{
    public function __construct(
        private ClientRepositoryInterface  $clientRepository,
        private ContactRepositoryInterface $contactRepository,
        private DeactivateUserHandler      $deactivateUser,
    ) {
    }

    public function __invoke(DeactivateClientCommand $command): void
    {
        $clientId = new ClientIdValueObject($command->clientId);
        $client = $this->clientRepository->findById($clientId)
            ?? throw ClientNotFoundException::withId($command->clientId);

        $client->deactivate();
        $this->clientRepository->save($client);

        // Cascade: desactivar todos los contactos activos del cliente
        // y sus usuarios en Identity BC.
        $contacts = $this->contactRepository->findByClientId($clientId);

        foreach ($contacts as $contact) {
            if (!$contact->isActive()) {
                continue;
            }

            $contact->deactivate();
            $this->contactRepository->save($contact);

            if ($contact->userId() !== null) {
                try {
                    ($this->deactivateUser)(new DeactivateUserCommand(
                        userId: (string) $contact->userId(),
                    ));
                } catch (\Throwable) {
                    // El usuario puede estar ya inactivo; no es crítico para la cascada.
                }
            }
        }
    }
}
