<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Hace nullable la columna user_id en la tabla employees.
 *
 * Motivo: Un empleado puede existir sin un usuario del sistema. El acceso al
 * portal es opcional — se asigna en un paso posterior a través de
 * LinkUserToEmployee o CreateEmployeeWithUser.
 */
final class Version20260422000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'employees.user_id nullable — el usuario del sistema es opcional para un empleado';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE employees MODIFY user_id VARCHAR(26) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('UPDATE employees SET user_id = \'\' WHERE user_id IS NULL');
        $this->addSql('ALTER TABLE employees MODIFY user_id VARCHAR(26) NOT NULL');
    }
}
