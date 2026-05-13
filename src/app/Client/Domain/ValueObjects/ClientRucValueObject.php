<?php

declare(strict_types=1);

namespace App\Client\Domain\ValueObjects;

use App\Client\Domain\Exceptions\ClientRucException;

/**
 * RUC ecuatoriano: 13 dígitos con validación completa del dígito verificador.
 *
 * Estructura:
 *   - Dígitos 1-2 : código de provincia (01-24)
 *   - Dígito 3    : tipo de contribuyente
 *       0-5 → persona natural          → verificador en posición 10 (módulo 10)
 *       6   → entidad pública          → verificador en posición 9  (módulo 11, coeficientes 3,2,7,6,5,4,3,2)
 *       9   → sociedad privada/extranjera → verificador en posición 10 (módulo 11, coeficientes 4,3,2,7,6,5,4,3,2)
 *   - Dígitos 4-12: número secuencial
 *   - Dígito 13   : dígito verificador (establecimiento 001)
 */
final readonly class ClientRucValueObject
{
    private const RUC_LENGTH = 13;
    private const MAX_PROVINCE = 24;

    private string $ruc;

    public function __construct(string $ruc)
    {
        $normalized = trim($ruc);

        if ($normalized === '') {
            throw new ClientRucException('El RUC no puede estar vacío.');
        }

        if (!preg_match('/^[0-9]+$/', $normalized)) {
            throw new ClientRucException('El RUC debe contener solo dígitos numéricos.');
        }

        if (strlen($normalized) !== self::RUC_LENGTH) {
            throw new ClientRucException(
                sprintf('El RUC debe tener exactamente %d dígitos.', self::RUC_LENGTH)
            );
        }

        $province = (int) substr($normalized, 0, 2);
        if ($province < 1 || $province > self::MAX_PROVINCE) {
            throw new ClientRucException(
                sprintf('Los dos primeros dígitos del RUC deben corresponder a una provincia válida (01–%02d).', self::MAX_PROVINCE)
            );
        }

        $thirdDigit = (int) $normalized[2];

        $valid = match (true) {
            $thirdDigit >= 0 && $thirdDigit <= 5 => self::validateNaturalPerson($normalized),
            $thirdDigit === 6                     => self::validatePublicEntity($normalized),
            $thirdDigit === 9                     => self::validatePrivateCompany($normalized),
            default                               => false,
        };

        if (!$valid) {
            throw new ClientRucException('El RUC no es válido (dígito verificador incorrecto).');
        }

        $this->ruc = $normalized;
    }

    public static function fromString(string $ruc): self
    {
        return new self($ruc);
    }

    public function __toString(): string
    {
        return $this->ruc;
    }

    /**
     * Persona natural: módulo 10 sobre los primeros 9 dígitos, verificador en posición 10.
     * Coeficientes: 2,1,2,1,2,1,2,1,2 — si resultado > 9 se resta 9.
     */
    private static function validateNaturalPerson(string $ruc): bool
    {
        $coefficients = [2, 1, 2, 1, 2, 1, 2, 1, 2];
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $product = (int) $ruc[$i] * $coefficients[$i];
            $sum += $product > 9 ? $product - 9 : $product;
        }
        $verifier = $sum % 10 === 0 ? 0 : 10 - ($sum % 10);

        return $verifier === (int) $ruc[9];
    }

    /**
     * Entidad pública: módulo 11 sobre los primeros 8 dígitos, verificador en posición 9.
     * Coeficientes: 3,2,7,6,5,4,3,2.
     */
    private static function validatePublicEntity(string $ruc): bool
    {
        $coefficients = [3, 2, 7, 6, 5, 4, 3, 2];
        $sum = 0;
        for ($i = 0; $i < 8; $i++) {
            $sum += (int) $ruc[$i] * $coefficients[$i];
        }
        $remainder = $sum % 11;
        $verifier = $remainder === 0 ? 0 : 11 - $remainder;

        return $verifier === (int) $ruc[8];
    }

    /**
     * Sociedad privada/extranjera: módulo 11 sobre los primeros 9 dígitos, verificador en posición 10.
     * Coeficientes: 4,3,2,7,6,5,4,3,2.
     */
    private static function validatePrivateCompany(string $ruc): bool
    {
        $coefficients = [4, 3, 2, 7, 6, 5, 4, 3, 2];
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += (int) $ruc[$i] * $coefficients[$i];
        }
        $remainder = $sum % 11;
        $verifier = $remainder === 0 ? 0 : 11 - $remainder;

        return $verifier === (int) $ruc[9];
    }
}
