<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\IntegrationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/integrations')]
#[IsGranted('ROLE_USER')]
class IntegrationController extends AbstractController
{
    private $integrationService;
    
    public function __construct(IntegrationService $integrationService)
    {
        $this->integrationService = $integrationService;
    }
    
    #[Route('/', name: 'app_integrations_manage')]
    public function manage(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Get current integration statuses
        $integrationStatuses = $this->integrationService->getIntegrationStatuses($user);
        
        return $this->render('integrations/manage.html.twig', [
            'integration_statuses' => $integrationStatuses
        ]);
    }
}