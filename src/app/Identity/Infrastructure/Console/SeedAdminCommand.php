<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Console;

use App\Identity\Application\Command\CreateRole\CreateRoleCommand;
use App\Identity\Application\Command\CreateRole\CreateRoleHandler;
use App\Identity\Application\Command\RegisterUser\RegisterUserCommand;
use App\Identity\Application\Command\RegisterUser\RegisterUserHandler;
use App\Identity\Application\Command\SyncRolePermissions\SyncRolePermissionsCommand;
use App\Identity\Application\Command\SyncRolePermissions\SyncRolePermissionsHandler;
use App\Identity\Domain\Enums\UserStatus;
use App\Identity\Domain\Repositories\PermissionRepositoryInterface;
use App\Identity\Domain\Repositories\RoleRepositoryInterface;
use App\Identity\Domain\Repositories\UserRepositoryInterface;
use App\Identity\Domain\ValueObjects\UserIdValueObject;
use App\Identity\Domain\ValueObjects\UsernameValueObject;
use App\Identity\Domain\ValueObjects\RoleNameValueObject;
use App\Organization\Application\Command\CreateEmployee\CreateEmployeeCommand;
use App\Organization\Application\Command\CreateEmployee\CreateEmployeeHandler;
use App\Organization\Application\Command\LinkUserToEmployee\LinkUserToEmployeeCommand;
use App\Organization\Application\Command\LinkUserToEmployee\LinkUserToEmployeeHandler;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed:admin',
    description: 'Crea empleado admin + rol Admin + usuario administrador inicial.',
)]
final class SeedAdminCommand extends Command
{
    public function __construct(
        private readonly CreateRoleHandler $createRoleHandler,
        private readonly RegisterUserHandler $registerUserHandler,
        private readonly CreateEmployeeHandler $createEmployeeHandler,
        private readonly LinkUserToEmployeeHandler $linkUserHandler,
        private readonly SyncRolePermissionsHandler $syncRolePermissionsHandler,
        private readonly PermissionRepositoryInterface $permissionRepo,
        private readonly RoleRepositoryInterface $roleRepo,
        private readonly UserRepositoryInterface $userRepo,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('username', 'u', InputOption::VALUE_REQUIRED, 'Nombre de usuario del admin', 'admin')
            ->addOption('password', 'p', InputOption::VALUE_REQUIRED, 'Contraseña del admin')
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'Email del admin', 'admin@orbis.cms.com.ec');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $username = $input->getOption('username');
        $password = $input->getOption('password');
        $email = $input->getOption('email');

        if (!$password || strlen($password) < 6) {
            $io->error('La contraseña debe tener al menos 6 caracteres.');
            return Command::FAILURE;
        }

        $io->title('Seed: Admin Role + Employee + User');

        if ($this->roleRepo->findByName(new RoleNameValueObject('Administrador')) === null) {
            $io->section('Creando rol Administrador...');
            $allPermissions = $this->permissionRepo->findAll();
            $allPermissionIds = array_map(static fn($p) => (string) $p->id(), $allPermissions);

            ($this->createRoleHandler)(new CreateRoleCommand(
                name: 'Administrador',
                permissionIds: $allPermissionIds,
            ));
            $io->success('Rol Administrador creado con ' . count($allPermissionIds) . ' permisos.');
        } else {
            $io->note('Rol Administrador ya existe — sincronizando permisos...');
            $adminRole = $this->roleRepo->findByName(new RoleNameValueObject('Administrador'));
            $allPermissions = $this->permissionRepo->findAll();
            $allPermissionIds = array_map(static fn($p) => (string) $p->id(), $allPermissions);
            ($this->syncRolePermissionsHandler)(new SyncRolePermissionsCommand(
                roleId: (string) $adminRole->id(),
                permissionIds: $allPermissionIds,
            ));
            $io->success('Permisos sincronizados (' . count($allPermissionIds) . ').');
        }

        $adminRole = $this->roleRepo->findByName(new RoleNameValueObject('Administrador'));
        if ($adminRole === null) {
            $io->error('No se pudo encontrar el rol admin.');
            return Command::FAILURE;
        }

        $existingUser = $this->userRepo->findByUsername(new UsernameValueObject($username));
        if ($existingUser !== null) {
            if ($existingUser->status() === UserStatus::UNVERIFIED) {
                $existingUser->activate();
                $this->userRepo->save($existingUser);
                $io->success("Usuario '$username' activado correctamente.");
            } else {
                $io->warning("El usuario '$username' ya existe y está activo. Se omitirá la creación.");
            }
            $io->note('Para crear un admin distinto usa: --username otro --password clave');
            return Command::SUCCESS;
        }

        $io->section('Creando empleado administrador...');
        $employeeId = ($this->createEmployeeHandler)(new CreateEmployeeCommand(
            identificationNumber: '1710034065',
            firstName: 'Admin',
            lastName: 'Sistema',
            email: $email,
            phoneNumber: '0999999999',
            hiredAt: date('Y-m-d'),
            profile: 'administrative',
        ));
        $io->success("Empleado administrador creado (ID: $employeeId)");

        $io->section('Creando usuario administrador...');
        $userId = ($this->registerUserHandler)(new RegisterUserCommand(
            username: $username,
            rawPassword: $password,
            roleId: (string) $adminRole->id(),
        ));

        $user = $this->userRepo->findById(new UserIdValueObject($userId));
        if ($user !== null && $user->status() === UserStatus::UNVERIFIED) {
            $user->activate();
            $this->userRepo->save($user);
            $io->text('Cuenta activada automáticamente.');
        }
        $io->success("Usuario creado (ID: $userId)");

        $io->section('Vinculando usuario al empleado...');
        ($this->linkUserHandler)(new LinkUserToEmployeeCommand(
            employeeId: $employeeId,
            userId: $userId,
        ));
        $io->success("Usuario '$username' vinculado al empleado administrador.");

        $io->success([
            'Admin creado exitosamente.',
            "  Usuario: $username",
            "  Password: $password",
            "  Email: $email",
        ]);

        return Command::SUCCESS;
    }
}
