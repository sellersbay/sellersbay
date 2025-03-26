<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Csrf\Exception\TokenNotFoundException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class SupportController extends AbstractController
{
    private $security;
    private $csrfTokenManager;
    private $params;

    public function __construct(
        Security $security, 
        CsrfTokenManagerInterface $csrfTokenManager,
        ParameterBagInterface $params
    )
    {
        $this->security = $security;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->params = $params;
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

        // Pass environment information to the template to show appropriate messages
        $isLocalEnvironment = $this->isLocalEnvironment();

        return $this->render('support/index.html.twig', [
            'user_email' => $userEmail,
            'user_name' => $userName,
            'is_local' => $isLocalEnvironment
        ]);
    }

    #[Route('/support/submit', name: 'app_support_submit', methods: ['POST'])]
    public function submit(Request $request, MailerInterface $mailer): Response
    {
        // CSRF protection
        $token = $request->request->get('token');
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('support_form', $token))) {
            $this->addFlash('error', 'Invalid form submission, please try again.');
            return $this->redirectToRoute('app_support');
        }
        
        // Anti-spam honeypot check
        if ($request->request->get('website') !== '') {
            // This is likely a bot - silently redirect without error
            return $this->redirectToRoute('app_support');
        }
        
        // Get and sanitize form data
        $name = htmlspecialchars(trim($request->request->get('name', '')));
        $email = filter_var(trim($request->request->get('email', '')), FILTER_SANITIZE_EMAIL);
        $subject = htmlspecialchars(trim($request->request->get('subject', '')));
        $message = htmlspecialchars(trim($request->request->get('message', '')));
        $issueType = htmlspecialchars(trim($request->request->get('issue_type', '')));
        $priority = htmlspecialchars(trim($request->request->get('priority', 'medium')));
        $orderNumber = htmlspecialchars(trim($request->request->get('order_number', '')));
        $agreeTerms = $request->request->getBoolean('agree_terms');
        
        // Validate required fields
        if (empty($name) || empty($email) || empty($subject) || empty($message) || empty($issueType) || !$agreeTerms) {
            $this->addFlash('error', 'All required fields must be filled in.');
            return $this->redirectToRoute('app_support');
        }
        
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addFlash('error', 'Please enter a valid email address.');
            return $this->redirectToRoute('app_support');
        }
        
        // Prepare email content
        $emailBody = "New support request details:\n\n";
        $emailBody .= "Name: $name\n";
        $emailBody .= "Email: $email\n";
        $emailBody .= "Issue Type: $issueType\n";
        $emailBody .= "Priority: $priority\n";
        
        if (!empty($orderNumber)) {
            $emailBody .= "Order/Reference Number: $orderNumber\n";
        }
        
        $emailBody .= "Subject: $subject\n\n";
        $emailBody .= "Message:\n$message\n";
        
        // Check if we're in a local environment
        $isLocalEnvironment = $this->isLocalEnvironment();
        
        try {
            // Only attempt to send email if we're not in a local environment
            if (!$isLocalEnvironment) {
                $email = (new Email())
                    ->from('no-reply@sellersbay.com')
                    ->to('support@sellersbay.com')
                    ->subject("[Support Request] $subject")
                    ->text($emailBody);
                
                $mailer->send($email);
            }
            
            // Always log the support request for backup
            $filename = sys_get_temp_dir() . '/support_requests.log';
            file_put_contents(
                $filename, 
                date('[Y-m-d H:i:s]') . " Support request from {$name} ({$email}): {$subject}\n" . $emailBody . "\n\n",
                FILE_APPEND
            );
            
            if ($isLocalEnvironment) {
                $this->addFlash('success', 'Your support request has been received and logged (email sending is simulated in local environment). In a production environment, an email would be sent to support@sellersbay.com.');
                $this->addFlash('info', 'Support request logged to: ' . $filename);
            } else {
                $this->addFlash('success', 'Your support request has been sent successfully. We will respond as soon as possible.');
            }
        } catch (\Exception $e) {
            // Log the exception
            error_log('Support email error: ' . $e->getMessage());
            
            if ($isLocalEnvironment) {
                $this->addFlash('warning', 'Local environment detected. The form works correctly, but email sending is only available on a production server with proper mail configuration.');
                $this->addFlash('info', 'Your support request has been logged locally for testing purposes.');
            } else {
                $this->addFlash('error', 'There was a problem sending your support request. Please try again later.');
            }
        }
        
        return $this->redirectToRoute('app_support');
    }
    
    /**
     * Check if we're running in a local/development environment.
     * This helps us provide appropriate behavior when email sending isn't available.
     */
    private function isLocalEnvironment(): bool
    {
        $serverName = $_SERVER['SERVER_NAME'] ?? '';
        $serverAddr = $_SERVER['SERVER_ADDR'] ?? '';
        
        // Check for common local development indicators
        return (
            $serverName === 'localhost' || 
            $serverName === '127.0.0.1' ||
            strpos($serverName, '.local') !== false ||
            strpos($serverAddr, '127.0.0.1') === 0 ||
            strpos($serverAddr, '::1') === 0 ||
            $this->params->has('app.environment') && $this->params->get('app.environment') === 'dev'
        );
    }
}