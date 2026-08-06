<?php

declare(strict_types=1);

namespace App\Organization\Presentation\Http;

use App\Organization\Application\Command\ApproveOvertime\ApproveOvertimeCommand;
use App\Organization\Application\Command\ApproveOvertime\ApproveOvertimeHandler;
use App\Organization\Application\Command\ApproveVacationRequest\ApproveVacationRequestCommand;
use App\Organization\Application\Command\ApproveVacationRequest\ApproveVacationRequestHandler;
use App\Organization\Application\Command\CancelVacationRequest\CancelVacationRequestCommand;
use App\Organization\Application\Command\CancelVacationRequest\CancelVacationRequestHandler;
use App\Organization\Application\Command\CompleteEmployeeSpecialPeriod\CompleteEmployeeSpecialPeriodCommand;
use App\Organization\Application\Command\CompleteEmployeeSpecialPeriod\CompleteEmployeeSpecialPeriodHandler;
use App\Organization\Application\Command\CorrectOvertime\CorrectOvertimeCommand;
use App\Organization\Application\Command\CorrectOvertime\CorrectOvertimeHandler;
use App\Organization\Application\Command\CreateEmployee\CreateEmployeeCommand;
use App\Organization\Application\Command\CreateEmployee\CreateEmployeeHandler;
use App\Organization\Application\Command\CreateVacationRequest\CreateVacationRequestCommand;
use App\Organization\Application\Command\CreateVacationRequest\CreateVacationRequestHandler;
use App\Organization\Application\Command\DeactivateEmployee\DeactivateEmployeeCommand;
use App\Organization\Application\Command\DeactivateEmployee\DeactivateEmployeeHandler;
use App\Organization\Application\Command\EditEmployeeSpecialPeriod\EditEmployeeSpecialPeriodCommand;
use App\Organization\Application\Command\EditEmployeeSpecialPeriod\EditEmployeeSpecialPeriodHandler;
use App\Organization\Application\Command\ProvisionEmployeeAccess\ProvisionEmployeeAccessCommand;
use App\Organization\Application\Command\ProvisionEmployeeAccess\ProvisionEmployeeAccessHandler;
use App\Organization\Application\Command\ReactivateEmployee\ReactivateEmployeeCommand;
use App\Organization\Application\Command\ReactivateEmployee\ReactivateEmployeeHandler;
use App\Organization\Application\Command\RegisterOvertime\RegisterOvertimeCommand;
use App\Organization\Application\Command\RegisterOvertime\RegisterOvertimeHandler;
use App\Organization\Application\Command\RejectOvertime\RejectOvertimeCommand;
use App\Organization\Application\Command\RejectOvertime\RejectOvertimeHandler;
use App\Organization\Application\Command\RejectVacationRequest\RejectVacationRequestCommand;
use App\Organization\Application\Command\RejectVacationRequest\RejectVacationRequestHandler;
use App\Organization\Application\Command\RequestOvertimeCorrection\RequestOvertimeCorrectionCommand;
use App\Organization\Application\Command\RequestOvertimeCorrection\RequestOvertimeCorrectionHandler;
use App\Organization\Application\Command\ScheduleEmployeeStandby\ScheduleEmployeeStandbyCommand;
use App\Organization\Application\Command\ScheduleEmployeeStandby\ScheduleEmployeeStandbyHandler;
use App\Organization\Application\Command\ScheduleEmployeeVacation\ScheduleEmployeeVacationCommand;
use App\Organization\Application\Command\ScheduleEmployeeVacation\ScheduleEmployeeVacationHandler;
use App\Organization\Application\Command\SoftDeleteEmployee\SoftDeleteEmployeeCommand;
use App\Organization\Application\Command\SoftDeleteEmployee\SoftDeleteEmployeeHandler;
use App\Organization\Application\Command\UnlinkUserFromEmployee\UnlinkUserFromEmployeeCommand;
use App\Organization\Application\Command\UnlinkUserFromEmployee\UnlinkUserFromEmployeeHandler;
use App\Organization\Application\Command\UpdateEmployee\UpdateEmployeeCommand;
use App\Organization\Application\Command\UpdateEmployee\UpdateEmployeeHandler;
use App\Organization\Application\Exception\EmployeeNotFoundException;
use App\Organization\Application\Exception\TerminationBlockedException;
use App\Organization\Application\Query\FindEmployeeById\FindEmployeeByIdHandler;
use App\Organization\Application\Query\FindEmployeeById\FindEmployeeByIdQuery;
use App\Organization\Application\Query\GetOrganizationDashboard\GetOrganizationDashboardHandler;
use App\Organization\Application\Query\GetOrganizationDashboard\GetOrganizationDashboardQuery;
use App\Organization\Application\Query\ListEmployees\ListEmployeesHandler;
use App\Organization\Application\Query\ListEmployees\ListEmployeesQuery;
use App\Organization\Application\Query\ListEmployeeSpecialPeriods\ListEmployeeSpecialPeriodsHandler;
use App\Organization\Application\Query\ListEmployeeSpecialPeriods\ListEmployeeSpecialPeriodsQuery;
use App\Organization\Application\Query\ListOvertimeRecordsByEmployee\ListOvertimeRecordsByEmployeeHandler;
use App\Organization\Application\Query\ListOvertimeRecordsByEmployee\ListOvertimeRecordsByEmployeeQuery;
use App\Organization\Application\Query\ListVacationRequestsByEmployee\ListVacationRequestsByEmployeeHandler;
use App\Organization\Application\Query\ListVacationRequestsByEmployee\ListVacationRequestsByEmployeeQuery;
use App\Shared\Exceptions\DomainException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/organization')]
final class ApiOrganizationController extends AbstractController
{
    #[IsGranted('employee.view')]
    #[Route('/employees', name: 'api_organization_employees_list', methods: ['GET'])]
    public function listEmployees(
        Request $request,
        ListEmployeesHandler $handler,
    ): JsonResponse {
        $result = $handler(new ListEmployeesQuery(
            profile:       $request->query->get('profile') ?: null,
            status:        $request->query->get('status') ?: null,
            serviceZoneId: $request->query->get('serviceZoneId') ?: null,
            q:             $request->query->get('q') ?: null,
            page:          (int) $request->query->get('page', '1'),
            limit:         (int) $request->query->get('limit', '20'),
        ));

        return $this->json($result);
    }

