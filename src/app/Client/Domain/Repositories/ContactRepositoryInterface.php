<?php

declare(strict_types=1);

namespace App\Client\Domain\Repositories;

use App\Client\Domain\Entities\Contact;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Client\Domain\ValueObjects\ContactIdValueObject;
use App\Identity\Domain\ValueObjects\UserIdValueObject;

interface ContactRepositoryInterface
{
    /**
     * @return Contact[]
     */
    public function findAll(): array;

    public function findById(ContactIdValueObject $id): ?Contact;

    public function findByUserId(UserIdValueObject $userId): ?Contact;

    /**
     * @return Contact[]
     */
    public function findByClientId(ClientIdValueObject $clientId): array;

    public function save(Contact $contact): void;

    public function delete(ContactIdValueObject $id): void;
}
