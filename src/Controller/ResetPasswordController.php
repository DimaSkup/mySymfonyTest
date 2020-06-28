<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\ResetPasswordRequest;
use App\Form\ChangePasswordFormType;
use App\Form\ResetPasswordRequestFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

use App\Service\CodeGenerator;



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

    private $codeGenerator;
    private $tokenLifetime;


    public function __construct(
        Swift_Mailer $mailer,
        Environment $twig,
        CodeGenerator $codeGenerator
    )
    {
        $this->twig = $twig;
        $this->mailer = $mailer;
        $this->codeGenerator = $codeGenerator;  // to generate token
        $this->tokenLifetime = 3;               // set the lifetime of the token
    }

    /**
     * Display & process form to request a password reset.
     *
     * @Route("", name="app_forgot_password_request")
     */
    public function request(Request $request): Response
    {
        $this->getDoctrine()->getRepository(ResetPasswordRequest::class)->deleteExpiredToken();

        $form = $this->createForm(ResetPasswordRequestFormType::class);
        $form->handleRequest($request);

        // get user email address from the form and send a reset-password email to this address
        if ($form->isSubmitted() && $form->isValid())
        {
            // get the user's email address from the form
            $userEmail = $form->get('email')->getData();

            // save the user's email address in a cookie
            $response = new Response('Content', Response::HTTP_OK, ['content-type' => 'text/html']);
            $response->headers->setCookie(new Cookie('resetPasswordUserEmail', $userEmail, strtotime('now + 60 minutes')));
            $response->sendHeaders();

            return $this->processSendingPasswordResetEmail($userEmail);
        }

        return $this->render('reset_password/request.html.twig', [
            'requestForm' => $form->createView(),
        ]);
    }


    /**
     * Validates and process the reset URL that the user clicked in their email.
     *
     * @Route("/reset/{token}", name="app_reset_password")
     */
    public function reset(Request $request,  UserPasswordEncoderInterface $passwordEncoder, string $token = null): Response
    {
        $em = $this->getDoctrine()->getManager();
        // get user email address from the cookies
        $userEmail = $request->cookies->get('resetPasswordUserEmail');

        if ($token)
        {
           // We store the token in database and remove it from the URL, to avoid the URL being
           // loaded in a browser and potentially leaking the token to 3rd party JavaScript.
           $expiresAt = new \DateTimeImmutable("now + $this->tokenLifetime hours");     // set the token expires time
           $resetToken = new ResetPasswordRequest($userEmail, $token, $expiresAt);
           $em->persist($resetToken);                                                       // save resetToken into DataBase
           $em->flush();
           return $this->redirectToRoute('app_reset_password');
        }


        // download the token from the database
        $token = $this->getDoctrine()->getRepository(ResetPasswordRequest::class)->loadTokenByEmail($userEmail);
        $token = $token[0];

        // no such token founded in the database
        if (null === $token) {
            throw $this->createNotFoundException('No reset password token found in the URL or in the database.');
        }


        // download the user from the database by the token
        $user = $this->getDoctrine()->getRepository(ResetPasswordrequest::class)->validateTokenAndFetchUser($token);

        // The token is valid; allow the user to change their password.
        $form = $this->createForm(ChangePasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // A password reset token should be used only once, remove it.
            $em->remove($token);

            // Encode the plain password, and set it.
            $encodedPassword = $passwordEncoder->encodePassword(
                $user,
                $form->get('plainPassword')->getData()
            );

            $user->setPassword($encodedPassword);
            $this->getDoctrine()->getManager()->flush();

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

        // a boolean variable that indicates whether a user with such an e-mail address exists in the database
        $isUserExists = $this->getDoctrine()->getRepository(User::class)->isUserExists($emailFormData);


        // Do not reveal whether a user account was found or not.
        if (!$isUserExists) {
            return $this->redirectToRoute('app_check_email');
        }

        // generate our token
        $resetToken = $this->codeGenerator->generateResetToken();

        // preparing the template of the page which will be sent to the user by e-mail
        $messageBody = $this->twig->render('reset_password/email.html.twig', [
            'userEmail' => $emailFormData,
            'resetToken' => $resetToken,
            'tokenLifetime' => $this->tokenLifetime,           // our token is valid for three hours
        ]);

        // letter preparation
        $message
            ->setSubject('Resetting your password!')
            ->setFrom('test_address@gmail.com')
            ->setTo($emailFormData)
            ->setBody($messageBody, 'text/html');

        // sending an email
        $this->mailer->send($message);

        return $this->render('reset_password/check_email.html.twig', [
            'tokenLifetime' => $this->tokenLifetime,
        ]);

        $form = $this->createForm(ResetPasswordRequestFormType::class);
        return $this->render('reset_password/request.html.twig', [
            'requestForm' => $form->createView(),
        ]);
    }
}
