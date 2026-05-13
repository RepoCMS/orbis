<?php

declare(strict_types=1);

namespace App\Inventory\Presentation\Http;

use App\Inventory\Application\Query\FindMovementById\FindMovementByIdHandler;
use App\Inventory\Application\Query\ListMovements\ListMovementsHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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

        return $this->render('inventory/movement/show.html.twig', [
            'movement' => $movement,
        ]);
    }
}
