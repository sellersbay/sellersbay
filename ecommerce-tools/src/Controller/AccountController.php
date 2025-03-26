<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\PasswordChangeFormType;
use App\Form\ProfileFormType;
use App\Service\IntegrationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class AccountController extends AbstractController
{
    #[Route('/account', name: 'app_account')]
    #[IsGranted('ROLE_USER')]
    public function index(IntegrationService $integrationService): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Get integration statuses for the connected services section
        $integrationStatuses = $integrationService->getIntegrationStatuses($user);
        
        return $this->render('account/index.html.twig', [
            'user' => $user,
            'integration_statuses' => $integrationStatuses,
        ]);
    }
    
    #[Route('/account/edit-profile', name: 'app_edit_profile')]
    #[IsGranted('ROLE_USER')]
    public function editProfile(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $form = $this->createForm(ProfileFormType::class, $user);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Update the user's updated_at timestamp
            $user->setUpdatedAt(new \DateTimeImmutable());
            
            $entityManager->persist($user);
            $entityManager->flush();
            
            $this->addFlash('success', 'Your profile has been updated successfully.');
            
            return $this->redirectToRoute('app_account');
        }
        
        return $this->render('account/edit_profile.html.twig', [
            'profileForm' => $form,
        ]);
    }
    
    #[Route('/account/change-password', name: 'app_change_password')]
    #[IsGranted('ROLE_USER')]
    public function changePassword(
        Request $request, 
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $form = $this->createForm(PasswordChangeFormType::class);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Verify current password
            $currentPassword = $form->get('currentPassword')->getData();
            if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                $this->addFlash('error', 'Current password is incorrect.');
                
                return $this->redirectToRoute('app_change_password');
            }
            
            // Set new password
            $newPassword = $form->get('newPassword')->getData();
            $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
            
            // Update the user's updated_at timestamp
            $user->setUpdatedAt(new \DateTimeImmutable());
            
            $entityManager->persist($user);
            $entityManager->flush();
            
            $this->addFlash('success', 'Your password has been changed successfully.');
            
            return $this->redirectToRoute('app_account');
        }
        
        return $this->render('account/change_password.html.twig', [
            'passwordForm' => $form,
        ]);
    }
    
    #[Route('/account/verify-email', name: 'app_verify_email')]
    #[IsGranted('ROLE_USER')]
    public function verifyEmail(Request $request): Response
    {
        return $this->render('account/verify_email.html.twig');
    }
    
    #[Route('/account/verify-email/send', name: 'app_verify_email_send', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function verifyEmailSend(Request $request, MailerInterface $mailer, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // In a real implementation, you'd generate a secure verification token,
        // store it in the database along with an expiration time.
        // For simplicity in this demo, we'll simulate verification.
        
        // Generate a simple demonstration verification token
        $verificationToken = bin2hex(random_bytes(16));
        
        // Create verification URL
        $verificationUrl = $this->generateUrl(
            'app_verify_email_confirm', 
            ['token' => $verificationToken],
            0 // Reference type absolute URL
        );
        
        // Compose email
        $email = (new Email())
            ->from('noreply@roboseo.com')
            ->to($user->getEmail())
            ->subject('Email Verification - RoboSEO')
            ->html(
                $this->renderView('account/email/verification_email.html.twig', [
                    'user' => $user,
                    'verification_url' => $verificationUrl
                ])
            );
        
        // In a real implementation, you would actually send this email
        // For this demo, we'll just mark the user as verified without sending
        
        // Mark user as verified
        $user->setIsVerified(true);
        $user->setUpdatedAt(new \DateTimeImmutable());
        $entityManager->persist($user);
        $entityManager->flush();
        
        $this->addFlash('success', 'Your email has been verified successfully! In a production environment, a verification email would be sent.');
        
        return $this->redirectToRoute('app_verify_email');
    }
    
    #[Route('/account/verify-email/confirm/{token}', name: 'app_verify_email_confirm')]
    public function verifyEmailConfirm(string $token, EntityManagerInterface $entityManager): Response
    {
        // In a real implementation, you would validate the token against the stored token
        // For this demo, we'll just mark the user as verified
        
        /** @var User|null $user */
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        
        $user->setIsVerified(true);
        $user->setUpdatedAt(new \DateTimeImmutable());
        $entityManager->persist($user);
        $entityManager->flush();
        
        $this->addFlash('success', 'Your email has been verified successfully!');
        
        return $this->redirectToRoute('app_account');
    }
}