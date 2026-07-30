<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Console;

use App\Identity\Application\Command\CreateRole\CreateRoleCommand;
use App\Identity\Application\Command\CreateRole\CreateRoleHandler;
use App\Identity\Domain\Repositories\PermissionRepositoryInterface;
use App\Identity\Domain\Repositories\RoleRepositoryInterface;
use App\Identity\Domain\ValueObjects\PermissionKeyValueObject;
use App\Identity\Domain\ValueObjects\RoleSlugValueObject;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Siembra roles predefinidos del sistema.
 *
 * Uso:
 *   symfony console app:roles:seed
 *
 * Los roles ya existentes se omiten (idempotente).
 */
#[AsCommand(
    name: 'app:roles:seed',
    description: 'Siembra roles predefinidos del sistema.',
)]
final class SeedRolesCommand extends Command
{
    /**
     * @var array<string, array{name: string, permissions: string[]}>
     */
    private const ROLES = [
        'client_portal' => [
            'name' => 'Portal de Cliente',
            'permissions' => [
                'ticket.view',
                'ticket.view_all',
                'ticket.create',
                'ticket.add_comment',
            ],
        ],
    ];

    public function __construct(
        private readonly CreateRoleHandler $createRoleHandler,
        private readonly RoleRepositoryInterface $roleRepository,
        private readonly PermissionRepositoryInterface $permissionRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Siembra de roles — Orbis');

        $created = 0;
        $skipped = 0;

        foreach (self::ROLES as $slug => $config) {
            if ($this->roleRepository->findBySlug(new RoleSlugValueObject($slug)) !== null) {
                $io->writeln(sprintf('  <comment>↷ Existente</comment> %s (%s)', $slug, $config['name']));
                $skipped++;
                continue;
            }

            $permissionIds = [];
            foreach ($config['permissions'] as $key) {
                $permission = $this->permissionRepository->findByKey(new PermissionKeyValueObject($key));
                if ($permission !== null) {
                    $permissionIds[] = (string) $permission->id();
                } else {
                    $io->writeln(sprintf('  <comment>↷ Permiso no encontrado</comment> %s', $key));
                }
            }

            ($this->createRoleHandler)(new CreateRoleCommand(
                name: $config['name'],
                slug: $slug,
                permissionIds: $permissionIds,
            ));

            $io->writeln(sprintf('  <info>✓ Creado</info>    %s (%s)', $slug, $config['name']));
            $created++;
        }

        $io->newLine();
        $io->success(sprintf('%d roles creados, %d ya existían.', $created, $skipped));

        return Command::SUCCESS;
    }
}
