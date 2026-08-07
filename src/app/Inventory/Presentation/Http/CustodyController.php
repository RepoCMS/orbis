<?php

declare(strict_types=1);

namespace App\Inventory\Presentation\Http;

use App\Inventory\Application\Command\CreateCustodyAssignment\CreateCustodyAssignmentCommand;
use App\Inventory\Application\Command\CreateCustodyAssignment\CreateCustodyAssignmentHandler;
use App\Inventory\Application\Command\ReturnFromCustody\ReturnFromCustodyCommand;
use App\Inventory\Application\Command\ReturnFromCustody\ReturnFromCustodyHandler;
use App\Inventory\Application\Exception\LocationNotFoundException;
use App\Inventory\Application\Exception\StockNotFoundException;
use App\Inventory\Application\Query\ListCustodyAssignments\ListCustodyAssignmentsHandler;
use App\Inventory\Application\Query\ListCustodyAssignments\ListCustodyAssignmentsQuery;
use App\Inventory\Application\Query\ListLocations\ListLocationsHandler;
use App\Organization\Application\Query\ListEmployees\ListEmployeesHandler;
use App\Organization\Application\Query\ListEmployees\ListEmployeesQuery;
use App\Shared\Exceptions\ApplicationException;
use App\Shared\Exceptions\DomainException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/inventory/custody')]
final class CustodyController extends AbstractController
{
    #[IsGranted('inventory.view')]
    #[Route('', name: 'inventory_custody_list', methods: ['GET'])]
    public function list(
        Request $request,
        ListCustodyAssignmentsHandler $handler,
        ListEmployeesHandler $listEmployees,
    ): Response {
        $employeeId = $request->query->get('employeeId');
        $locationId = $request->query->get('locationId');

        $assignments = $handler(new ListCustodyAssignmentsQuery(
            employeeId: $employeeId !== null ? (string) $employeeId : null,
            locationId: $locationId !== null ? (string) $locationId : null,
        ));

        $employees = $listEmployees(new ListEmployeesQuery(limit: 500))->items;

        return $this->render('inventory/custody/list.html.twig', [
            'assignments' => $assignments,
            'employees'   => $employees,
            'filters'     => [
                'employeeId' => $employeeId,
                'locationId' => $locationId,
            ],
        ]);
    }

    #[IsGranted('inventory.view')]
    #[Route('/employee/{employeeId}', name: 'inventory_custody_by_employee', methods: ['GET'])]
    public function byEmployee(
        string $employeeId,
        ListCustodyAssignmentsHandler $handler,
        ListEmployeesHandler $listEmployees,
    ): Response {
        $assignments = $handler(new ListCustodyAssignmentsQuery(employeeId: $employeeId));

        $employees = $listEmployees(new ListEmployeesQuery(limit: 500))->items;

        return $this->render('inventory/custody/list.html.twig', [
            'assignments' => $assignments,
            'employees'   => $employees,
            'filters'     => [
                'employeeId' => $employeeId,
                'locationId' => null,
            ],
        ]);
    }

    #[IsGranted('inventory.view')]
    #[Route('/location/{locationId}', name: 'inventory_custody_by_location', methods: ['GET'])]
    public function byLocation(
        string $locationId,
        ListCustodyAssignmentsHandler $handler,
        ListEmployeesHandler $listEmployees,
    ): Response {
        $assignments = $handler(new ListCustodyAssignmentsQuery(locationId: $locationId));

        $employees = $listEmployees(new ListEmployeesQuery(limit: 500))->items;

        return $this->render('inventory/custody/list.html.twig', [
            'assignments' => $assignments,
            'employees'   => $employees,
            'filters'     => [
                'employeeId' => null,
                'locationId' => $locationId,
            ],
        ]);
    }

