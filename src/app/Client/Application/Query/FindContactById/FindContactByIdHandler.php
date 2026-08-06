<?php

declare(strict_types=1);

namespace App\Client\Application\Query\FindContactById;

use App\Client\Application\Exception\ContactNotFoundException;
use App\Client\Application\Query\Response\ContactResponse;
use App\Client\Domain\Repositories\ContactRepositoryInterface;
use App\Client\Domain\ValueObjects\ContactIdValueObject;

final readonly class FindContactByIdHandler
{
    public function __construct(private ContactRepositoryInterface $contactRepository)
    {
    }

    public function __invoke(FindContactByIdQuery $query): ContactResponse
    {
        $contactId = new ContactIdValueObject($query->contactId);
        $contact = $this->contactRepository->findById($contactId);

        if ($contact === null) {
            throw ContactNotFoundException::withId($query->contactId);
        }

        return ContactResponse::fromEntity($contact);
    }
}
