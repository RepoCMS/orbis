<?php

declare(strict_types=1);

namespace App\Organization\Presentation\Http;

use App\Identity\Application\Exception\RoleNotFoundException;
use App\Identity\Application\Exception\UsernameAlreadyExistsException;
use App\Identity\Application\Exception\UserNotFoundException;
use App\Organization\Application\Command\ProvisionEmployeeAccess\ProvisionEmployeeAccessCommand;
use App\Organization\Application\Command\ProvisionEmployeeAccess\ProvisionEmployeeAccessHandler;
use App\Identity\Application\Query\FindUserById\FindUserByIdHandler;
use App\Identity\Application\Query\FindUserById\FindUserByIdQuery;
use App\Identity\Application\Query\ListRoles\ListRolesHandler;
use App\Identity\Application\Query\ListRoles\ListRolesQuery;
use App\Identity\Application\Query\ListUsers\ListUsersHandler;
use App\Identity\Application\Query\ListUsers\ListUsersQuery;
use App\Organization\Application\Command\CreateEmployee\CreateEmployeeCommand;
use App\Organization\Application\Command\CreateEmployee\CreateEmployeeHandler;
use App\Organization\Application\Command\DeactivateEmployee\DeactivateEmployeeCommand;
use App\Organization\Application\Command\DeactivateEmployee\DeactivateEmployeeHandler;
use App\Organization\Application\Command\ScheduleEmployeeVacation\ScheduleEmployeeVacationCommand;
use App\Organization\Application\Command\ScheduleEmployeeVacation\ScheduleEmployeeVacationHandler;
use App\Organization\Application\Command\ScheduleEmployeeStandby\ScheduleEmployeeStandbyCommand;
use App\Organization\Application\Command\ScheduleEmployeeStandby\ScheduleEmployeeStandbyHandler;
use App\Organization\Application\Command\CancelEmployeeSpecialPeriod\CancelEmployeeSpecialPeriodCommand;
use App\Organization\Application\Command\CancelEmployeeSpecialPeriod\CancelEmployeeSpecialPeriodHandler;
use App\Organization\Application\Command\ReactivateEmployee\ReactivateEmployeeCommand;
use App\Organization\Application\Command\ReactivateEmployee\ReactivateEmployeeHandler;
use App\Organization\Application\Command\SoftDeleteEmployee\SoftDeleteEmployeeCommand;
use App\Organization\Application\Command\SoftDeleteEmployee\SoftDeleteEmployeeHandler;
use App\Organization\Application\Command\UpdateEmployee\UpdateEmployeeCommand;
use App\Organization\Application\Command\UpdateEmployee\UpdateEmployeeHandler;
use App\Organization\Application\Command\LinkUserToEmployee\LinkUserToEmployeeCommand;
use App\Organization\Application\Command\LinkUserToEmployee\LinkUserToEmployeeHandler;
use App\Organization\Application\Command\UnlinkUserFromEmployee\UnlinkUserFromEmployeeCommand;
use App\Organization\Application\Command\UnlinkUserFromEmployee\UnlinkUserFromEmployeeHandler;
use App\Organization\Application\Exception\EmployeeNotFoundException;
use App\Organization\Application\Query\FindEmployeeById\FindEmployeeByIdHandler;
use App\Organization\Application\Query\FindEmployeeById\FindEmployeeByIdQuery;
use App\Organization\Application\Query\ListEmployees\ListEmployeesHandler;
use App\Organization\Application\Query\ListEmployees\ListEmployeesQuery;
use App\Organization\Application\Query\ListEmployeeSpecialPeriods\ListEmployeeSpecialPeriodsHandler;
use App\Organization\Application\Query\ListEmployeeSpecialPeriods\ListEmployeeSpecialPeriodsQuery;
use App\Shared\Exceptions\DomainException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/employees')]
final class EmployeeController extends AbstractController
{
    #[IsGranted('employee.view_all')]
    #[Route('', name: 'employee_list', methods: ['GET'])]
    public function list(ListEmployeesHandler $listEmployees, ListUsersHandler $listUsers): Response
    {
        $usernamesById = [];
        foreach ($listUsers(new ListUsersQuery()) as $user) {
            $usernamesById[$user->id] = $user->username;
        }

        return $this->render('employee/list.html.twig', [
            'employees'     => $listEmployees(new ListEmployeesQuery()),
            'usernamesById' => $usernamesById,
        ]);
    }

    #[IsGranted('employee.create')]
    #[Route('/new', name: 'employee_new', methods: ['GET'])]
    public function newForm(): Response
    {
        return $this->render('employee/form.html.twig', [
            'errors' => [],
            'input'  => [],
        ]);
    }

