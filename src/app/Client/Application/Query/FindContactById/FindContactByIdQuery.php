<?php

declare(strict_types=1);

namespace App\Client\Application\Query\FindContactById;

final readonly class FindContactByIdQuery
{
    public function __construct(public string $contactId)
    {
    }
}