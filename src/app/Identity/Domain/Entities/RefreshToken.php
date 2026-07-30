<?php

declare(strict_types=1);

namespace App\Identity\Domain\Entities;

use App\Identity\Domain\ValueObjects\RefreshTokenIdValueObject;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use DateTimeImmutable;

final class RefreshToken
{
    private ?DateTimeImmutable $createdAt = null;

    private function __construct(
        private RefreshTokenIdValueObject $id,
        private UserIdValueObject $userId,
        private string $tokenHash,
        private DateTimeImmutable $expiresAt,
        private ?DateTimeImmutable $revokedAt = null,
    ) {
    }

    public static function create(
        RefreshTokenIdValueObject $id,
        UserIdValueObject $userId,
        string $plainToken,
    ): self {
        $token = new self(
            $id,
            $userId,
            hash('sha256', $plainToken),
            new DateTimeImmutable('+30 days'),
        );
        $token->createdAt = new DateTimeImmutable();

        return $token;
    }

    public function isValid(): bool
    {
        if ($this->revokedAt !== null) {
            return false;
        }

        return $this->expiresAt > new DateTimeImmutable();
    }

    public function revoke(): void
    {
        $this->revokedAt = new DateTimeImmutable();
    }

    public function id(): RefreshTokenIdValueObject
    {
        return $this->id;
    }

    public function userId(): UserIdValueObject
    {
        return $this->userId;
    }

    public function tokenHash(): string
    {
        return $this->tokenHash;
    }

    public function expiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function revokedAt(): ?DateTimeImmutable
    {
        return $this->revokedAt;
    }

    public function createdAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }
}