    #[IsGranted('employee.create')]
    #[Route('', name: 'employee_create', methods: ['POST'])]
    public function create(
        Request $request,
        CreateEmployeeHandler $createEmployee,
    ): Response {
        $identificationNumber = trim((string) $request->request->get('identification_number', ''));
        $firstName            = trim((string) $request->request->get('first_name', ''));
        $lastName             = trim((string) $request->request->get('last_name', ''));
        $email                = trim((string) $request->request->get('email', ''));
        $phoneNumber          = trim((string) $request->request->get('phone_number', ''));
        $hiredAt              = trim((string) $request->request->get('hired_at', ''));
        $profile              = trim((string) $request->request->get('profile', 'administrative'));

        $errors = [];

        try {
            $employeeId = ($createEmployee)(new CreateEmployeeCommand(
                identificationNumber: $identificationNumber,
                firstName: $firstName,
                lastName: $lastName,
                email: $email,
                phoneNumber: $phoneNumber,
                hiredAt: $hiredAt,
                profile: $profile,
            ));

            $this->addFlash('success', sprintf('Empleado "%s %s" registrado. Ahora crea su acceso al sistema.', $firstName, $lastName));

            return $this->redirectToRoute('employee_show', ['id' => $employeeId, 'setup' => '1']);
        } catch (DomainException $e) {
            $errors[] = $e->getMessage();
        }

        return $this->render('employee/form.html.twig', [
            'errors' => $errors,
            'input'  => compact('identificationNumber', 'firstName', 'lastName', 'email', 'phoneNumber', 'hiredAt', 'profile'),
        ], new Response(null, Response::HTTP_UNPROCESSABLE_ENTITY));
    }

    #[IsGranted('employee.view')]
    #[Route('/{id}', name: 'employee_show', methods: ['GET'])]
    public function show(
        string $id,
        FindEmployeeByIdHandler $findEmployee,
        FindUserByIdHandler $findUser,
        ListUsersHandler $listUsers,
        ListRolesHandler $listRoles,
        ListEmployeeSpecialPeriodsHandler $listPeriods,
    ): Response {
        try {
            $employee = $findEmployee(new FindEmployeeByIdQuery($id));
        } catch (EmployeeNotFoundException) {
            throw $this->createNotFoundException('Empleado no encontrado.');
        }

        $linkedUser = null;
        if ($employee->userId !== null) {
            try {
                $linkedUser = $findUser(new FindUserByIdQuery($employee->userId));
            } catch (UserNotFoundException) {
                // usuario eliminado — mostrar como sin acceso
            }
        }

        $roles = $listRoles(new ListRolesQuery());
        $rolesById = [];
        foreach ($roles as $role) {
            $rolesById[$role->id] = $role->name;
        }

        $availableUsers = $linkedUser === null ? $listUsers(new ListUsersQuery()) : [];

        $specialPeriods = $listPeriods(new ListEmployeeSpecialPeriodsQuery($id));

        // Período de vacaciones activo (para mostrar botón de cancelar)
        $activeVacationPeriod = null;
        foreach ($specialPeriods as $p) {
            if ($p->type === 'vacation' && $p->isActive) {
                $activeVacationPeriod = $p;
                break;
            }
        }

        return $this->render('employee/show.html.twig', [
            'employee'             => $employee,
            'linkedUser'           => $linkedUser,
            'availableUsers'       => $availableUsers,
            'rolesById'            => $rolesById,
            'specialPeriods'       => $specialPeriods,
            'activeVacationPeriod' => $activeVacationPeriod,
        ]);
    }

