<?php

declare(strict_types=1);

namespace App\Identity\Presentation\Http;

use App\Identity\Application\Command\VerifyUserByToken\VerifyUserByTokenCommand;
use App\Identity\Application\Command\VerifyUserByToken\VerifyUserByTokenHandler;
use App\Identity\Domain\Exceptions\TokenException;
use App\Shared\Exceptions\DomainException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controlador público para consumir tokens de verificación de cuenta.
 * Accesible sin autenticación (el token ES la credencial).
 */
final class VerificationController extends AbstractController
{
    #[Route('/verify/{token}', name: 'account_verify', methods: ['GET'])]
    public function verify(string $token, VerifyUserByTokenHandler $verifyByToken): Response
    {
        try {
            ($verifyByToken)(new VerifyUserByTokenCommand($token));

            return $this->render('verification/success.html.twig');
        } catch (TokenException $e) {
            return $this->render('verification/error.html.twig', [
                'message' => $e->getMessage(),
            ], new Response(null, Response::HTTP_UNPROCESSABLE_ENTITY));
        } catch (DomainException $e) {
            return $this->render('verification/error.html.twig', [
                'message' => $e->getMessage(),
            ], new Response(null, Response::HTTP_UNPROCESSABLE_ENTITY));
        }
    }
}
