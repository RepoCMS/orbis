<?php

declare(strict_types=1);

namespace App\Client\Application\Command\AddContact;

use App\Client\Application\Exception\ClientNotFoundException;
use App\Client\Domain\Entities\Contact;
use App\Client\Domain\Repositories\ClientRepositoryInterface;
use App\Client\Domain\Repositories\ContactRepositoryInterface;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Client\Domain\ValueObjects\ContactEmailValueObject;
use App\Client\Domain\ValueObjects\ContactIdValueObject;
use App\Client\Domain\ValueObjects\ContactPhoneNumberValueObject;
use App\Client\Domain\ValueObjects\ContactRoleValueObject;
use App\Shared\Domain\Service\IdGeneratorInterface;

final readonly class AddContactHandler
{
    public function __construct(
        private ClientRepositoryInterface $clientRepository,
        private ContactRepositoryInterface $contactRepository,
        private IdGeneratorInterface $idGenerator,
    ) {
    }

    public function __invoke(AddContactCommand $command): void
    {
        $clientId = new ClientIdValueObject($command->clientId);

        if ($this->clientRepository->findById($clientId) === null) {
            throw ClientNotFoundException::withId($command->clientId);
        }

        $contact = Contact::create(
            id:          new ContactIdValueObject($this->idGenerator->generate()),
            clientId:    $clientId,
            firstName:   $command->firstName,
            lastName:    $command->lastName,
            email:       new ContactEmailValueObject($command->email),
            phoneNumber: new ContactPhoneNumberValueObject($command->phoneNumber),
            role:        new ContactRoleValueObject($command->role),
        );

        $this->contactRepository->save($contact);
    }
}
