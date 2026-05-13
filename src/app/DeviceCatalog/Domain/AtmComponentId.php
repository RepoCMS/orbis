<?php

declare(strict_types=1);

namespace App\DeviceCatalog\Domain;

final class AtmComponentId
{
    private function __construct(private readonly string $value) {}

    public static function fromString(string $value): self
    {
        if (!\preg_match('/^[0-9A-HJKMNP-TV-Z]{26}$/', $value)) {
            throw new \InvalidArgumentException("Invalid AtmComponentId: {$value}");
        }
        return new self($value);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
