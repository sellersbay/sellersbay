<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\SecurityBundle\Security;

class SupportController extends AbstractController
{
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    #[Route('/support', name: 'app_support')]
    public function index(): Response
    {
        // Get the current user if logged in
        $user = $this->security->getUser();
        
        // Default values
        $userEmail = '';
        $userName = '';
        
        // Check if user is our App\Entity\User class
        if ($user instanceof \App\Entity\User) {
            $userEmail = $user->getEmail();
            $userName = $user->getFullName(); // Using the getFullName() method already defined in User entity
        }

        return $this->render('support/index.html.twig', [
            'user_email' => $userEmail,
            'user_name' => $userName
        ]);
    }

    #[Route('/support/submit', name: 'app_support_submit', methods: ['POST'])]
    public function submit(Request $request): Response
    {
        // Get form data
        $name = $request->request->get('name');
        $email = $request->request->get('email');
        $subject = $request->request->get('subject');
        $message = $request->request->get('message');
        
        // Basic validation
        if (empty($name) || empty($email) || empty($subject) || empty($message)) {
            $this->addFlash('error', 'All fields are required');
            return $this->redirectToRoute('app_support');
        }
        
        try {
            // In a real application, you would process the form data here
            // For example, sending an email or storing in database
            // This is a simplified version
            
            // Log the support request (you could send an email here)
            error_log("Support request received from {$name} ({$email}): {$subject}");
            
            $this->addFlash('success', 'Your support request has been sent successfully. We will respond as soon as possible.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'There was a problem sending your support request. Please try again later.');
        }
        
        return $this->redirectToRoute('app_support');
    }
}