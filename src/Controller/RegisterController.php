<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Event\RegisteredUserEvent;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Service\CodeGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class RegisterController extends AbstractController
{
    /**
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param Request $request
     * @param CodeGenerator $codeGenerator
     * @param EventDispatcherInterface $eventDispatcher
     * @return Response
     */
    public function register(
        UserPasswordEncoderInterface $passwordEncoder,
        Request $request,
        //Mailer $mailer,
        CodeGenerator $codeGenerator,
        EventDispatcherInterface $eventDispatcher
    )
    {
        $user = new User();
        $form = $this->createForm(
            UserType::class,
            $user
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $form->getData();

            $password = $passwordEncoder->encodePassword(
                $user,
                $user->getPlainPassword()
            );

            $user->setPassword($password)
                 ->setConfirmationCode($codeGenerator->getConfirmationCode())
                 ->setUserBrowserData($request->headers->get('user-agent'))
                 ->setUserIp($request->getClientIp());

            if (strtolower($user->getUsername()) == 'admin')
            {
                return new Response('<html><body>There is admin already exists!</body></html>');
            }

            $em = $this->getDoctrine()->getManager();

            $em->persist($user);
            $em->flush();

            //$mailer->sendConfirmationMessage($user);
            $userRegisteredEvent = new RegisteredUserEvent($user);
            $eventDispatcher->dispatch($userRegisteredEvent, RegisteredUserEvent::NAME);
        }

        return $this->render('security/register.html.twig', [
            'form' => $form->createView()
        ]);
    }


    public function confirmEmail(UserRepository $userRepository, string $code)
    {
        /** @var User $user */
        $user = $userRepository->findOneBy(['confirmationCode' => $code]);
        $em = $this->getDoctrine()->getManager();

        if ($user === null) {
            return new Response('404');
        }

        $user->setEnabled(true);
        $user->setConfirmationCode('');

        $em->flush();

        return $this->render('security/account_confirm.html.twig', [
            'user' => $user,
        ]);
    }
}