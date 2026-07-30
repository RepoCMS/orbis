<?php

declare(strict_types=1);

namespace App\Identity\Application\Query\Response;

use App\Identity\Domain\Entities\AuthLog;

final readonly class AuthLogResponse
{
    public function __construct(
        public string $id,
        public string $userId,
        public string $event,
        public string $ipAddress,
        public string $userAgent,
        public string $occurredAt,
    ) {
    }

    public static function fromEntity(AuthLog $authLog): self
    {
        return new self(
            id: (string) $authLog->id(),
            userId: (string) $authLog->userId(),
            event: $authLog->event()->value,
            ipAddress: $authLog->ipAddress(),
            userAgent: $authLog->userAgent(),
            occurredAt: $authLog->occurredAt()->format('c'),
        );
    }
}
