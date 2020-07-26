<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2Client;
use KnpU\OAuth2ClientBundle\Security\Authenticator\SocialAuthenticator;

use League\OAuth2\Client\Provider\GithubResourceOwner;
use League\OAuth2\Client\Provider\GoogleUser;
use League\OAuth2\Client\Token\AccessToken;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Routing\RouterInterface;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;


class OAuthGoogleAuthenticator extends SocialAuthenticator
{
    /**
     * @param ClientRegistry $clientRegistry
     * @param EntityManagerInterface $em
     * @param UserRepository $userRepository
     * @param RouterInterface $router
     */
    public function __construct(
        ClientRegistry $clientRegistry,
        EntityManagerInterface $em,
        UserRepository $userRepository,
        RouterInterface $router
    )
    {
        $this->clientRegistry = $clientRegistry;
        $this->em = $em;
        $this->userRepository = $userRepository;
        $this->router = $router;
    }

    //***************************************
    //
    //        Interface of the class
    //
    //***************************************
    /**
     * @param Request $request
     * @param AuthenticationException|null $authException
     *
     * @return RedirectResponse|Response
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        return new RedirectResponse(
            '/connect/',
            Response::HTTP_TEMPORARY_REDIRECT
        );
    }

    /**
     * @param Request $request
     *
     * @return bool
     */
    public function supports(Request $request): bool
    {
        return $request->attributes->get('_route') === 'google_auth';
    }

    /**
     * @param Request $request
     *
     * @return AccessToken|mixed
     */
    public function getCredentials(Request $request)
    {
        return $this->fetchAccessToken($this->getGoogleClient());
    }

    /**
     * @param mixed $credentials
     * @param UserProviderInterface $userProvider
     *
     * @return User|null|UserInterface
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        /** @var GoogleUser $googleUser */
        $googleUser = $this->getGoogleClient()
            ->fetchUserFromToken($credentials);

        $email = $googleUser->getEmail();
        $clientId = $googleUser->getId();

        /** @var User $existingUser */
        $existingUser = $this->userRepository
            ->findOneBy(['clientId' => $clientId]);

        if ($existingUser)
        {
            return $existingUser;
        }

        /** @var User $user */
        $user = $this->userRepository
            ->findOneBy(['email' => $email]);
        if ($user)
        {
            $user->setClientId($googleUser->getId());
        }
        else // if (!$user)
        {
            $user = User::fromGoogleRequest(
                $clientId,
                $email,
                $googleUser->getName()
            );

            $this->em->persist($user);
            $this->em->flush();
        }

        return $user;
    }

    /**
     * @param Request $request
     * @param TokenInterface $token
     * @param string $providerKey
     *
     * @return null|Response
     */
    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
        $providerKey
    ):  ?Response
    {
        return new RedirectResponse($this->router->generate('blog_posts'));
    }

    /**
     * @param Request $request
     * @param AuthenticationException $exception
     *
     * @return null|Response|void
     */
    public function onAuthenticationFailure(
        Request $request,
        AuthenticationException $exception
    ):  ?Response
    {
        return new Response('Authentication failed', 403);
    }

    /**
     * @return \KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface
     */
    public function getGoogleClient(): \KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface
    {
        return $this->clientRegistry->getClient('google');
    }

    /**
     * @return bool
     */
    public function supportsRememberMe(): bool
    {
        return true;
    }


    //***************************************
    //
    //          Data of the class
    //
    //***************************************
    /**
     * @var ClientRegistry
     */
    private $clientRegistry;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var RouterInterface
     */
    private $router;


}