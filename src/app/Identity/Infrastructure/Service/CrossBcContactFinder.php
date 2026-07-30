<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Service;

use App\Client\Domain\Repositories\ContactRepositoryInterface;
use App\Client\Domain\ValueObjects\ContactIdValueObject;
use App\Identity\Domain\Service\ContactFinderInterface;

/**
 * Adaptador cross-BC que consulta datos de un Contact (Client BC)
 * usando el repositorio de dominio en lugar de DBAL directo.
 */
final readonly class CrossBcContactFinder implements ContactFinderInterface
{
    public function __construct(
        private ContactRepositoryInterface $contactRepository,
    ) {
    }

    public function findById(string $contactId): ?array
    {
        $contact = $this->contactRepository->findById(
            ContactIdValueObject::fromString($contactId)
        );

        if ($contact === null) {
            return null;
        }

        return [
            'id'        => (string) $contact->id(),
            'clientId'  => (string) $contact->clientId(),
            'firstName' => $contact->firstName(),
            'lastName'  => $contact->lastName(),
            'email'     => (string) $contact->email(),
        ];
    }
}
