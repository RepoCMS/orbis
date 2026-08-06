<?php

declare(strict_types=1);

namespace App\Client\Presentation\Http;

use App\Client\Application\Command\AddSite\AddSiteCommand;
use App\Client\Application\Command\AddSite\AddSiteHandler;
use App\Client\Application\Command\DeactivateSite\DeactivateSiteCommand;
use App\Client\Application\Command\DeactivateSite\DeactivateSiteHandler;
use App\Client\Application\Command\ReactivateSite\ReactivateSiteCommand;
use App\Client\Application\Command\ReactivateSite\ReactivateSiteHandler;
use App\Client\Application\Command\UpdateSite\UpdateSiteCommand;
use App\Client\Application\Command\UpdateSite\UpdateSiteHandler;
use App\Client\Application\Exception\ClientNotFoundException;
use App\Client\Application\Exception\SiteNotFoundException;
use App\Client\Application\Query\FindClientById\FindClientByIdHandler;
use App\Client\Application\Query\FindClientById\FindClientByIdQuery;
use App\Client\Application\Query\FindSiteById\FindSiteByIdHandler;
use App\Client\Application\Query\FindSiteById\FindSiteByIdQuery;
use App\Organization\Application\Query\ListServiceZones\ListServiceZonesHandler;
use App\Organization\Application\Query\ListServiceZones\ListServiceZonesQuery;
use App\Shared\Exceptions\DomainException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/clients/{clientId}/sites')]
final class SiteController extends AbstractController
{
    #[IsGranted('site.create')]
    #[Route('', name: 'site_add', methods: ['POST'])]
    public function addSite(
        string $clientId,
        Request $request,
        AddSiteHandler $addSite,
    ): Response {
        $name          = trim((string) $request->request->get('name', ''));
        $cityId        = trim((string) $request->request->get('city_id', ''));
        $terminalId    = trim((string) $request->request->get('terminal_id', ''));
        $serviceZoneId = trim((string) $request->request->get('service_zone_id', '')) ?: null;

        try {
            ($addSite)(new AddSiteCommand(
                clientId:      $clientId,
                cityId:        $cityId,
                terminalId:    $terminalId,
                name:          $name,
                serviceZoneId: $serviceZoneId,
            ));

            $this->addFlash('success', sprintf('Sucursal "%s" agregada.', $name));
        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('client_show', ['id' => $clientId]);
    }

    #[IsGranted('site.view')]
    #[Route('/{siteId}', name: 'site_show', methods: ['GET'])]
    public function showSite(
        string $clientId,
        string $siteId,
        FindSiteByIdHandler $findSite,
        FindClientByIdHandler $findClient,
        ListServiceZonesHandler $listZones,
    ): Response {
        try {
            $client = $findClient(new FindClientByIdQuery($clientId));
            $site   = $findSite(new FindSiteByIdQuery($siteId));
        } catch (ClientNotFoundException) {
            throw $this->createNotFoundException('Cliente no encontrado.');
        } catch (SiteNotFoundException) {
            throw $this->createNotFoundException('Sucursal no encontrada.');
        }

        $zones = $listZones(new ListServiceZonesQuery(onlyActive: true));

        return $this->render('site/show.html.twig', [
            'client' => $client,
            'site'   => $site,
            'zones'  => $zones,
        ]);
    }

    #[IsGranted('site.update')]
    #[Route('/{siteId}/edit', name: 'site_edit', methods: ['POST'])]
    public function editSite(
        string $clientId,
        string $siteId,
        Request $request,
        UpdateSiteHandler $updateSite,
        FindSiteByIdHandler $findSite,
        FindClientByIdHandler $findClient,
        ListServiceZonesHandler $listZones,
    ): Response {
        $name          = trim((string) $request->request->get('name', ''));
        $serviceZoneId = trim((string) $request->request->get('service_zone_id', '')) ?: null;

        try {
            ($updateSite)(new UpdateSiteCommand(
                siteId:        $siteId,
                name:          $name,
                serviceZoneId: $serviceZoneId,
            ));

            $this->addFlash('success', 'Sucursal actualizada.');

            return $this->redirectToRoute('site_show', ['clientId' => $clientId, 'siteId' => $siteId]);
        } catch (SiteNotFoundException) {
            throw $this->createNotFoundException('Sucursal no encontrada.');
        } catch (DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        try {
            $client = $findClient(new FindClientByIdQuery($clientId));
            $site   = $findSite(new FindSiteByIdQuery($siteId));
        } catch (ClientNotFoundException) {
            throw $this->createNotFoundException('Cliente no encontrado.');
        } catch (SiteNotFoundException) {
            throw $this->createNotFoundException('Sucursal no encontrada.');
        }

        $zones = $listZones(new ListServiceZonesQuery(onlyActive: true));

        return $this->render('site/show.html.twig', [
            'client'    => $client,
            'site'      => $site,
            'zones'     => $zones,
            'editMode'  => true,
            'editInput' => compact('name', 'serviceZoneId'),
        ], new Response(null, Response::HTTP_UNPROCESSABLE_ENTITY));
    }

    #[IsGranted('site.update')]
    #[Route('/{siteId}/deactivate', name: 'site_deactivate', methods: ['POST'])]
    public function deactivateSite(
        string $clientId,
        string $siteId,
        DeactivateSiteHandler $deactivate,
    ): Response {
        try {
            ($deactivate)(new DeactivateSiteCommand($siteId));
            $this->addFlash('success', 'Sucursal desactivada.');
        } catch (SiteNotFoundException) {
            throw $this->createNotFoundException('Sucursal no encontrada.');
        } catch (DomainException $e) {
            $this->addFlash('warning', $e->getMessage());
        }

        return $this->redirectToRoute('site_show', ['clientId' => $clientId, 'siteId' => $siteId]);
    }

    #[IsGranted('site.update')]
    #[Route('/{siteId}/reactivate', name: 'site_reactivate', methods: ['POST'])]
    public function reactivateSite(
        string $clientId,
        string $siteId,
        ReactivateSiteHandler $reactivate,
    ): Response {
        try {
            ($reactivate)(new ReactivateSiteCommand($siteId));
            $this->addFlash('success', 'Sucursal reactivada.');
        } catch (SiteNotFoundException) {
            throw $this->createNotFoundException('Sucursal no encontrada.');
        } catch (DomainException $e) {
            $this->addFlash('warning', $e->getMessage());
        }

        return $this->redirectToRoute('site_show', ['clientId' => $clientId, 'siteId' => $siteId]);
    }
}
