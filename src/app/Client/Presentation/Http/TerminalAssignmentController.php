<?php

declare(strict_types=1);

namespace App\Client\Presentation\Http;

use App\Client\Application\Command\ActivateTerminalAssignment\ActivateTerminalAssignmentCommand;
use App\Client\Application\Command\ActivateTerminalAssignment\ActivateTerminalAssignmentHandler;
use App\Client\Application\Command\DeactivateTerminalAssignment\DeactivateTerminalAssignmentCommand;
use App\Client\Application\Command\DeactivateTerminalAssignment\DeactivateTerminalAssignmentHandler;
use App\Client\Application\Command\RegisterTerminalAssignment\RegisterTerminalAssignmentCommand;
use App\Client\Application\Command\RegisterTerminalAssignment\RegisterTerminalAssignmentHandler;
use App\Client\Application\Exception\TerminalAssignmentNotFoundException;
use App\Client\Application\Exception\TerminalNotFoundException;
use App\Client\Application\Query\FindTerminalAssignmentById\FindTerminalAssignmentByIdHandler;
use App\Client\Application\Query\FindTerminalAssignmentById\FindTerminalAssignmentByIdQuery;
use App\Client\Application\Query\FindTerminalsByClient\FindTerminalsByClientHandler;
use App\Client\Application\Query\FindTerminalsByClient\FindTerminalsByClientQuery;
use App\Client\Application\Query\ListTerminalAssignments\ListTerminalAssignmentsHandler;
use App\Client\Application\Query\ListTerminalAssignments\ListTerminalAssignmentsQuery;
use App\Organization\Application\Query\ListServiceZones\ListServiceZonesHandler;
use App\Organization\Application\Query\ListServiceZones\ListServiceZonesQuery;
use App\Shared\Exceptions\DomainException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/clients/{clientId}/terminal-assignments')]
final class TerminalAssignmentController extends AbstractController
{
    #[IsGranted('terminal.view')]
    #[Route('', name: 'client_terminal_assignment_list', methods: ['GET'])]
    public function listAssignments(
        string $clientId,
        ListTerminalAssignmentsHandler $listAssignments,
        FindTerminalsByClientHandler $listTerminals,
        ListServiceZonesHandler $listZones,
    ): Response {
        $assignments = ($listAssignments)(new ListTerminalAssignmentsQuery());
        $terminals = ($listTerminals)(new FindTerminalsByClientQuery($clientId));
        $serviceZones = $listZones(new ListServiceZonesQuery(onlyActive: true));

        return $this->render('client/terminal_assignment/list.html.twig', [
            'clientId'          => $clientId,
            'assignments'       => $assignments,
            'terminals'         => $terminals,
            'serviceZones'      => $serviceZones,
        ]);
    }

    #[IsGranted('terminal.create')]
    #[Route('/new', name: 'client_terminal_assignment_new', methods: ['GET'])]
    public function newAssignmentForm(
        string $clientId,
        FindTerminalsByClientHandler $listTerminals,
        ListServiceZonesHandler $listZones,
    ): Response {
        $terminals = ($listTerminals)(new FindTerminalsByClientQuery($clientId));
        $serviceZones = $listZones(new ListServiceZonesQuery(onlyActive: true));

        return $this->render('client/terminal_assignment/form.html.twig', [
            'clientId'          => $clientId,
            'terminals'         => $terminals,
            'serviceZones'      => $serviceZones,
            'errors'            => [],
            'input'             => [],
        ]);
    }

