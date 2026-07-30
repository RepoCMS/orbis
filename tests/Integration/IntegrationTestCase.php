<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Base test case para tests de integración con Doctrine.
 *
 * - Bootea el kernel Symfony en entorno test.
 * - Obtiene el EntityManager configurado (misma conexión que la app).
 * - Envuelve cada test en una transacción que se revierte automáticamente
 *   en tearDown(), asegurando aislamiento total entre tests.
 */
#[Group('integration')]
abstract class IntegrationTestCase extends KernelTestCase
{
    protected ?EntityManagerInterface $entityManager = null;

    /**
     * Configuración común antes de cada test.
     * Bootea kernel, obtiene EM, inicia transacción.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Boot kernel en modo test si no está booteado
        self::bootKernel();

        $container = self::getContainer();

        $this->entityManager = $container->get(EntityManagerInterface::class);
        \assert($this->entityManager instanceof EntityManagerInterface);

        // Inicia transacción — se revertirá en tearDown
        $this->entityManager->beginTransaction();
    }

    /**
     * Limpieza después de cada test.
     * Revierte la transacción abierta en setUp.
     */
    protected function tearDown(): void
    {
        if ($this->entityManager !== null && $this->entityManager->getConnection()->isTransactionActive()) {
            $this->entityManager->rollback();
        }

        $this->entityManager = null;

        parent::tearDown();
    }

    /**
     * Retorna el EntityManager actual.
     */
    protected function getEntityManager(): EntityManagerInterface
    {
        \assert($this->entityManager !== null, 'EntityManager no inicializado. ¿Llamaste a setUp()?');

        return $this->entityManager;
    }

    /**
     * Persiste una entidad y hace flush parcial.
     */
    protected function persistAndFlush(object $entity): void
    {
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }

    /**
     * Limpia el EntityManager para forzar recarga desde BD.
     */
    protected function clearEntityManager(): void
    {
        $this->entityManager->clear();
    }
}
