<?php

declare(strict_types=1);

namespace App\Identity\Domain\Entities;

use App\Identity\Domain\Enums\AuthEvent;
use App\Identity\Domain\ValueObjects\AuthLogIdValueObject;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use DateTimeImmutable;

/**
 * Registro inmutable de auditoría de eventos de seguridad.
 * Nunca se modifica ni elimina — es append-only.
 */
final class AuthLog
{
    private function __construct(
        private AuthLogIdValueObject $id,
        private UserIdValueObject $userId,
        private AuthEvent $event,
        private string $ipAddress,
        private string $userAgent,
        private DateTimeImmutable $occurredAt,
    ) {
    }

    public static function record(
        AuthLogIdValueObject $id,
        UserIdValueObject $userId,
        AuthEvent $event,
        string $ipAddress,
        string $userAgent,
    ): self {
        return new self(
            $id,
            $userId,
            $event,
            $ipAddress,
            $userAgent,
            new DateTimeImmutable(),
        );
    }

    public function id(): AuthLogIdValueObject
    {
        return $this->id;
    }

    public function userId(): UserIdValueObject
    {
        return $this->userId;
    }

    public function event(): AuthEvent
    {
        return $this->event;
    }

    public function ipAddress(): string
    {
        return $this->ipAddress;
    }

    public function userAgent(): string
    {
        return $this->userAgent;
    }

    public function occurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
