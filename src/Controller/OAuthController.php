<?php

declare(strict_types=1);

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class OAuthController extends AbstractController
{
    /**
     * @param ClientRegistry $clientRegistry
     *
     * @return RedirectResponse
     *
     * @Route("/connect/google", name="connect_google_start")
     */
    public function redirectToGoogleConnect(ClientRegistry $clientRegistry)
    {
        return $clientRegistry
            ->getClient('google')
            ->redirect([
                'email', 'profile'
            ]);
    }

    /**
     * @Route("/google/auth", name="google_auth")
     *
     * @return JsonResponse|RedirectResponse
     */
    public function connectGoogleCheck()
    {
        if (!$this->getUser())
        {
            return new JsonResponse(['status' => false, 'message' => "User not found!"]);
        }
        else
        {
            return $this->redirectToRoute('blog_posts', ['page' => 1, '_locale' => 'ua']);
        }
    }
}