    #[IsGranted('terminal.create')]
    #[Route('', name: 'client_terminal_assignment_create', methods: ['POST'])]
    public function createAssignment(
        string $clientId,
        Request $request,
        RegisterTerminalAssignmentHandler $registerAssignment,
        FindTerminalsByClientHandler $listTerminals,
        ListServiceZonesHandler $listZones,
    ): Response {
        $terminalId         = trim((string) $request->request->get('terminalId', ''));
        $name               = trim((string) $request->request->get('name', ''));
        $idAssignedByClient = trim((string) $request->request->get('idAssignedByClient', ''));
        $locationId         = trim((string) $request->request->get('locationId', ''));

        try {
            ($registerAssignment)(new RegisterTerminalAssignmentCommand(
                terminalId:         $terminalId,
                locationId:         $locationId,
                name:               $name,
                idAssignedByClient: $idAssignedByClient,
            ));

            $this->addFlash('success', 'Asignación de terminal registrada exitosamente.');

            return $this->redirectToRoute('client_terminal_assignment_list', ['clientId' => $clientId]);
        } catch (TerminalNotFoundException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        $terminals = ($listTerminals)(new FindTerminalsByClientQuery($clientId));
        $serviceZones = $listZones(new ListServiceZonesQuery(onlyActive: true));

        return $this->render('client/terminal_assignment/form.html.twig', [
            'clientId'          => $clientId,
            'terminals'         => $terminals,
            'serviceZones'      => $serviceZones,
            'errors'            => [],
            'input'             => compact('terminalId', 'name', 'idAssignedByClient', 'locationId'),
        ], new Response(null, Response::HTTP_UNPROCESSABLE_ENTITY));
    }

    #[IsGranted('terminal.view')]
    #[Route('/{id}', name: 'client_terminal_assignment_show', methods: ['GET'])]
    public function showAssignment(
        string $clientId,
        string $id,
        FindTerminalAssignmentByIdHandler $findAssignment,
        FindTerminalsByClientHandler $listTerminals,
        ListServiceZonesHandler $listZones,
    ): Response {
        try {
            $assignment = ($findAssignment)(new FindTerminalAssignmentByIdQuery($id));
        } catch (TerminalAssignmentNotFoundException) {
            throw $this->createNotFoundException('Asignación de terminal no encontrada.');
        }

        $terminals = ($listTerminals)(new FindTerminalsByClientQuery($clientId));
        $serviceZones = $listZones(new ListServiceZonesQuery(onlyActive: true));

        return $this->render('client/terminal_assignment/show.html.twig', [
            'clientId'          => $clientId,
            'assignment'        => $assignment,
            'terminals'         => $terminals,
            'serviceZones'      => $serviceZones,
        ]);
    }

    #[IsGranted('terminal.manage')]
    #[Route('/{id}/deactivate', name: 'client_terminal_assignment_deactivate', methods: ['POST'])]
    public function deactivateAssignment(
        string $clientId,
        string $id,
        DeactivateTerminalAssignmentHandler $deactivateAssignment,
    ): Response {
        try {
            ($deactivateAssignment)(new DeactivateTerminalAssignmentCommand($id));
            $this->addFlash('success', 'Asignación de terminal desactivada.');
        } catch (TerminalAssignmentNotFoundException) {
            throw $this->createNotFoundException('Asignación de terminal no encontrada.');
        } catch (DomainException $e) {
            $this->addFlash('warning', $e->getMessage());
        }

        return $this->redirectToRoute('client_terminal_assignment_list', ['clientId' => $clientId]);
    }

    #[IsGranted('terminal.manage')]
    #[Route('/{id}/reactivate', name: 'client_terminal_assignment_reactivate', methods: ['POST'])]
    public function reactivateAssignment(
        string $clientId,
        string $id,
        ActivateTerminalAssignmentHandler $activateAssignment,
    ): Response {
        try {
            ($activateAssignment)(new ActivateTerminalAssignmentCommand($id));
            $this->addFlash('success', 'Asignación de terminal reactivada.');
        } catch (TerminalAssignmentNotFoundException) {
            throw $this->createNotFoundException('Asignación de terminal no encontrada.');
        } catch (DomainException $e) {
            $this->addFlash('warning', $e->getMessage());
        }

        return $this->redirectToRoute('client_terminal_assignment_list', ['clientId' => $clientId]);
    }
}
