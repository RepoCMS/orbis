<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Agrega restricción UNIQUE en user_id para contacts y employees.
 *
 * La invariante de dominio "un User sólo puede estar vinculado a UNA persona"
 * se garantiza en la capa de aplicación (UserBindingChecker), pero estos
 * índices únicos refuerzan la integridad a nivel de base de datos dentro
 * de cada tabla.
 *
 * NOTA: si existe data duplicada, ejecutar primero un script de limpieza.
 */
final class Version20260504120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'UNIQUE constraint on user_id in contacts and employees tables';
    }

    public function up(Schema $schema): void
    {
        // Eliminar duplicados antes de crear el índice (protección)
        $this->addSql(<<<'SQL'
            DELETE c1 FROM contacts c1
            INNER JOIN contacts c2
                ON c1.user_id = c2.user_id
                AND c1.id > c2.id
            WHERE c1.user_id IS NOT NULL
        SQL);

        $this->addSql(<<<'SQL'
            DELETE e1 FROM employees e1
            INNER JOIN employees e2
                ON e1.user_id = e2.user_id
                AND e1.id > e2.id
            WHERE e1.user_id IS NOT NULL
        SQL);

        $this->addSql('CREATE UNIQUE INDEX uniq_contacts_user_id ON contacts (user_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_employees_user_id ON employees (user_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX uniq_contacts_user_id ON contacts');
        $this->addSql('DROP INDEX uniq_employees_user_id ON employees');
    }
}
