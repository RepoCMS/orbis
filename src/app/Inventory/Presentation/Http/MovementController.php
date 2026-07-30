<?php

declare(strict_types=1);

namespace App\Inventory\Presentation\Http;

use App\Identity\Infrastructure\Security\SymfonySecurityUser;
use App\Inventory\Application\Command\CancelTransfer\CancelTransferCommand;
use App\Inventory\Application\Command\CancelTransfer\CancelTransferHandler;
use App\Inventory\Application\Command\ConfirmTransfer\ConfirmTransferCommand;
use App\Inventory\Application\Command\ConfirmTransfer\ConfirmTransferHandler;
use App\Inventory\Application\Command\CreateTransfer\CreateTransferCommand;
use App\Inventory\Application\Command\CreateTransfer\CreateTransferHandler;
use App\Inventory\Application\Command\RejectTransfer\RejectTransferCommand;
use App\Inventory\Application\Command\RejectTransfer\RejectTransferHandler;
use App\Inventory\Application\Query\FindMovementById\FindMovementByIdHandler;
use App\Inventory\Application\Query\ListMovements\ListMovementsHandler;
use App\Shared\Exceptions\DomainException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/inventory/movements')]
final class MovementController extends AbstractController
{
    #[IsGranted('inventory.view')]
    #[Route('', name: 'inventory_movement_list', methods: ['GET'])]
    public function list(ListMovementsHandler $handler): Response
    {
        return $this->render('inventory/movement/list.html.twig', [
            'movements' => $handler(),
        ]);
    }

    #[IsGranted('inventory.view')]
    #[Route('/{id}', name: 'inventory_movement_show', methods: ['GET'])]
    public function show(string $id, FindMovementByIdHandler $handler): Response
    {
        try {
            $movement = $handler($id);
        } catch (\RuntimeException $e) {
            throw $this->createNotFoundException($e->getMessage());
        }

        $user   = $this->getUser();
        $userId = $user instanceof SymfonySecurityUser ? (string) $user->getDomainUser()->id() : '';

        return $this->render('inventory/movement/show.html.twig', [
            'movement'   => $movement,
            'canConfirm' => $movement->assignedToUserId === $userId,
            'canCancel'  => $movement->createdByUserId === $userId,
            'canReject'  => $movement->assignedToUserId === $userId,
        ]);
    }

    #[IsGranted('inventory.move_stock')]
    #[Route('/new', name: 'inventory_movement_form', methods: ['GET'])]
    public function form(): Response
    {
        return $this->render('inventory/movement/form.html.twig');
    }

    #[IsGranted('inventory.move_stock')]
    #[Route('/transfer-from-stock', name: 'inventory_movement_transfer', methods: ['POST'])]
    public function transferFromStock(Request $request, CreateTransferHandler $handler): Response
    {
        $user      = $this->getUser();
        $createdBy = $user instanceof SymfonySecurityUser ? (string) $user->getDomainUser()->id() : '';

        $destinationLocationId = trim((string) $request->request->get('destination_location', ''));
        $notes                 = $request->request->get('notes') ? trim((string) $request->request->get('notes')) : null;
        $rawItems              = $request->request->all('items') ?? [];
        $quantities            = $request->request->all('qty') ?? [];

        $items = [];
        foreach ($rawItems as $raw) {
            $parts = explode('|', (string) $raw);
            if (count($parts) < 3) {
                continue;
            }
            $stockId   = $parts[0];
            $type      = $parts[1];
            $productId = $parts[2];
            $quantity  = isset($quantities[$stockId]) ? max(1, (int) $quantities[$stockId]) : 1;

            $items[] = [
                'stockId'   => $stockId,
                'type'      => $type,
                'productId' => $productId,
                'quantity'  => $quantity,
            ];
        }

        try {
            ($handler)(new CreateTransferCommand(
                destinationLocationId: $destinationLocationId,
                items:                 $items,
                notes:                 $notes,
                createdBy:             $createdBy,
            ));
        } catch (DomainException | \InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());

            return $this->redirectToRoute('inventory_stock_overview');
        }