    #[IsGranted('employee.update')]
    #[Route('/{id}/link-user', name: 'employee_link_user', methods: ['POST'])]
    public function linkUser(
        string $id,
        Request $request,
        LinkUserToEmployeeHandler $linkUserToEmployee,
    ): Response {
        $userId = trim((string) $request->request->get('user_id', ''));

        try {
            ($linkUserToEmployee)(new LinkUserToEmployeeCommand(
                employeeId: $id,
                userId: $userId,
            ));

            $this->addFlash('success', 'Usuario vinculado al empleado exitosamente.');
        } catch (EmployeeNotFoundException) {
            throw $this->createNotFoundException('Empleado no encontrado.');
        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('employee_show', ['id' => $id]);
    }

    #[IsGranted('employee.update')]
    #[Route('/{id}/unlink-user', name: 'employee_unlink_user', methods: ['POST'])]
    public function unlinkUser(
        string $id,
        UnlinkUserFromEmployeeHandler $unlinkUserFromEmployee,
    ): Response {
        try {
            ($unlinkUserFromEmployee)(new UnlinkUserFromEmployeeCommand(
                employeeId: $id,
            ));

            $this->addFlash('success', 'Usuario desvinculado del empleado exitosamente.');
        } catch (EmployeeNotFoundException) {
            throw $this->createNotFoundException('Empleado no encontrado.');
        }

        return $this->redirectToRoute('employee_show', ['id' => $id]);
    }

    #[IsGranted('employee.update')]
    #[Route('/{id}/edit', name: 'employee_edit', methods: ['POST'])]
    public function edit(
        string $id,
        Request $request,
        UpdateEmployeeHandler $updateEmployee,
        FindEmployeeByIdHandler $findEmployee,
    ): Response {
        $firstName   = trim((string) $request->request->get('first_name', ''));
        $lastName    = trim((string) $request->request->get('last_name', ''));
        $email       = trim((string) $request->request->get('email', ''));
        $phoneNumber = trim((string) $request->request->get('phone_number', ''));
        $profile     = trim((string) $request->request->get('profile', 'administrative'));

        $errors = [];

        try {
            ($updateEmployee)(new UpdateEmployeeCommand(
                employeeId: $id,
                firstName: $firstName,
                lastName: $lastName,
                email: $email,
                phoneNumber: $phoneNumber,
                profile: $profile,
            ));

            $this->addFlash('success', 'Datos del empleado actualizados.');
            return $this->redirectToRoute('employee_show', ['id' => $id]);
        } catch (EmployeeNotFoundException) {
            throw $this->createNotFoundException('Empleado no encontrado.');
        } catch (DomainException $e) {
            $errors[] = $e->getMessage();
        }

        try {
            $employee = $findEmployee(new FindEmployeeByIdQuery($id));
        } catch (EmployeeNotFoundException) {
            throw $this->createNotFoundException('Empleado no encontrado.');
        }

        return $this->render('employee/show.html.twig', [
            'employee'       => $employee,
            'linkedUser'     => null,
            'availableUsers' => [],
            'rolesById'      => [],
            'errors'         => $errors,
            'editMode'       => true,
            'editInput'      => compact('firstName', 'lastName', 'email', 'phoneNumber'),
        ], new Response(null, Response::HTTP_UNPROCESSABLE_ENTITY));
    }

    #[IsGranted('user.create')]
    #[Route('/{id}/create-user', name: 'employee_create_user', methods: ['POST'])]
    public function createUser(
        string $id,
        Request $request,
        ProvisionEmployeeAccessHandler $provision,
        FindEmployeeByIdHandler $findEmployee,
        ListRolesHandler $listRoles,
        ListUsersHandler $listUsers,
    ): Response {
        try {
            $employee = $findEmployee(new FindEmployeeByIdQuery($id));
        } catch (EmployeeNotFoundException) {
            throw $this->createNotFoundException('Empleado no encontrado.');
        }

        $username        = trim((string) $request->request->get('username', ''));
        $password        = (string) $request->request->get('password', '');
        $passwordConfirm = (string) $request->request->get('password_confirm', '');
        $roleId          = trim((string) $request->request->get('role_id', ''));

        $errors = [];

        if ($password !== $passwordConfirm) {
            $errors[] = 'Las contraseñas no coinciden.';
        }

        if ($errors === []) {
            try {
                ($provision)(new ProvisionEmployeeAccessCommand(
                    employeeId: $id,
                    username: $username,
                    rawPassword: $password,
                    roleId: $roleId,
                ));

                $this->addFlash('success', 'Acceso al sistema creado y vinculado al empleado.');
                return $this->redirectToRoute('employee_show', ['id' => $id]);
            } catch (UsernameAlreadyExistsException $e) {
                $errors[] = $e->getMessage();
            } catch (RoleNotFoundException) {
                $errors[] = 'El rol seleccionado no existe.';
            } catch (DomainException $e) {
                $errors[] = $e->getMessage();
            }
        }

        $roles = $listRoles(new ListRolesQuery());
        $rolesById = [];
        foreach ($roles as $role) {
            $rolesById[$role->id] = $role->name;
        }

        return $this->render('employee/show.html.twig', [
            'employee'        => $employee,
            'linkedUser'      => null,
            'availableUsers'  => $listUsers(new ListUsersQuery()),
            'rolesById'       => $rolesById,
            'errors'          => $errors,
            'createUserMode'  => true,
            'createUserInput' => compact('username', 'roleId'),
        ], new Response(null, Response::HTTP_UNPROCESSABLE_ENTITY));
    }

    #[IsGranted('employee.update')]
    #[Route('/{id}/deactivate', name: 'employee_deactivate', methods: ['POST'])]
    public function deactivate(string $id, DeactivateEmployeeHandler $deactivateEmployee): Response
    {
        try {
            ($deactivateEmployee)(new DeactivateEmployeeCommand($id));
            $this->addFlash('success', 'Empleado desactivado.');
        } catch (EmployeeNotFoundException) {
            throw $this->createNotFoundException('Empleado no encontrado.');
        } catch (DomainException $e) {
            $this->addFlash('warning', $e->getMessage());
        }

        return $this->redirectToRoute('employee_show', ['id' => $id]);
    }

    #[IsGranted('employee.update')]
    #[Route('/{id}/reactivate', name: 'employee_reactivate', methods: ['POST'])]
    public function reactivate(string $id, ReactivateEmployeeHandler $reactivateEmployee): Response
    {
        try {
            ($reactivateEmployee)(new ReactivateEmployeeCommand($id));
            $this->addFlash('success', 'Empleado reactivado.');
        } catch (EmployeeNotFoundException) {
            throw $this->createNotFoundException('Empleado no encontrado.');
        } catch (DomainException $e) {
            $this->addFlash('warning', $e->getMessage());
        }

        return $this->redirectToRoute('employee_show', ['id' => $id]);
    }

    #[IsGranted('employee.delete')]
    #[Route('/{id}/delete', name: 'employee_delete', methods: ['POST'])]
    public function delete(string $id, SoftDeleteEmployeeHandler $deleteEmployee): Response
    {
        try {
            ($deleteEmployee)(new SoftDeleteEmployeeCommand($id));
            $this->addFlash('success', 'Empleado eliminado lógicamente.');
        } catch (EmployeeNotFoundException) {
            throw $this->createNotFoundException('Empleado no encontrado.');
        } catch (DomainException $e) {
            $this->addFlash('warning', $e->getMessage());

            return $this->redirectToRoute('employee_show', ['id' => $id]);
        }

        return $this->redirectToRoute('employee_list');
    }

    #[IsGranted('employee.update')]
    #[Route('/{id}/vacation/schedule', name: 'employee_vacation_schedule', methods: ['POST'])]
    public function scheduleVacation(string $id, Request $request, ScheduleEmployeeVacationHandler $handler): Response
    {
        $startedAt = trim((string) $request->request->get('started_at', ''));
        $endedAt   = trim((string) $request->request->get('ended_at', ''));

        try {
            ($handler)(new ScheduleEmployeeVacationCommand($id, $startedAt, $endedAt));
            $this->addFlash('success', 'Vacaciones programadas correctamente.');
        } catch (EmployeeNotFoundException) {
            throw $this->createNotFoundException('Empleado no encontrado.');
        } catch (DomainException $e) {
            $this->addFlash('warning', $e->getMessage());
        }

        return $this->redirectToRoute('employee_show', ['id' => $id]);
    }

    #[IsGranted('employee.update')]
    #[Route('/{id}/standby/schedule', name: 'employee_standby_schedule', methods: ['POST'])]
    public function scheduleStandby(string $id, Request $request, ScheduleEmployeeStandbyHandler $handler): Response
    {
        $startedAt = trim((string) $request->request->get('started_at', ''));
        $endedAt   = trim((string) $request->request->get('ended_at', ''));
        $notes     = trim((string) $request->request->get('notes', '')) ?: null;

        try {
            ($handler)(new ScheduleEmployeeStandbyCommand($id, $startedAt, $endedAt, $notes));
            $this->addFlash('success', 'Standby programado correctamente.');
        } catch (EmployeeNotFoundException) {
            throw $this->createNotFoundException('Empleado no encontrado.');
        } catch (DomainException $e) {
            $this->addFlash('warning', $e->getMessage());
        }

        return $this->redirectToRoute('employee_show', ['id' => $id]);
    }

    #[IsGranted('employee.update')]
    #[Route('/{id}/periods/{periodId}/cancel', name: 'employee_period_cancel', methods: ['POST'])]
    public function cancelPeriod(string $id, string $periodId, CancelEmployeeSpecialPeriodHandler $handler): Response
    {
        try {
            ($handler)(new CancelEmployeeSpecialPeriodCommand($periodId, $id));
            $this->addFlash('success', 'Período cancelado.');
        } catch (EmployeeNotFoundException) {
            throw $this->createNotFoundException('Empleado no encontrado.');
        } catch (DomainException $e) {
            $this->addFlash('warning', $e->getMessage());
        }

        return $this->redirectToRoute('employee_show', ['id' => $id]);
    }
}

