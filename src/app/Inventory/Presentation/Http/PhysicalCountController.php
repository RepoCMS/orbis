<?php

declare(strict_types=1);

namespace App\Inventory\Presentation\Http;

use App\Inventory\Application\Command\ApprovePhysicalCount\ApprovePhysicalCountCommand;
use App\Inventory\Application\Command\ApprovePhysicalCount\ApprovePhysicalCountHandler;
use App\Inventory\Application\Command\RejectPhysicalCount\RejectPhysicalCountCommand;
use App\Inventory\Application\Command\RejectPhysicalCount\RejectPhysicalCountHandler;
use App\Inventory\Application\Command\StartPhysicalCount\StartPhysicalCountCommand;
use App\Inventory\Application\Command\StartPhysicalCount\StartPhysicalCountHandler;
use App\Inventory\Application\Command\SubmitPhysicalCount\SubmitPhysicalCountCommand;
use App\Inventory\Application\Command\SubmitPhysicalCount\SubmitPhysicalCountHandler;
use App\Inventory\Application\Exception\LocationNotFoundException;
use App\Inventory\Application\Query\ListLocations\ListLocationsHandler;
use App\Inventory\Application\Query\ListPhysicalCounts\ListPhysicalCountsHandler;
use App\Inventory\Application\Query\ListPhysicalCounts\ListPhysicalCountsQuery;
use App\Inventory\Domain\Repositories\PhysicalCountRepositoryInterface;
use App\Inventory\Domain\ValueObjects\PhysicalCountIdValueObject;
use App\Identity\Infrastructure\Security\SymfonySecurityUser;
use App\Shared\Exceptions\DomainException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/inventory/physical-counts')]
final class PhysicalCountController extends AbstractController
{
    #[IsGranted('inventory.view')]
    #[Route('', name: 'inventory_physical_count_list', methods: ['GET'])]
    public function list(
        Request $request,
        ListPhysicalCountsHandler $handler,
    ): Response {
        $status = $request->query->get('status') ?: null;

        $query = new ListPhysicalCountsQuery(
            locationId: null,
            status:     $status,
        );

        return $this->render('inventory/physical_count/list.html.twig', [
            'counts'       => $handler($query),
            'statusFilter' => $status,
        ]);
    }

    #[IsGranted('inventory.view')]
    #[Route('/{id}', name: 'inventory_physical_count_show', methods: ['GET'])]
    public function show(
        string $id,
        PhysicalCountRepositoryInterface $repository,
    ): Response {
        $count = $repository->findById(new PhysicalCountIdValueObject($id));

        if ($count === null) {
            throw $this->createNotFoundException('Conteo físico no encontrado.');
        }

        $response = \App\Inventory\Application\Query\Response\PhysicalCountResponse::fromEntity($count);

        return $this->render('inventory/physical_count/show.html.twig', [
            'count' => $response,
        ]);
    }

    #[IsGranted('inventory.create')]
    #[Route('/new', name: 'inventory_physical_count_new', methods: ['GET'])]
    public function newForm(ListLocationsHandler $listLocations): Response
    {
        return $this->render('inventory/physical_count/form.html.twig', [
            'errors'    => [],
            'input'     => [],
            'locations' => $listLocations(),
        ]);
    }

    #[IsGranted('inventory.create')]
    #[Route('', name: 'inventory_physical_count_create', methods: ['POST'])]
    public function create(
        Request $request,
        StartPhysicalCountHandler $handler,
        ListLocationsHandler $listLocations,
    ): Response {
        $locationId = trim((string) $request->request->get('location_id', ''));
        $notes      = trim((string) $request->request->get('notes', '')) ?: null;
        $user       = $this->getUser();
        $userId     = $user instanceof SymfonySecurityUser ? (string) $user->getDomainUser()->id() : '';

        try {
            ($handler)(new StartPhysicalCountCommand(
                locationId: $locationId,
                countedBy:  $userId,
                notes:      $notes,
            ));
            $this->addFlash('success', 'Conteo físico iniciado.');

            return $this->redirectToRoute('inventory_physical_count_list');
        } catch (DomainException $e) {
            return $this->render('inventory/physical_count/form.html.twig', [
                'errors'    => [$e->getMessage()],
                'input'     => compact('locationId', 'notes'),
                'locations' => $listLocations(),
            ], new Response(null, 422));
        }
    }

    #[IsGranted('inventory.update')]
    #[Route('/{id}/submit', name: 'inventory_physical_count_submit', methods: ['POST'])]
    public function submit(
        string $id,
        SubmitPhysicalCountHandler $handler,
    ): Response {
        try {
            ($handler)(new SubmitPhysicalCountCommand($id));
            $this->addFlash('success', 'Conteo enviado para aprobación.');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('inventory_physical_count_show', ['id' => $id]);
    }

    #[IsGranted('inventory.manage')]
    #[Route('/{id}/approve', name: 'inventory_physical_count_approve', methods: ['POST'])]
    public function approve(
        string $id,
        ApprovePhysicalCountHandler $handler,
    ): Response {
        $user   = $this->getUser();
        $userId = $user instanceof SymfonySecurityUser ? (string) $user->getDomainUser()->id() : '';

        try {
            ($handler)(new ApprovePhysicalCountCommand($id, $userId));
            $this->addFlash('success', 'Conteo aprobado. Ajustes de stock generados.');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('inventory_physical_count_show', ['id' => $id]);
    }

    #[IsGranted('inventory.manage')]
    #[Route('/{id}/reject', name: 'inventory_physical_count_reject', methods: ['POST'])]
    public function reject(
        string $id,
        Request $request,
        RejectPhysicalCountHandler $handler,
    ): Response {
        $reason = trim((string) $request->request->get('reason', ''));

        try {
            ($handler)(new RejectPhysicalCountCommand($id, $reason));
            $this->addFlash('success', 'Conteo rechazado.');
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('inventory_physical_count_show', ['id' => $id]);
    }
}
