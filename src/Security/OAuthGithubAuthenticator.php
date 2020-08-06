<?php

declare(strict_types=1);

namespace App\Security;

use App\Controller\OAuthController;
use App\Entity\User;
use App\Repository\UserRepository;

use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2Client;
use KnpU\OAuth2ClientBundle\Security\Authenticator\SocialAuthenticator;

use League\OAuth2\Client\Provider\GithubResourceOwner;
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

final class OAuthGithubAuthenticator extends SocialAuthenticator
{
    /**
     * @param ClientRegistry $clientRegistry
     * @param EntityManagerInterface $em
     * @param UserRepository $userRepository
     * @param RouterInterface $router
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        ClientRegistry $clientRegistry,
        EntityManagerInterface $em,
        UserRepository $userRepository,
        RouterInterface $router,
        EventDispatcherInterface $eventDispatcher
    )
    {
        $this->clientRegistry = $clientRegistry;
        $this->em = $em;
        $this->userRepository = $userRepository;
        $this->router = $router;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param Request $request
     * @param AuthenticationException|null $authException
     *
     * @return RedirectResponse|Response
     */
    public function start(
        Request $request,
        AuthenticationException $authException = null
    ): Response
    {
        return new RedirectResponse($this->router->generate('login'));
    }

    /**
     * @param Request $request
     *
     * @return bool
     */
    public function supports(Request $request)
    {
        return $request->attributes->get('_route') === 'github_auth';
    }

    /**
     * @param Request $request
     *
     * @return AccessToken|mixed
     */
    public function getCredentials(Request $request)
    {

        $githubClient = $this->getGithubClient();
        return $this->fetchAccessToken($githubClient);
    }

    /**
     * @param mixed $credentials
     * @param UserProviderInterface $userProvider
     *
     * @return User|null|UserInterface
     *
     * @throws Exception
     */
    public function getUser($credentials, UserProviderInterface $userProvider): ?UserInterface
    {
        /** @var GithubResourceOwner $githubUser */
        $githubUser = $this->getGithubClient()
            ->fetchUserFromToken($credentials);
        $clientId = $githubUser->getId();

        /** @var User $existingUser */
        $existingUser = $this->userRepository
            ->findOneBy(['clientId' => $clientId]);

        if ($existingUser) {
            return $existingUser;
        }

        $nickName = $githubUser->getNickname();
        $githubUserData = $githubUser->toArray();

        $user = User::fromGithubRequest(
            (int)$clientId,
            $githubUserData['email'] ?? $githubUserData['login'],
            $nickName
        );

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    /**
     * @param Request $request
     * @param AuthenticationException $exception
     *
     * @return null|Response
     */
    public function onAuthenticationFailure(
        Request $request,
        AuthenticationException $exception
    ): ?Response
    {
        return new Response('Authentication failed', Response::HTTP_FORBIDDEN);
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
    ): ?Response
    {
        $locale = $locale = $request->cookies->get('localeWhenSignInWithGithub');     // get the user's locale from the cookie
        return new RedirectResponse($this->router->generate('blog_posts', ['_locale' => $locale]));
    }

    /**
     * @return OAuth2Client
     */
    private function getGithubClient(): OAuth2Client
    {
        return $this->clientRegistry->getClient('github');
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

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;
}





















