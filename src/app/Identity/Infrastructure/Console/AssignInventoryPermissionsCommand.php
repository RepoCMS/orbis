<?php
/**
 * Assigns all inventory.* permissions to every active role that already has
 * at least one permission (i.e. non-empty roles, which are the "super/admin" ones).
 *
 * Run once:  php bin/console app:inventory:assign-permissions
 *
 * Or just use the UI: Roles → Edit → tick the inventory.* permissions.
 */

declare(strict_types=1);

namespace App\Identity\Infrastructure\Console;

use App\Identity\Application\Command\AssignPermissionToRole\AssignPermissionToRoleCommand;
use App\Identity\Application\Command\AssignPermissionToRole\AssignPermissionToRoleHandler;
use App\Identity\Domain\Repositories\PermissionRepositoryInterface;
use App\Identity\Domain\Repositories\RoleRepositoryInterface;
use App\Identity\Domain\ValueObjects\PermissionKeyValueObject;
use App\Identity\Domain\ValueObjects\RoleNameValueObject;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:inventory:assign-permissions',
    description: 'Asigna los permisos de inventario a un rol dado (por nombre).',
)]
final class AssignInventoryPermissionsCommand extends Command
{
    private const INVENTORY_KEYS = [
        'inventory.view',
        'inventory.create',
        'inventory.update',
        'inventory.delete',
        'inventory.move_stock',
    ];

    public function __construct(
        private readonly RoleRepositoryInterface $roleRepository,
        private readonly PermissionRepositoryInterface $permissionRepository,
        private readonly AssignPermissionToRoleHandler $handler,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('role', InputArgument::REQUIRED, 'Nombre del rol (ej: Administrador)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $roleName = $input->getArgument('role');

        $role = $this->roleRepository->findByName(new RoleNameValueObject($roleName));

        if ($role === null) {
            $io->error(sprintf('Rol "%s" no encontrado.', $roleName));
            $io->writeln('Roles disponibles:');
            $io->listing(array_map(
                static fn($r) => (string) $r->name(),
                $this->roleRepository->findAll(),
            ));
            return Command::FAILURE;
        }

        $assigned = 0;
        $skipped  = 0;

        foreach (self::INVENTORY_KEYS as $key) {
            $permission = $this->permissionRepository->findByKey(new PermissionKeyValueObject($key));

            if ($permission === null) {
                $io->warning(sprintf('Permiso "%s" no encontrado en BD — ejecuta app:permissions:seed primero.', $key));
                continue;
            }

            $permissionId = $permission->id();

            if ($role->hasPermission($permissionId)) {
                $io->writeln(sprintf('  <comment>↷ Ya asignado</comment>  %s', $key));
                $skipped++;
                continue;
            }

            ($this->handler)(new AssignPermissionToRoleCommand(
                roleId: (string) $role->id(),
                permissionId: (string) $permissionId,
            ));

            $io->writeln(sprintf('  <info>✓ Asignado</info>    %s', $key));
            $assigned++;
        }

        $io->newLine();
        $io->success(sprintf('%d permisos asignados al rol "%s", %d ya existían.', $assigned, $roleName, $skipped));

        return Command::SUCCESS;
    }
}
