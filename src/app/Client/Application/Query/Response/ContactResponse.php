<?php

declare(strict_types=1);

namespace App\Client\Application\Query\Response;

use App\Client\Domain\Entities\Contact;

final readonly class ContactResponse
{
    public function __construct(
        public string $id,
        public string $clientId,
        public ?string $userId,
        public string $firstName,
        public string $lastName,
        public string $email,
        public string $phoneNumber,
        public string $role,
        public string $status,
    ) {
    }

    public static function fromEntity(Contact $contact): self
    {
        return new self(
            id:          (string) $contact->id(),
            clientId:    (string) $contact->clientId(),
            userId:      $contact->userId() !== null ? (string) $contact->userId() : null,
            firstName:   $contact->firstName(),
            lastName:    $contact->lastName(),
            email:       (string) $contact->email(),
            phoneNumber: (string) $contact->phoneNumber(),
            role:        (string) $contact->role(),
            status:      $contact->status()->value,
        );
    }
}
