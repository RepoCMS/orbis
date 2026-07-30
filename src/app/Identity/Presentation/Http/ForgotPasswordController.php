<?php

declare(strict_types=1);

namespace App\Identity\Presentation\Http;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/forgot-password')]
final class ForgotPasswordController extends AbstractController
{
    #[Route('', name: 'app_forgot_password_form', methods: ['GET'])]
    public function forgotPasswordForm(): Response
    {
        return $this->render('security/forgot_password.html.twig');
    }

    #[Route('/reset-password', name: 'app_reset_password_form', methods: ['GET'])]
    public function resetPasswordForm(Request $request): Response
    {
        $token = trim((string) ($request->query->get('token') ?? ''));

        return $this->render('reset_password.html.twig', [
            'token' => $token,
        ]);
    }
}
