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
use App\Inventory\Application\Query\ListPhysicalCounts\ListPhysicalCountsHandler;
use App\Inventory\Application\Query\ListPhysicalCounts\ListPhysicalCountsQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/inventory/physical-counts')]
final class ApiInventoryPhysicalCountController extends AbstractController
{
    #[IsGranted('inventory.manage')]
    #[Route('', name: 'api_inventory_physical_counts_create', methods: ['POST'])]
    public function create(
        Request $request,
        StartPhysicalCountHandler $handler,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $command = new StartPhysicalCountCommand(
            locationId: $data['locationId'],
            countedBy:  $data['countedBy'],
            notes:      $data['notes'] ?? null,
            items:      $data['items'] ?? [],
        );

        try {
            $countId = $handler($command);

            return $this->json(['data' => ['id' => $countId]], Response::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[IsGranted('inventory.view')]
    #[Route('', name: 'api_inventory_physical_counts_list', methods: ['GET'])]
    public function list(
        Request $request,
        ListPhysicalCountsHandler $handler,
    ): JsonResponse {
        $query = new ListPhysicalCountsQuery(
            locationId: $request->query->get('locationId'),
            status:     $request->query->get('status'),
        );

        $counts = $handler($query);

        return $this->json(['data' => $counts]);
    }

    #[IsGranted('inventory.manage')]
    #[Route('/{id}/submit', name: 'api_inventory_physical_counts_submit', methods: ['POST'])]
    public function submit(
        string $id,
        SubmitPhysicalCountHandler $handler,
    ): JsonResponse {
        $command = new SubmitPhysicalCountCommand(countId: $id);

        try {
            $handler($command);

            return $this->json(['data' => ['id' => $id]]);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[IsGranted('inventory.manage')]
    #[Route('/{id}/approve', name: 'api_inventory_physical_counts_approve', methods: ['POST'])]
    public function approve(
        string $id,
        Request $request,
        ApprovePhysicalCountHandler $handler,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $command = new ApprovePhysicalCountCommand(
            countId:    $id,
            approvedBy: $data['approvedBy'],
        );

        try {
            $handler($command);

            return $this->json(['data' => ['id' => $id]]);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[IsGranted('inventory.manage')]
    #[Route('/{id}/reject', name: 'api_inventory_physical_counts_reject', methods: ['POST'])]
    public function reject(
        string $id,
        Request $request,
        RejectPhysicalCountHandler $handler,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $command = new RejectPhysicalCountCommand(
            countId: $id,
            reason:  $data['reason'],
        );

        try {
            $handler($command);

            return $this->json(['data' => ['id' => $id]]);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}
