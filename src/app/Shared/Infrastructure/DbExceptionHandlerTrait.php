<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure;

use App\Shared\Exceptions\ApplicationException;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\ORMException;

trait DbExceptionHandlerTrait
{
    /**
     * Wrap DB operations and convert framework/DB exceptions to DDD ApplicationException.
     *
     * @param callable $fn The DB operation to execute
     * @param string $message Optional custom error message
     *
     * @throws ApplicationException
     */
    protected function wrapDbExceptions(callable $fn, string $message = 'Error de base de datos al procesar la solicitud.'): void
    {
        try {
            $fn();
        } catch (UniqueConstraintViolationException $e) {
            throw new ApplicationException('Ya existe un registro con esos datos.', 0, $e);
        } catch (DBALException | ORMException $e) {
            throw new ApplicationException($message, 0, $e);
        }
    }
}
