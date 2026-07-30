<?php

declare(strict_types=1);

namespace App\Identity\Presentation\Http;

use App\Identity\Application\Query\ListAuthLog\ListAuthLogHandler;
use App\Identity\Application\Query\ListAuthLog\ListAuthLogQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/v1/identity/auth-log')]
#[IsGranted('auth_log.view')]
final class ApiIdentityAuthLogController extends AbstractController
{
    #[Route('', name: 'api_identity_auth_log_list', methods: ['GET'])]
    public function list(Request $request, ListAuthLogHandler $handler): JsonResponse
    {
        $page  = (int) ($request->query->get('page', '1'));
        $limit = (int) ($request->query->get('limit', '20'));

        if ($page < 1) {
            $page = 1;
        }
        if ($limit < 1) {
            $limit = 20;
        }
        if ($limit > 100) {
            $limit = 100;
        }

        $result = $handler(new ListAuthLogQuery(
            userId:   $request->query->get('userId'),
            dateFrom: $request->query->get('dateFrom'),
            dateTo:   $request->query->get('dateTo'),
            page:     $page,
            limit:    $limit,
        ));

        return $this->json([
            'data'       => $result->items,
            'pagination' => [
                'page'  => $result->page,
                'limit' => $result->limit,
                'total' => $result->total,
            ],
        ]);
    }
}
