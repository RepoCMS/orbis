<?php

declare(strict_types=1);

namespace App\Identity\Presentation\Http;

use App\Identity\Application\Query\ListAuthLog\ListAuthLogHandler;
use App\Identity\Application\Query\ListAuthLog\ListAuthLogQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/security/auth-logs')]
#[IsGranted('auth_log.view')]
final class AuthLogController extends AbstractController
{
    #[Route('', name: 'auth_log_list', methods: ['GET'])]
    public function list(Request $request, ListAuthLogHandler $handler): Response
    {
        $page     = (int) ($request->query->get('page', '1'));
        $limit    = (int) ($request->query->get('limit', '20'));
        $userId   = $request->query->get('userId');
        $dateFrom = $request->query->get('dateFrom');
        $dateTo   = $request->query->get('dateTo');

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
            userId:   $userId !== '' ? $userId : null,
            dateFrom: $dateFrom !== '' ? $dateFrom : null,
            dateTo:   $dateTo !== '' ? $dateTo : null,
            page:     $page,
            limit:    $limit,
        ));

        return $this->render('identity/auth_log/list.html.twig', [
            'logs'   => $result->items,
            'total'  => $result->total,
            'page'   => $result->page,
            'limit'  => $result->limit,
            'filters' => [
                'userId'   => $userId,
                'dateFrom' => $dateFrom,
                'dateTo'   => $dateTo,
            ],
        ]);
    }

    #[Route('/{id}', name: 'auth_log_show', methods: ['GET'])]
    public function show(
        string $id,
        ListAuthLogHandler $handler,
    ): Response {
        // We use list handler with a higher limit and filter in memory
        $result = $handler(new ListAuthLogQuery(
            page:  1,
            limit: 100,
        ));

        $log = null;
        foreach ($result->items as $item) {
            if ($item->id === $id) {
                $log = $item;
                break;
            }
        }

        if ($log === null) {
            throw $this->createNotFoundException('Registro de auditoría no encontrado.');
        }

        return $this->render('identity/auth_log/show.html.twig', [
            'log' => $log,
        ]);
    }
}
