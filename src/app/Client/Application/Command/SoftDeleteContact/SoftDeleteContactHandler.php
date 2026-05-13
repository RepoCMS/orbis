<?php

declare(strict_types=1);

namespace App\Client\Application\Command\SoftDeleteContact;

use App\Client\Application\Exception\ContactNotFoundException;
use App\Client\Domain\Repositories\ContactRepositoryInterface;
use App\Client\Domain\ValueObjects\ContactIdValueObject;

final readonly class SoftDeleteContactHandler
{
    public function __construct(private ContactRepositoryInterface $contactRepository)
    {
    }

    public function __invoke(SoftDeleteContactCommand $command): void
    {
        $contactId = new ContactIdValueObject($command->contactId);
        $contact = $this->contactRepository->findById($contactId)
            ?? throw ContactNotFoundException::withId($command->contactId);

        $contact->softDelete();

        $this->contactRepository->save($contact);
    }
}