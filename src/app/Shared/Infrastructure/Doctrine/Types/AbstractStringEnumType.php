<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Doctrine\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

abstract class AbstractStringEnumType extends StringType
{
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): mixed
    {
        if (null === $value || '' === $value) {
            return null;
        }

        $class = $this->getEnumClass();

        return $class::from((string) $value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if (null === $value) {
            return null;
        }

        /** @var \BackedEnum $value */
        return $value->value;
    }

    /** @return class-string<\BackedEnum> */
    abstract protected function getEnumClass(): string;
}