        $this->addFlash('success', 'Transferencia creada correctamente.');

        return $this->redirectToRoute('inventory_movement_list');
    }

    #[IsGranted('inventory.move_stock')]
    #[Route('/new', name: 'inventory_movement_create', methods: ['POST'])]
    public function create(Request $request, CreateTransferHandler $handler): Response
    {
        $user      = $this->getUser();
        $createdBy = $user instanceof SymfonySecurityUser ? (string) $user->getDomainUser()->id() : '';

        $rawItems = $request->request->all('items') ?? [];

        /** @var array<array{stockId: string, type: string, productId: string, quantity?: int}> $items */
        $items = [];
        foreach ($rawItems as $item) {
            if (empty($item['stockId']) || empty($item['productId']) || empty($item['type'])) {
                continue;
            }
            $items[] = [
                'stockId'   => trim((string) $item['stockId']),
                'type'      => trim((string) $item['type']),
                'productId' => trim((string) $item['productId']),
                'quantity'  => isset($item['quantity']) ? (int) $item['quantity'] : 1,
            ];
        }

        try {
            ($handler)(new CreateTransferCommand(
                destinationLocationId: trim((string) $request->request->get('destinationLocationId', '')),
                items:                 $items,
                notes:                 $request->request->get('notes') ? trim((string) $request->request->get('notes')) : null,
                createdBy:             $createdBy,
            ));
        } catch (DomainException | \InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());

            return $this->render('inventory/movement/form.html.twig', [
                'errors' => [$e->getMessage()],
                'input'  => $request->request->all(),
            ]);
        }

        $this->addFlash('success', 'Transferencia creada correctamente.');

        return $this->redirectToRoute('inventory_movement_list');
    }

    #[IsGranted('inventory.move_stock')]
    #[Route('/{id}/confirm', name: 'inventory_movement_confirm', methods: ['POST'])]
    public function confirm(string $id, ConfirmTransferHandler $handler): Response
    {
        $user        = $this->getUser();
        $confirmedBy = $user instanceof SymfonySecurityUser ? (string) $user->getDomainUser()->id() : '';

        try {
            ($handler)(new ConfirmTransferCommand(
                movementId:  $id,
                confirmedBy: $confirmedBy,
            ));

            $this->addFlash('success', 'Transferencia confirmada correctamente.');
        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('inventory_movement_show', ['id' => $id]);
    }

    #[IsGranted('inventory.move_stock')]
    #[Route('/{id}/cancel', name: 'inventory_movement_cancel', methods: ['POST'])]
    public function cancel(string $id, CancelTransferHandler $handler): Response
    {
        $user        = $this->getUser();
        $cancelledBy = $user instanceof SymfonySecurityUser ? (string) $user->getDomainUser()->id() : '';

        try {
            ($handler)(new CancelTransferCommand(
                movementId:  $id,
                cancelledBy: $cancelledBy,
            ));

            $this->addFlash('success', 'Transferencia cancelada correctamente.');
        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('inventory_movement_show', ['id' => $id]);
    }

    #[IsGranted('inventory.move_stock')]
    #[Route('/{id}/reject', name: 'inventory_movement_reject', methods: ['POST'])]
    public function reject(string $id, Request $request, RejectTransferHandler $handler): Response
    {
        $user       = $this->getUser();
        $rejectedBy = $user instanceof SymfonySecurityUser ? (string) $user->getDomainUser()->id() : '';

        try {
            ($handler)(new RejectTransferCommand(
                movementId: $id,
                rejectedBy: $rejectedBy,
                reason:     trim((string) $request->request->get('reason', '')),
                quantity:   (int) $request->request->get('reject_qty', 1),
            ));

            $this->addFlash('success', 'Transferencia rechazada correctamente.');
        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('inventory_movement_show', ['id' => $id]);
    }
}
