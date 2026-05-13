<?php

declare(strict_types=1);

namespace App\Client\Domain\Repositories;

use App\Client\Domain\Criteria\ClientSearchCriteria;
use App\Client\Domain\Entities\Client;
use App\Client\Domain\ValueObjects\ClientIdValueObject;
use App\Client\Domain\ValueObjects\ClientRucValueObject;


interface ClientRepositoryInterface
{
    /**
     * @return Client[]
     * - Listar todos los clientes activos, ordenados alfabéticamente por su nombre, asegurando que no se incluyan clientes que han sido eliminados (soft deleted).
      * - Implementar paginación para la lista de clientes si la cantidad de registros es grande, para mejorar el rendimiento y la experiencia del usuario.
      * - Permitir filtrar la lista de clientes por estado (ACTIVE/INACTIVE) o por parte del nombre, para facilitar la búsqueda de clientes específicos.
      * - Asegurar que las consultas a la base de datos estén optimizadas, utilizando índices adecuados en los campos de búsqueda como el nombre y el RUC.
     */
    public function search(ClientSearchCriteria $criteria): array;

    public function findById(ClientIdValueObject $id): ?Client;

    public function findByRuc(ClientRucValueObject $ruc): ?Client;

    public function save(Client $client): void;

    public function delete(ClientIdValueObject $id): void;
}
