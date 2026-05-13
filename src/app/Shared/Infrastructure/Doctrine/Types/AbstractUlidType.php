<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Doctrine\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

abstract class AbstractUlidType extends StringType
{
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): mixed
    {
        if (null === $value || '' === $value) {
            return null;
        }

        $class = $this->getValueObjectClass();

        return $class::fromString((string) $value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        return (string) $value;
    }

    /** @return class-string */
    abstract protected function getValueObjectClass(): string;
}
