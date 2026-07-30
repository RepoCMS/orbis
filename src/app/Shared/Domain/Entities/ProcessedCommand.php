<?php

declare(strict_types=1);

namespace App\Shared\Domain\Entities;

use App\Shared\Domain\ValueObjects\ProcessedCommandId;

final class ProcessedCommand
{
    private function __construct(
        private readonly ProcessedCommandId $id,
        private readonly string $commandType,
        private readonly string $idempotencyKey,
        private readonly string $processedBy,
        private \DateTimeImmutable $processedAt,
    ) {}

    public static function register(
        ProcessedCommandId $id,
        string $commandType,
        string $idempotencyKey,
        string $processedBy,
    ): self {
        return new self($id, $commandType, $idempotencyKey, $processedBy, new \DateTimeImmutable());
    }

    public function id(): ProcessedCommandId
    {
        return $this->id;
    }

    public function commandType(): string
    {
        return $this->commandType;
    }

    public function idempotencyKey(): string
    {
        return $this->idempotencyKey;
    }

    public function processedBy(): string
    {
        return $this->processedBy;
    }

    public function processedAt(): \DateTimeImmutable
    {
        return $this->processedAt;
    }
}
