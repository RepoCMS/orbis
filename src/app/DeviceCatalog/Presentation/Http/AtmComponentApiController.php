<?php

declare(strict_types=1);

namespace App\DeviceCatalog\Presentation\Http;

use App\DeviceCatalog\Application\Query\ListAtmComponents\ListAtmComponentsHandler;
use App\DeviceCatalog\Application\Query\ListAtmComponents\ListAtmComponentsQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/atm-components')]
final class AtmComponentApiController extends AbstractController
{
    #[Route('', name: 'api_atm_components', methods: ['GET'])]
    public function list(Request $request, ListAtmComponentsHandler $handler): JsonResponse
    {
        $parentId = $request->query->get('parentId');

        $items = $handler(new ListAtmComponentsQuery($parentId ?: null));

        return $this->json(array_map(static fn($r) => [
            'id'      => $r->id,
            'name'    => $r->name,
            'aliases' => $r->aliases,
            'level'   => $r->level,
        ], $items));
    }
}
