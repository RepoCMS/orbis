<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Console;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'orbis:cross-bc:check-consistency',
    description: 'Verifica consistencia cross-BC: entidades huérfanas, referencias inválidas y violaciones de integridad.',
)]
final class CrossBcConsistencyCheckCommand extends Command
{
    private int $issues = 0;
    private OutputInterface $output;

    public function __construct(
        private readonly Connection $dbal,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;

        $this->checkOrphanUsers();
        $this->checkEmployeesWithoutUser();
        $this->checkContactsWithoutUser();
        $this->checkCashAdvancesForInactiveEmployees();
        $this->checkOperatingExpensesForMissingTickets();

        if ($this->issues === 0) {
            $output->writeln('<info>✔ No se encontraron inconsistencias cross-BC.</info>');

            return Command::SUCCESS;
        }

        $output->writeln(sprintf(
            '<comment>Se encontraron %d inconsistencia(s). Revise los reportes anteriores.</comment>',
            $this->issues,
        ));

        return Command::FAILURE;
    }

    private function addIssue(string $message): void
    {
        $this->issues++;
        $this->output->writeln("  <fg=red>✘</> $message");
    }

    private function checkOrphanUsers(): void
    {
        $this->output->writeln('<fg=blue>▶</> Verificando usuarios huérfanos (sin Employee ni Contact)...');

        $orphans = $this->dbal->fetchAllAssociative(
            'SELECT u.id, u.username, u.status
             FROM users u
             WHERE u.deleted_at IS NULL
               AND u.id NOT IN (
                   SELECT e.user_id FROM employees e WHERE e.user_id IS NOT NULL AND e.deleted_at IS NULL
                )
               AND u.id NOT IN (
                   SELECT c.user_id FROM contacts c WHERE c.user_id IS NOT NULL AND c.deleted_at IS NULL
                )',
        );

        if ($orphans === []) {
            $this->output->writeln('  <info>✔ Sin usuarios huérfanos.</info>');

            return;
        }

        $this->output->writeln(sprintf('  <comment>%d usuario(s) huérfano(s):</comment>', count($orphans)));

        $table = new Table($this->output);
        $table->setHeaders(['ID', 'Username', 'Status']);
        $table->setRows(array_map(fn (array $o) => [$o['id'], $o['username'], $o['status']], $orphans));
        $table->render();

        $this->issues += count($orphans);
    }

    private function checkEmployeesWithoutUser(): void
    {
        $this->output->writeln('<fg=blue>▶</> Verificando empleados activos sin User vinculado...');

        $employees = $this->dbal->fetchAllAssociative(
            "SELECT e.id, e.identification, e.first_name, e.last_name
             FROM employees e
             WHERE e.user_id IS NULL
               AND e.deleted_at IS NULL
               AND e.profile = 'technician'",
        );

        if ($employees === []) {
            $this->output->writeln('  <info>✔ Todos los técnicos tienen User vinculado.</info>');

            return;
        }

        $this->output->writeln(sprintf('  <comment>%d técnico(s) sin User:</comment>', count($employees)));

        $table = new Table($this->output);
        $table->setHeaders(['ID', 'Identificación', 'Nombre']);
        $table->setRows(array_map(
            fn (array $e) => [$e['id'], $e['identification'], $e['first_name'] . ' ' . $e['last_name']],
            $employees,
        ));
        $table->render();

        $this->issues += count($employees);
    }

    private function checkContactsWithoutUser(): void
    {
        $this->output->writeln('<fg=blue>▶</> Verificando contactos activos sin User vinculado...');

        $contacts = $this->dbal->fetchAllAssociative(
            'SELECT c.id, c.first_name, c.last_name, c.email
             FROM contacts c
             WHERE c.user_id IS NULL
               AND c.deleted_at IS NULL',
        );

        if ($contacts === []) {
            $this->output->writeln('  <info>✔ Todos los contactos tienen User vinculado.</info>');

            return;
        }

        $this->output->writeln(sprintf('  <comment>%d contacto(s) sin User:</comment>', count($contacts)));

        $table = new Table($this->output);
        $table->setHeaders(['ID', 'Nombre', 'Email']);
        $table->setRows(array_map(
            fn (array $c) => [$c['id'], $c['first_name'] . ' ' . $c['last_name'], $c['email']],
            $contacts,
        ));
        $table->render();

        $this->issues += count($contacts);
    }

    private function checkCashAdvancesForInactiveEmployees(): void
    {
        $this->output->writeln('<fg=blue>▶</> Verificando adelantos activos para empleados inactivos...');

        $advances = $this->dbal->fetchAllAssociative(
            "SELECT ca.id, ca.advance_number, ca.amount, ca.employee_id
             FROM expense_cash_advances ca
             JOIN employees e ON e.id = ca.employee_id
             WHERE ca.status = 'outstanding'
               AND ca.deleted_at IS NULL
               AND (e.status != 'active' OR e.deleted_at IS NOT NULL)",
        );

        if ($advances === []) {
            $this->output->writeln('  <info>✔ Sin adelantos activos para empleados inactivos.</info>');

            return;
        }

        $this->output->writeln(sprintf('  <comment>%d adelanto(s) en empleados inactivos:</comment>', count($advances)));

        $table = new Table($this->output);
        $table->setHeaders(['ID', 'N° Adelanto', 'Monto', 'Employee ID']);
        $table->setRows(array_map(
            fn (array $a) => [$a['id'], $a['advance_number'], '$' . $a['amount'], $a['employee_id']],
            $advances,
        ));
        $table->render();

        $this->issues += count($advances);
    }

    private function checkOperatingExpensesForMissingTickets(): void
    {
        $this->output->writeln('<fg=blue>▶</> Verificando liquidaciones con referencias a tickets inexistentes...');

        $expenses = $this->dbal->fetchAllAssociative(
            'SELECT oe.id, oe.sequence, oe.employee_id
             FROM expense_operating_expenses oe
             WHERE oe.deleted_at IS NULL
               AND JSON_LENGTH(oe.ticket_ids) > 0
               AND NOT EXISTS (
                   SELECT 1 FROM tickets t
                   WHERE t.id MEMBER OF(oe.ticket_ids)
               )',
        );

        if ($expenses === []) {
            $this->output->writeln('  <info>✔ Todos los ticketIds refieren a tickets existentes.</info>');

            return;
        }

        $this->output->writeln(sprintf('  <comment>%d liquidación(es) con tickets inválidos:</comment>', count($expenses)));

        $table = new Table($this->output);
        $table->setHeaders(['ID', 'Secuencia', 'Employee ID']);
        $table->setRows(array_map(
            fn (array $e) => [$e['id'], $e['sequence'], $e['employee_id']],
            $expenses,
        ));
        $table->render();

        $this->issues += count($expenses);
    }
}