    #[IsGranted('inventory.create')]
    #[Route('/assign', name: 'inventory_custody_assign_form', methods: ['GET'])]
    public function assignForm(
        ListEmployeesHandler $listEmployees,
        ListLocationsHandler $listLocations,
    ): Response {
        $employees = $listEmployees(new ListEmployeesQuery(limit: 500))->items;
        $locations = $listLocations();

        return $this->render('inventory/custody/form.html.twig', [
            'employees' => $employees,
            'locations' => $locations,
            'errors'    => [],
            'input'     => [],
        ]);
    }

    #[IsGranted('inventory.create')]
    #[Route('/assign', name: 'inventory_custody_assign', methods: ['POST'])]
    public function assign(
        Request $request,
        CreateCustodyAssignmentHandler $handler,
        ListEmployeesHandler $listEmployees,
        ListLocationsHandler $listLocations,
    ): Response {
        $employeeId = trim((string) $request->request->get('employeeId', ''));
        $stockItemId = trim((string) $request->request->get('stockItemId', ''));
        $locationId = trim((string) $request->request->get('locationId', ''));
        $notes = trim((string) $request->request->get('notes', '')) ?: null;

        try {
            ($handler)(new CreateCustodyAssignmentCommand(
                employeeId:  $employeeId,
                stockItemId: $stockItemId,
                locationId:  $locationId,
                notes:       $notes,
            ));

            $this->addFlash('success', 'Custodia asignada correctamente.');

            return $this->redirectToRoute('inventory_custody_list');
        } catch (LocationNotFoundException | StockNotFoundException $e) {
            $errors = [$e->getMessage()];
        } catch (ApplicationException | DomainException | \InvalidArgumentException $e) {
            $errors = [$e->getMessage()];
        }

        $employees = $listEmployees(new ListEmployeesQuery(limit: 500))->items;
        $locations = $listLocations();

        return $this->render('inventory/custody/form.html.twig', [
            'employees' => $employees,
            'locations' => $locations,
            'errors'    => $errors ?? [],
            'input'     => compact('employeeId', 'stockItemId', 'locationId', 'notes'),
        ], new Response(null, 422));
    }

    #[IsGranted('inventory.create')]
    #[Route('/return', name: 'inventory_custody_return_form', methods: ['GET'])]
    public function returnForm(
        ListLocationsHandler $listLocations,
        ListCustodyAssignmentsHandler $listCustody,
    ): Response {
        $assignments = $listCustody(new ListCustodyAssignmentsQuery());
        $locations = $listLocations();

        return $this->render('inventory/custody/return.html.twig', [
            'assignments' => $assignments,
            'locations'   => $locations,
            'errors'      => [],
            'input'       => [],
        ]);
    }

    #[IsGranted('inventory.create')]
    #[Route('/return', name: 'inventory_custody_return', methods: ['POST'])]
    public function returnStock(
        Request $request,
        ReturnFromCustodyHandler $handler,
        ListLocationsHandler $listLocations,
        ListCustodyAssignmentsHandler $listCustody,
    ): Response {
        $custodyLocationId = trim((string) $request->request->get('custodyLocationId', ''));
        $stockItemId = trim((string) $request->request->get('stockItemId', ''));
        $returnLocationId = trim((string) $request->request->get('returnLocationId', ''));

        try {
            ($handler)(new ReturnFromCustodyCommand(
                custodyLocationId: $custodyLocationId,
                stockItemId:       $stockItemId,
                returnLocationId:  $returnLocationId,
            ));

            $this->addFlash('success', 'Devolución de custodia registrada.');

            return $this->redirectToRoute('inventory_custody_list');
        } catch (LocationNotFoundException | StockNotFoundException $e) {
            $errors = [$e->getMessage()];
        } catch (ApplicationException | DomainException | \InvalidArgumentException $e) {
            $errors = [$e->getMessage()];
        }

        $assignments = $listCustody(new ListCustodyAssignmentsQuery());
        $locations = $listLocations();

        return $this->render('inventory/custody/return.html.twig', [
            'assignments' => $assignments,
            'locations'   => $locations,
            'errors'      => $errors ?? [],
            'input'       => compact('custodyLocationId', 'stockItemId', 'returnLocationId'),
        ], new Response(null, 422));
    }
}
