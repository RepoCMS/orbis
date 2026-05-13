<?php

declare(strict_types=1);

namespace App\Inventory\Presentation\Http;

use App\Inventory\Application\Command\AssignRepair\AssignRepairCommand;
use App\Inventory\Application\Command\AssignRepair\AssignRepairHandler;
use App\Inventory\Application\Command\CompleteRepair\CompleteRepairCommand;
use App\Inventory\Application\Command\CompleteRepair\CompleteRepairHandler;
use App\Inventory\Application\Command\MarkRepairUnrepairable\MarkRepairUnrepairableCommand;
use App\Inventory\Application\Command\MarkRepairUnrepairable\MarkRepairUnrepairableHandler;
use App\Inventory\Application\Command\StartRepair\StartRepairCommand;
use App\Inventory\Application\Command\StartRepair\StartRepairHandler;
use App\Inventory\Application\Exception\RepairNotFoundException;
use App\Inventory\Application\Query\FindRepairById\FindRepairByIdHandler;
use App\Inventory\Application\Query\ListLocations\ListLocationsHandler;
use App\Inventory\Application\Query\ListRepairs\ListRepairsHandler;
use App\Shared\Exceptions\ApplicationException;
use App\Shared\Exceptions\DomainException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/inventory/repairs')]
final class RepairController extends AbstractController
{
    #[IsGranted('inventory.view')]
    #[Route('', name: 'inventory_repair_list', methods: ['GET'])]
    public function list(Request $request, ListRepairsHandler $handler): Response
    {
        $status = $request->query->get('status') ?: null;

        return $this->render('inventory/repair/list.html.twig', [
            'repairs'       => $handler($status),
            'statusFilter'  => $status,
        ]);
    }

    #[IsGranted('inventory.create')]
    #[Route('/new', name: 'inventory_repair_new', methods: ['GET'])]
    public function newForm(ListLocationsHandler $listLocations): Response
    {
        return $this->render('inventory/repair/form.html.twig', [
            'errors'     => [],
            'input'      => [],
            'locations'  => $listLocations(),
        ]);
    }

    #[IsGranted('inventory.create')]
    #[Route('', name: 'inventory_repair_create', methods: ['POST'])]
    public function assign(
        Request $request,
        AssignRepairHandler $handler,
        ListLocationsHandler $listLocations,
    ): Response {
        $serializedStockId = trim((string) $request->request->get('serialized_stock_id', ''));
        $laboratoryId      = trim((string) $request->request->get('laboratory_id', ''));
        $notes             = trim((string) $request->request->get('notes', '')) ?: null;
        $userId            = $this->getUser()?->getUserIdentifier() ?? '';

        try {
            ($handler)(new AssignRepairCommand($serializedStockId, $laboratoryId, $userId, $notes));
            $this->addFlash('success', 'Reparación asignada.');

            return $this->redirectToRoute('inventory_repair_list');
        } catch (ApplicationException | DomainException $e) {
            return $this->render('inventory/repair/form.html.twig', [
                'errors'    => [$e->getMessage()],
                'input'     => compact('serializedStockId', 'laboratoryId', 'notes'),
                'locations' => $listLocations(),
            ], new Response(null, 422));
        }
    }

    #[IsGranted('inventory.view')]
    #[Route('/{id}', name: 'inventory_repair_show', methods: ['GET'])]
    public function show(string $id, FindRepairByIdHandler $findRepair): Response
    {
        try {
            $repair = $findRepair($id);
        } catch (RepairNotFoundException $e) {
            throw $this->createNotFoundException($e->getMessage());
        }

        return $this->render('inventory/repair/show.html.twig', [
            'repair' => $repair,
        ]);
    }

    #[IsGranted('inventory.update')]
    #[Route('/{id}/start', name: 'inventory_repair_start', methods: ['POST'])]
    public function start(string $id, StartRepairHandler $handler): Response
    {
        $userId = $this->getUser()?->getUserIdentifier() ?? '';

        try {
            ($handler)(new StartRepairCommand($id, $userId));
            $this->addFlash('success', 'Reparación iniciada.');
        } catch (RepairNotFoundException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('inventory_repair_show', ['id' => $id]);
    }

    #[IsGranted('inventory.update')]
    #[Route('/{id}/complete', name: 'inventory_repair_complete', methods: ['POST'])]
    public function complete(string $id, Request $request, CompleteRepairHandler $handler): Response
    {
        $notes = trim((string) $request->request->get('notes', '')) ?: null;

        try {
            ($handler)(new CompleteRepairCommand($id, $notes));
            $this->addFlash('success', 'Reparación completada. El stock está disponible.');
        } catch (RepairNotFoundException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('inventory_repair_show', ['id' => $id]);
    }

    #[IsGranted('inventory.update')]
    #[Route('/{id}/unrepairable', name: 'inventory_repair_unrepairable', methods: ['POST'])]
    public function markUnrepairable(string $id, Request $request, MarkRepairUnrepairableHandler $handler): Response
    {
        $notes = trim((string) $request->request->get('notes', '')) ?: null;

        try {
            ($handler)(new MarkRepairUnrepairableCommand($id, $notes));
            $this->addFlash('success', 'Repuesto marcado como irreparable. Stock dado de baja.');
        } catch (RepairNotFoundException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('inventory_repair_show', ['id' => $id]);
    }
}
