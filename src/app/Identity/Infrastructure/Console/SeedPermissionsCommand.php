<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Console;

use App\Identity\Application\Command\DefinePermission\DefinePermissionCommand;
use App\Identity\Application\Command\DefinePermission\DefinePermissionHandler;
use App\Identity\Application\Exception\PermissionKeyAlreadyExistsException;
use App\Shared\Domain\Service\IdGeneratorInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Siembra el catálogo completo de permisos del sistema.
 *
 * Uso:
 *   symfony console app:permissions:seed           # solo muestra los nuevos creados
 *   symfony console app:permissions:seed --dry-run # simula sin persistir
 *
 * Los permisos ya existentes se omiten (idempotente).
 */
#[AsCommand(
    name: 'app:permissions:seed',
    description: 'Siembra el catálogo completo de permisos del sistema.',
)]
final class SeedPermissionsCommand extends Command
{
    /**
     * Catálogo de permisos agrupados por recurso.
     *
     * Formato: 'recurso.accion' => 'Descripción legible'
     *
     * Acciones estándar por recurso:
     *   view        → Ver el propio registro (ej: perfil propio)
     *   view_all    → Listar / ver todos los registros
     *   create      → Crear nuevo registro
     *   update      → Editar registro existente
     *   delete      → Eliminar (soft delete)
     *
     * Acciones de negocio específicas por recurso debajo de los estándar.
     *
     * @var array<string, string>
     */
    private const PERMISSIONS = [
        // ── Identity: Usuarios ──────────────────────────────────────────────
        'user.view'               => 'Ver detalle de un usuario',
        'user.view_all'           => 'Listar todos los usuarios',
        'user.create'             => 'Crear usuario',
        'user.update'             => 'Editar usuario',
        'user.delete'             => 'Eliminar usuario (soft delete)',
        'user.verify'             => 'Verificar cuenta de usuario',
        'user.activate'           => 'Activar cuenta de usuario',
        'user.deactivate'         => 'Desactivar cuenta de usuario',
        'user.reset_password'     => 'Restablecer contraseña de usuario',

        // ── Identity: Roles ──────────────────────────────────────────────────
        'role.view'               => 'Ver detalle de un rol',
        'role.view_all'           => 'Listar todos los roles',
        'role.create'             => 'Crear rol',
        'role.update'             => 'Editar nombre de un rol',
        'role.delete'             => 'Eliminar rol',
        'role.activate'           => 'Activar rol',
        'role.deactivate'         => 'Desactivar rol',
        'role.assign_permission'  => 'Asignar / revocar permisos en un rol',

        // ── Identity: Permisos ───────────────────────────────────────────────
        'permission.view_all'     => 'Listar todos los permisos del sistema',

        // ── Organization: Empleados ──────────────────────────────────────────
        'employee.view'           => 'Ver perfil de un empleado',
        'employee.view_all'       => 'Listar todos los empleados',
        'employee.create'         => 'Registrar nuevo empleado',
        'employee.update'         => 'Editar datos de un empleado',
        'employee.delete'         => 'Eliminar empleado (soft delete)',

        // ── Client: Clientes ─────────────────────────────────────────────────
        'client.view'             => 'Ver detalle de un cliente',
        'client.view_all'         => 'Listar todos los clientes',
        'client.create'           => 'Registrar nuevo cliente',
        'client.update'           => 'Editar datos de un cliente',
        'client.delete'           => 'Eliminar cliente (soft delete)',
        'client.activate'         => 'Activar contrato / estado de cliente',
        'client.deactivate'       => 'Desactivar contrato / estado de cliente',

        // ── Client: Contactos ────────────────────────────────────────────────
        'contact.view'            => 'Ver detalle de un contacto',
        'contact.view_all'        => 'Listar todos los contactos de un cliente',
        'contact.create'          => 'Agregar contacto a un cliente',
        'contact.update'          => 'Editar datos de un contacto',
        'contact.delete'          => 'Eliminar contacto (soft delete)',

        // ── Client: Sucursales ───────────────────────────────────────────────
        'site.view'               => 'Ver detalle de una sucursal',
        'site.create'             => 'Agregar sucursal a un cliente',
        'site.update'             => 'Editar datos de una sucursal (nombre, zona de servicio, estado)',

        // ── Organization: Zonas de Servicio ──────────────────────────────────
        'service_zone.view_all'       => 'Listar todas las zonas de servicio',
        'service_zone.create'         => 'Crear zona de servicio',
        'service_zone.update'         => 'Editar zona de servicio (nombre, estado)',
        'service_zone.assign_technician' => 'Asignar / remover técnicos en una zona de servicio',

        // ── Ticket: Incidencias de cajeros ───────────────────────────────────
        'ticket.view'             => 'Ver detalle de un ticket',
        'ticket.view_all'         => 'Listar todos los tickets',
        'ticket.create'           => 'Abrir nuevo ticket de servicio',
        'ticket.update'           => 'Editar datos de un ticket',
        'ticket.delete'           => 'Eliminar ticket (soft delete)',
        'ticket.assign'           => 'Asignar ticket a un técnico',
        'ticket.reassign'         => 'Reasignar ticket a otro técnico',
        'ticket.reschedule'       => 'Reprogramar fecha de atención',
        'ticket.close'            => 'Cerrar ticket resuelto',
        'ticket.reopen'           => 'Reabrir ticket cerrado',
        'ticket.add_comment'      => 'Agregar comentario / nota técnica',

        // ── Expense: Gastos operativos ───────────────────────────────────────
        'expense.view'            => 'Ver detalle de un gasto',
        'expense.view_all'        => 'Listar todos los gastos',
        'expense.create'          => 'Registrar nuevo gasto',
        'expense.update'          => 'Editar gasto',
        'expense.delete'          => 'Eliminar gasto (soft delete)',
        'expense.approve'         => 'Aprobar gasto',
        'expense.reject'          => 'Rechazar gasto',

        // ── Inventory ────────────────────────────────────────────────────────
        'inventory.view'          => 'Ver módulo de inventario (productos, ubicaciones, stock)',
        'inventory.create'        => 'Crear productos, ubicaciones, entradas y órdenes de compra',
        'inventory.update'        => 'Editar productos, ubicaciones y órdenes de compra',
        'inventory.delete'        => 'Eliminar productos y ubicaciones (soft delete)',
        'inventory.move_stock'    => 'Mover / transferir stock entre ubicaciones',
    ];

    public function __construct(
        private readonly DefinePermissionHandler $handler,
        private readonly IdGeneratorInterface $idGenerator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simula la siembra sin persistir nada.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $isDryRun = (bool) $input->getOption('dry-run');

        $io->title('Siembra de permisos — Orbis');

        if ($isDryRun) {
            $io->warning('Modo DRY-RUN: ningún cambio será persistido.');
        }

        $created = 0;
        $skipped = 0;

        foreach (self::PERMISSIONS as $key => $description) {
            try {
                if (!$isDryRun) {
                    ($this->handler)(new DefinePermissionCommand(
                        permissionId: $this->idGenerator->generate(),
                        key: $key,
                        description: $description,
                    ));
                }
                $io->writeln(sprintf('  <info>✓ Creado</info>    %s', $key));
                $created++;
            } catch (PermissionKeyAlreadyExistsException) {
                $io->writeln(sprintf('  <comment>↷ Existente</comment> %s', $key));
                $skipped++;
            }
        }

        $io->newLine();
        $io->success(sprintf(
            '%d permisos creados, %d ya existían.%s',
            $created,
            $skipped,
            $isDryRun ? ' (dry-run, nada fue persistido)' : '',
        ));

        return Command::SUCCESS;
    }
}
