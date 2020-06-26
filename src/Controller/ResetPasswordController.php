<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\ResetPasswordRequest;
//use App\Form\ChangePasswordFormType;
use App\Form\ResetPasswordRequestFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


use Symfony\Component\Routing\Annotation\Route;
use App\Service\CodeGenerator;
//use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

//use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;



use Twig\Environment;
use Swift_Mailer;
use Swift_Message;

/**
 * @Route("/reset-password")
 */
class ResetPasswordController extends AbstractController
{

    private $resetPasswordHelper;

    /** @var Environment */
    private $twig;

    /** @var Swift_Mailer */
    private $mailer;

    /** @var string */
    private $userEmail;

    private $codeGenerator;


    public function __construct(
        Swift_Mailer $mailer,
        Environment $twig,
        CodeGenerator $codeGenerator
    )
    {
        $this->twig = $twig;
        $this->mailer = $mailer;
        $this->codeGenerator = $codeGenerator;      // to generate token
        $this->userEmail = 'defaultEmail@gmail.com';
    }

    /**
     * Display & process form to request a password reset.
     *
     * @Route("", name="app_forgot_password_request")
     */
    public function request(Request $request): Response
    {
        $form = $this->createForm(ResetPasswordRequestFormType::class);
        $form->handleRequest($request);

        $this->userEmail = $form->get('email')->getData();

        if ($form->isSubmitted() && $form->isValid()) {
            return $this->processSendingPasswordResetEmail(
                $this->userEmail
            );
        }

        return $this->render('reset_password/request.html.twig', [
            'requestForm' => $form->createView(),
        ]);
    }

    /*
     * Confirmation page after a user has requested a password reset.
     *
     * @Route("/check-email", name="app_check_email")
     *
    public function checkEmail(): Response
    {
        // We prevent users from directly accessing this page
        if (!$this->canCheckEmail()) {
            return $this->redirectToRoute('app_forgot_password_request');
        }

        return $this->render('reset_password/check_email.html.twig', [
            'tokenLifetime' => $this->resetPasswordHelper->getTokenLifetime(),
        ]);
    }
    */

    /**
     * Validates and process the reset URL that the user clicked in their email.
     *
     * @Route("/reset/{token}", name="app_reset_password")
     */
    //public function reset(Request $request, UserPasswordEncoderInterface $passwordEncoder, string $token = null): Response
    public function reset(Request $request, string $token = null): Response
    {
        $em = $this->getDoctrine()->getManager();


        if ($token) {
            // We store the token in session and remove it from the URL, to avoid the URL being
            // loaded in a browser and potentially leaking the token to 3rd party JavaScript.

            // save resetToken into DataBase
            $tokenEntity = new ResetPasswordRequest($this->userEmail, $token, new \DateTimeImmutable('now + 3'));
            $em->persist($tokenEntity);
            $em->flush();

            return $this->redirectToRoute('app_reset_password');
        }

        $token = $this->getDoctrine()->getRepository(ResetPasswordRequest::class)->loadTokenByUsername($this->userEmail);
        dd($token);

        if (null === $token) {
            throw $this->createNotFoundException('No reset password token found in the URL or in the session.');
        }

        try {
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface $e) {
            $this->addFlash('reset_password_error', sprintf(
                'There was a problem validating your reset request - %s',
                $e->getReason()
            ));

            return $this->redirectToRoute('app_forgot_password_request');
        }

        // The token is valid; allow the user to change their password.
        $form = $this->createForm(ChangePasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // A password reset token should be used only once, remove it.
            $this->resetPasswordHelper->removeResetRequest($token);

            // Encode the plain password, and set it.
            $encodedPassword = $passwordEncoder->encodePassword(
                $user,
                $form->get('plainPassword')->getData()
            );

            $user->setPassword($encodedPassword);
            $this->getDoctrine()->getManager()->flush();

            // The session is cleaned up after the password has been changed.
            $this->cleanSessionAfterReset();

            return $this->redirectToRoute('login');
        }

        return $this->render('reset_password/reset.html.twig', [
            'resetForm' => $form->createView(),
        ]);
    }

    /**
     * @param string $emailFormData
     * @return RedirectResponse|Response
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    private function processSendingPasswordResetEmail(string $emailFormData)
    {
        $message = new Swift_Message();
        $em = $this->getDoctrine()->getManager();

        // a boolean variable that indicates whether a user with such an e-mail exists in the database
        $isUserExists = $this->getDoctrine()->getRepository(User::class)->isUserExists($emailFormData);


        // Do not reveal whether a user account was found or not.
        if (!$isUserExists) {
            return $this->redirectToRoute('app_check_email');
        }

        // generate our token
        $resetToken = $this->codeGenerator->generateResetToken();

        // preparing the template of the page
        $messageBody = $this->twig->render('reset_password/email.html.twig', [
            'resetToken' => $resetToken,
            'tokenLifetime' => 3,           // our token is valid for three hours
        ]);

        $message
            ->setSubject('Resetting your password!')
            ->setFrom('test_address@gmail.com')
            ->setTo($emailFormData)
            ->setBody($messageBody, 'text/html');

        $this->mailer->send($message);


        $form = $this->createForm(ResetPasswordRequestFormType::class);
        return $this->render('reset_password/request.html.twig', [
            'requestForm' => $form->createView(),
        ]);
    }
}