    #[IsGranted('employee.create')]
    #[Route('/employees', name: 'api_organization_employees_create', methods: ['POST'])]
    public function createEmployee(
        Request $request,
        CreateEmployeeHandler $handler,
    ): JsonResponse {
        $payload = $request->toArray();

        try {
            $employeeId = ($handler)(new CreateEmployeeCommand(
                identificationNumber: trim((string) ($payload['identificationNumber'] ?? '')),
                firstName:            trim((string) ($payload['firstName'] ?? '')),
                lastName:             trim((string) ($payload['lastName'] ?? '')),
                email:                trim((string) ($payload['email'] ?? '')),
                phoneNumber:          trim((string) ($payload['phoneNumber'] ?? '')),
                hiredAt:              trim((string) ($payload['hiredAt'] ?? '')),
                profile:              trim((string) ($payload['profile'] ?? 'administrative')),
            ));

            return $this->json(['data' => ['id' => $employeeId, 'hasUser' => false]], Response::HTTP_CREATED);
        } catch (DomainException | \InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('employee.view')]
    #[Route('/employees/{id}', name: 'api_organization_employees_show', methods: ['GET'])]
    public function showEmployee(
        string $id,
        FindEmployeeByIdHandler $handler,
        ListEmployeeSpecialPeriodsHandler $listPeriods,
    ): JsonResponse {
        try {
            $employee = $handler(new FindEmployeeByIdQuery($id));
            $periods = $listPeriods(new ListEmployeeSpecialPeriodsQuery($id));

            return $this->json([
                'data' => [
                    'employee'       => $employee,
                    'specialPeriods' => $periods,
                ],
            ]);
        } catch (EmployeeNotFoundException) {
            return $this->json(['error' => 'Empleado no encontrado.'], Response::HTTP_NOT_FOUND);
        }
    }

    #[IsGranted('employee.update')]
    #[Route('/employees/{id}', name: 'api_organization_employees_update', methods: ['PATCH'])]
    public function updateEmployee(
        string $id,
        Request $request,
        UpdateEmployeeHandler $handler,
    ): JsonResponse {
        $payload = $request->toArray();

        try {
            ($handler)(new UpdateEmployeeCommand(
                employeeId:   $id,
                firstName:    trim((string) ($payload['firstName'] ?? '')),
                lastName:     trim((string) ($payload['lastName'] ?? '')),
                email:        trim((string) ($payload['email'] ?? '')),
                phoneNumber:  trim((string) ($payload['phoneNumber'] ?? '')),
                profile:      trim((string) ($payload['profile'] ?? 'administrative')),
            ));

            return $this->json(['data' => ['id' => $id]]);
        } catch (EmployeeNotFoundException) {
            return $this->json(['error' => 'Empleado no encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('employee.manage')]
    #[Route('/employees/{id}/terminate', name: 'api_organization_employees_terminate', methods: ['POST'])]
    public function terminateEmployee(
        string $id,
        DeactivateEmployeeHandler $handler,
    ): JsonResponse {
        try {
            ($handler)(new DeactivateEmployeeCommand($id));

            return $this->json(['data' => ['id' => $id, 'status' => 'inactive']]);
        } catch (EmployeeNotFoundException) {
            return $this->json(['error' => 'Empleado no encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (TerminationBlockedException $e) {
            return $this->json([
                'error'        => $e->getMessage(),
                'pendingItems' => $e->pendingItems,
            ], Response::HTTP_CONFLICT);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('employee.update')]
    #[Route('/employees/{id}/reactivate', name: 'api_organization_employees_reactivate', methods: ['POST'])]
    public function reactivateEmployee(
        string $id,
        ReactivateEmployeeHandler $handler,
    ): JsonResponse {
        try {
            ($handler)(new ReactivateEmployeeCommand($id));

            return $this->json(['data' => ['id' => $id, 'status' => 'active']]);
        } catch (EmployeeNotFoundException) {
            return $this->json(['error' => 'Empleado no encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('employee.delete')]
    #[Route('/employees/{id}', name: 'api_organization_employees_delete', methods: ['DELETE'])]
    public function deleteEmployee(
        string $id,
        SoftDeleteEmployeeHandler $handler,
    ): JsonResponse {
        try {
            ($handler)(new SoftDeleteEmployeeCommand($id));

            return $this->json(null, Response::HTTP_NO_CONTENT);
        } catch (EmployeeNotFoundException) {
            return $this->json(['error' => 'Empleado no encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('employee.view')]
    #[Route('/employees/{id}/special-periods', name: 'api_organization_employees_periods_list', methods: ['GET'])]
    public function listSpecialPeriods(
        string $id,
        ListEmployeeSpecialPeriodsHandler $handler,
    ): JsonResponse {
        return $this->json([
            'data' => $handler(new ListEmployeeSpecialPeriodsQuery($id)),
        ]);
    }

    #[IsGranted('employee.update')]
    #[Route('/employees/{id}/special-periods', name: 'api_organization_employees_periods_create', methods: ['POST'])]
    public function createSpecialPeriod(
        string $id,
        Request $request,
        ScheduleEmployeeVacationHandler $vacationHandler,
        ScheduleEmployeeStandbyHandler $standbyHandler,
    ): JsonResponse {
        $payload = $request->toArray();
        $type = $payload['type'] ?? null;

        if (!in_array($type, ['vacation', 'standby'], true)) {
            return $this->json(
                ['error' => 'El campo type debe ser "vacation" o "standby".'],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        try {
            if ($type === 'vacation') {
                ($vacationHandler)(new ScheduleEmployeeVacationCommand(
                    employeeId: $id,
                    startedAt:  trim((string) ($payload['startDate'] ?? '')),
                    endedAt:    trim((string) ($payload['endDate'] ?? '')),
                ));
            } else {
                ($standbyHandler)(new ScheduleEmployeeStandbyCommand(
                    employeeId: $id,
                    startedAt:  trim((string) ($payload['startDate'] ?? '')),
                    endedAt:    trim((string) ($payload['endDate'] ?? '')),
                    notes:      isset($payload['notes']) ? trim((string) $payload['notes']) : null,
                ));
            }

            return $this->json(['data' => ['id' => $id, 'type' => $type]], Response::HTTP_CREATED);
        } catch (EmployeeNotFoundException) {
            return $this->json(['error' => 'Empleado no encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('employee.update')]
    #[Route('/employees/{id}/special-periods/{periodId}', name: 'api_organization_employees_periods_edit', methods: ['PATCH'])]
    public function editSpecialPeriod(
        string $id,
        string $periodId,
        Request $request,
        EditEmployeeSpecialPeriodHandler $handler,
    ): JsonResponse {
        $payload = $request->toArray();

        $type = $payload['type'] ?? null;
        if (!in_array($type, ['vacation', 'standby'], true)) {
            return $this->json(
                ['error' => 'El campo type debe ser "vacation" o "standby".'],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        try {
            ($handler)(new EditEmployeeSpecialPeriodCommand(
                periodId:   $periodId,
                employeeId: $id,
                startedAt:  trim((string) ($payload['startedAt'] ?? '')),
                endedAt:    trim((string) ($payload['endedAt'] ?? '')),
                type:       $type,
                notes:      isset($payload['notes']) ? trim((string) $payload['notes']) : null,
            ));

            return $this->json(['data' => ['id' => $periodId]]);
        } catch (EmployeeNotFoundException) {
            return $this->json(['error' => 'Empleado no encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('employee.update')]
    #[Route('/employees/{id}/special-periods/{periodId}/complete', name: 'api_organization_employees_periods_complete', methods: ['POST'])]
    public function completeSpecialPeriod(
        string $id,
        string $periodId,
        Request $request,
        CompleteEmployeeSpecialPeriodHandler $handler,
    ): JsonResponse {
        $payload = $request->toArray();

        try {
            ($handler)(new CompleteEmployeeSpecialPeriodCommand(
                periodId:     $periodId,
                employeeId:   $id,
                realEndDate:  isset($payload['realEndDate']) ? trim((string) $payload['realEndDate']) : null,
            ));

            return $this->json(['data' => ['id' => $periodId, 'status' => 'completed']]);
        } catch (EmployeeNotFoundException) {
            return $this->json(['error' => 'Empleado no encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('employee.create')]
    #[Route('/employees/{id}/vacation-requests', name: 'api_organization_employees_vacation_requests_create', methods: ['POST'])]
    public function createVacationRequest(
        string $id,
        Request $request,
        CreateVacationRequestHandler $handler,
    ): JsonResponse {
        $payload = $request->toArray();

        try {
            $requestId = ($handler)(new CreateVacationRequestCommand(
                employeeId: $id,
                startDate:  trim((string) ($payload['startDate'] ?? '')),
                endDate:    trim((string) ($payload['endDate'] ?? '')),
            ));

            return $this->json(['data' => ['id' => $requestId]], Response::HTTP_CREATED);
        } catch (EmployeeNotFoundException) {
            return $this->json(['error' => 'Empleado no encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('employee.view')]
    #[Route('/employees/{id}/vacation-requests', name: 'api_organization_employees_vacation_requests_list', methods: ['GET'])]
    public function listVacationRequests(
        string $id,
        ListVacationRequestsByEmployeeHandler $handler,
    ): JsonResponse {
        return $this->json([
            'data' => $handler(new ListVacationRequestsByEmployeeQuery($id)),
        ]);
    }

    #[IsGranted('employee.update')]
    #[Route('/vacation-requests/{id}/approve', name: 'api_organization_vacation_requests_approve', methods: ['POST'])]
    public function approveVacationRequest(
        string $id,
        Request $request,
        ApproveVacationRequestHandler $handler,
    ): JsonResponse {
        $payload = $request->toArray();
        $reviewedBy = trim((string) ($payload['reviewedBy'] ?? ''));

        if ($reviewedBy === '') {
            return $this->json(
                ['error' => 'El campo reviewedBy es obligatorio.'],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        try {
            ($handler)(new ApproveVacationRequestCommand(
                requestId:  $id,
                reviewedBy: $reviewedBy,
            ));

            return $this->json(['data' => ['id' => $id, 'status' => 'approved']]);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('employee.update')]
    #[Route('/vacation-requests/{id}/reject', name: 'api_organization_vacation_requests_reject', methods: ['POST'])]
    public function rejectVacationRequest(
        string $id,
        Request $request,
        RejectVacationRequestHandler $handler,
    ): JsonResponse {
        $payload = $request->toArray();
        $reviewedBy = trim((string) ($payload['reviewedBy'] ?? ''));
        $reason = trim((string) ($payload['reason'] ?? ''));

        if ($reviewedBy === '' || $reason === '') {
            return $this->json(
                ['error' => 'Los campos reviewedBy y reason son obligatorios.'],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        try {
            ($handler)(new RejectVacationRequestCommand(
                requestId:  $id,
                reviewedBy: $reviewedBy,
                reason:     $reason,
            ));

            return $this->json(['data' => ['id' => $id, 'status' => 'rejected']]);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('employee.update')]
    #[Route('/vacation-requests/{id}/cancel', name: 'api_organization_vacation_requests_cancel', methods: ['POST'])]
    public function cancelVacationRequest(
        string $id,
        Request $request,
        CancelVacationRequestHandler $handler,
    ): JsonResponse {
        $payload = $request->toArray();
        $employeeId = trim((string) ($payload['employeeId'] ?? ''));

        if ($employeeId === '') {
            return $this->json(
                ['error' => 'El campo employeeId es obligatorio.'],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        try {
            ($handler)(new CancelVacationRequestCommand(
                requestId:  $id,
                employeeId: $employeeId,
            ));

            return $this->json(['data' => ['id' => $id, 'status' => 'cancelled']]);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('employee.create')]
    #[Route('/employees/{id}/overtime', name: 'api_organization_employees_overtime_create', methods: ['POST'])]
    public function registerOvertime(
        string $id,
        Request $request,
        RegisterOvertimeHandler $handler,
    ): JsonResponse {
        $payload = $request->toArray();

        try {
            $recordId = ($handler)(new RegisterOvertimeCommand(
                employeeId:  $id,
                workedAt:    trim((string) ($payload['workedAt'] ?? '')),
                startTime:   trim((string) ($payload['startTime'] ?? '')),
                endTime:     trim((string) ($payload['endTime'] ?? '')),
                reason:      trim((string) ($payload['reason'] ?? '')),
                evidenceUrl: isset($payload['evidenceUrl']) ? trim((string) $payload['evidenceUrl']) : null,
            ));

            return $this->json(['data' => ['id' => $recordId]], Response::HTTP_CREATED);
        } catch (EmployeeNotFoundException) {
            return $this->json(['error' => 'Empleado no encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('employee.view')]
    #[Route('/employees/{id}/overtime', name: 'api_organization_employees_overtime_list', methods: ['GET'])]
    public function listOvertimeRecords(
        string $id,
        ListOvertimeRecordsByEmployeeHandler $handler,
    ): JsonResponse {
        return $this->json([
            'data' => $handler(new ListOvertimeRecordsByEmployeeQuery($id)),
        ]);
    }

    #[IsGranted('employee.update')]
    #[Route('/overtime-records/{id}/approve', name: 'api_organization_overtime_approve', methods: ['POST'])]
    public function approveOvertime(
        string $id,
        Request $request,
        ApproveOvertimeHandler $handler,
    ): JsonResponse {
        $payload = $request->toArray();
        $reviewedBy = trim((string) ($payload['reviewedBy'] ?? ''));

        if ($reviewedBy === '') {
            return $this->json(
                ['error' => 'El campo reviewedBy es obligatorio.'],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        try {
            ($handler)(new ApproveOvertimeCommand(
                overtimeRecordId: $id,
                reviewedBy:       $reviewedBy,
            ));

            return $this->json(['data' => ['id' => $id, 'status' => 'approved']]);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('employee.update')]
    #[Route('/overtime-records/{id}/reject', name: 'api_organization_overtime_reject', methods: ['POST'])]
    public function rejectOvertime(
        string $id,
        Request $request,
        RejectOvertimeHandler $handler,
    ): JsonResponse {
        $payload = $request->toArray();
        $reviewedBy = trim((string) ($payload['reviewedBy'] ?? ''));
        $reason = trim((string) ($payload['reason'] ?? ''));

        if ($reviewedBy === '' || $reason === '') {
            return $this->json(
                ['error' => 'Los campos reviewedBy y reason son obligatorios.'],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        try {
            ($handler)(new RejectOvertimeCommand(
                overtimeRecordId: $id,
                reviewedBy:       $reviewedBy,
                reason:           $reason,
            ));

            return $this->json(['data' => ['id' => $id, 'status' => 'rejected']]);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('employee.update')]
    #[Route('/overtime-records/{id}/request-correction', name: 'api_organization_overtime_request_correction', methods: ['POST'])]
    public function requestOvertimeCorrection(
        string $id,
        Request $request,
        RequestOvertimeCorrectionHandler $handler,
    ): JsonResponse {
        $payload = $request->toArray();
        $reviewedBy = trim((string) ($payload['reviewedBy'] ?? ''));

        if ($reviewedBy === '') {
            return $this->json(
                ['error' => 'El campo reviewedBy es obligatorio.'],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        try {
            ($handler)(new RequestOvertimeCorrectionCommand(
                overtimeRecordId: $id,
                reviewedBy:       $reviewedBy,
                correctionHint:   isset($payload['correctionHint']) ? trim((string) $payload['correctionHint']) : null,
            ));

            return $this->json(['data' => ['id' => $id, 'status' => 'in_review']]);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('employee.create')]
    #[Route('/overtime-records/{id}/correct', name: 'api_organization_overtime_correct', methods: ['POST'])]
    public function correctOvertime(
        string $id,
        Request $request,
        CorrectOvertimeHandler $handler,
    ): JsonResponse {
        $payload = $request->toArray();

        try {
            ($handler)(new CorrectOvertimeCommand(
                overtimeRecordId: $id,
                workedAt:         trim((string) ($payload['workedAt'] ?? '')),
                startTime:        trim((string) ($payload['startTime'] ?? '')),
                endTime:          trim((string) ($payload['endTime'] ?? '')),
                reason:           trim((string) ($payload['reason'] ?? '')),
                evidenceUrl:      isset($payload['evidenceUrl']) ? trim((string) $payload['evidenceUrl']) : null,
            ));

            return $this->json(['data' => ['id' => $id, 'status' => 'pending']]);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('employee.manage')]
    #[Route('/employees/{id}/provision-access', name: 'api_organization_employees_provision_access', methods: ['POST'])]
    public function provisionEmployeeAccess(
        string $id,
        Request $request,
        ProvisionEmployeeAccessHandler $handler,
    ): JsonResponse {
        $payload = $request->toArray();

        try {
            ($handler)(new ProvisionEmployeeAccessCommand(
                employeeId: $id,
                username:   trim((string) ($payload['username'] ?? '')),
                rawPassword: (string) ($payload['password'] ?? ''),
                roleId:     trim((string) ($payload['roleId'] ?? '')),
            ));

            return $this->json(['data' => ['id' => $id, 'hasUser' => true]]);
        } catch (EmployeeNotFoundException) {
            return $this->json(['error' => 'Empleado no encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException | \InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('employee.update')]
    #[Route('/employees/{id}/unlink-user', name: 'api_organization_employees_unlink_user', methods: ['POST'])]
    public function unlinkEmployeeUser(
        string $id,
        UnlinkUserFromEmployeeHandler $handler,
    ): JsonResponse {
        try {
            ($handler)(new UnlinkUserFromEmployeeCommand(
                employeeId: $id,
            ));

            return $this->json(['data' => ['id' => $id, 'hasUser' => false]]);
        } catch (EmployeeNotFoundException) {
            return $this->json(['error' => 'Empleado no encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (DomainException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    #[IsGranted('employee.view')]
    #[Route('/dashboard/summary', name: 'api_organization_dashboard_summary', methods: ['GET'])]
    public function dashboardSummary(
        GetOrganizationDashboardHandler $handler,
    ): JsonResponse {
        return $this->json([
            'data' => $handler(new GetOrganizationDashboardQuery()),
        ]);
    }
}
