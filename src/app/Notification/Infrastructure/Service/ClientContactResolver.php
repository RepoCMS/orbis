<?php

declare(strict_types=1);

namespace App\Notification\Infrastructure\Service;

use App\Client\Domain\Repositories\ContactRepositoryInterface;
use App\Client\Domain\ValueObjects\ContactIdValueObject;
use App\Notification\Domain\Services\ContactResolverInterface;

final readonly class ClientContactResolver implements ContactResolverInterface
{
    public function __construct(
        private ContactRepositoryInterface $contactRepository,
    ) {
    }

    public function resolve(string $recipientType, string $recipientId): array
    {
        if ($recipientType !== 'contact') {
            return ['email' => null, 'phone' => null, 'name' => null];
        }

        $contact = $this->contactRepository->findById(
            ContactIdValueObject::fromString($recipientId),
        );

        if ($contact === null) {
            return ['email' => null, 'phone' => null, 'name' => null];
        }

        return [
            'email' => (string) $contact->email(),
            'phone' => (string) $contact->phoneNumber(),
            'name' => $contact->fullName(),
        ];
    }
}
