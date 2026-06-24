<?php

namespace App\Controller;

use App\Entity\Users;
use App\Form\RegisterType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;

class AuthController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('auth/login.html.twig', [
            'last_username' => $lastUsername,
            'error'         => $error,
        ]);
    }

    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        MailerInterface $mailer,
        UrlGeneratorInterface $urlGenerator
    ): Response {
        $user = new Users();

        $form = $this->createForm(RegisterType::class, $user);
        $form->handleRequest($request);

        $emailSent = false;

        if ($form->isSubmitted() && $form->isValid()) {
            $hashedPassword = $passwordHasher->hashPassword($user, $form->get('password')->getData());
            $user->setPassword($hashedPassword);
            $user->setRole('ROLE_ADMIN');

            $token = bin2hex(random_bytes(32));
            $user->setVerificationToken($token);
            $user->setIsVerified(false);

            $entityManager->persist($user);
            $entityManager->flush();

            $verificationUrl = $urlGenerator->generate(
                'app_verify_email',
                ['token' => $token],
                UrlGeneratorInterface::ABSOLUTE_URL
            );

            $email = (new Email())
                ->from('ayzchagra@gmail.com')
                ->to($user->getEmail())
                ->subject('Vérifiez votre adresse email')
                ->html(
                    $this->renderView('emails/verification.html.twig', [
                        'user'            => $user,
                        'verificationUrl' => $verificationUrl,
                    ])
                );

            $mailer->send($email);
            $emailSent = true;
        }

        return $this->render('auth/register.html.twig', [
            'form'      => $form->createView(),
            'emailSent' => $emailSent,
        ]);
    }

    #[Route('/verify-email/{token}', name: 'app_verify_email')]
    public function verifyEmail(
        string $token,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $entityManager->getRepository(Users::class)->findOneBy([
            'verificationToken' => $token,
        ]);

        if (!$user) {
            $this->addFlash('error', 'Lien de vérification invalide ou expiré.');
            return $this->redirectToRoute('app_login');
        }

        if ($user->isVerified()) {
            $this->addFlash('info', 'Votre compte est déjà vérifié.');
            return $this->redirectToRoute('app_login');
        }

        $user->setIsVerified(true);
        $user->setVerificationToken(null);
        $entityManager->flush();

        $this->addFlash('success', '✅ Votre compte est vérifié ! Vous pouvez maintenant vous connecter.');

        return $this->redirectToRoute('app_login');
    }

    #[Route('/forgot-password', name: 'app_forgot_password')]
    public function forgotPassword(
        Request $request,
        EntityManagerInterface $em,
        MailerInterface $mailer,
        UrlGeneratorInterface $urlGenerator
    ): Response {
        $emailSent = false;

        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $user  = $em->getRepository(Users::class)->findOneBy(['email' => $email]);

            if ($user) {
                $token = bin2hex(random_bytes(32));
                $user->setVerificationToken($token);
                $em->flush();

                $resetUrl = $urlGenerator->generate(
                    'app_reset_password',
                    ['token' => $token],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );

                $mail = (new Email())
                    ->from('ayzchagra@gmail.com')
                    ->to($user->getEmail())
                    ->subject('Réinitialisation de votre mot de passe')
                    ->html(
                        $this->renderView('emails/reset_password.html.twig', [
                            'user'     => $user,
                            'resetUrl' => $resetUrl,
                        ])
                    );

                $mailer->send($mail);
            }

            $emailSent = true;
        }

        return $this->render('auth/forgot_password.html.twig', [
            'emailSent' => $emailSent,
        ]);
    }

    #[Route('/reset-password/{token}', name: 'app_reset_password')]
    public function resetPassword(
        string $token,
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = $em->getRepository(Users::class)->findOneBy([
            'verificationToken' => $token,
        ]);

        if (!$user) {
            $this->addFlash('error', 'Lien invalide ou expiré.');
            return $this->redirectToRoute('app_forgot_password');
        }

        $form = $this->createFormBuilder()
            ->add('password', RepeatedType::class, [
                'type'            => PasswordType::class,
                'first_options'   => ['label' => 'Nouveau mot de passe'],
                'second_options'  => ['label' => 'Confirmer le mot de passe'],
                'invalid_message' => 'Les mots de passe ne correspondent pas.',
                'constraints'     => [
                    new NotBlank(['message' => 'Le mot de passe est requis.']),
                    new Length([
                        'min'        => 6,
                        'minMessage' => 'Le mot de passe doit contenir au moins {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newPassword = $form->get('password')->getData();
            $hashed      = $passwordHasher->hashPassword($user, $newPassword);

            $user->setPassword($hashed);
            $user->setVerificationToken(null);
            $em->flush();

            $this->addFlash('success', '✅ Mot de passe modifié avec succès ! Connectez-vous.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('auth/reset_password.html.twig', [
            'form'  => $form->createView(),
            'token' => $token,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void {}
}