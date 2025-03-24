<?php
// filepath: c:\Users\rober\projects\roboseo2\src\Controller\InfoController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/info')]
class InfoController extends AbstractController
{
    #[Route('', name: 'app_info', methods: ['GET'])]
    public function info(): Response
    {
        $systemInfo = [
            'PHP Version' => PHP_VERSION,
            'Symfony Version' => \Symfony\Component\HttpKernel\Kernel::VERSION,
            'Operating System' => PHP_OS,
            'Server Software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'Database' => 'MySQL/MariaDB 10.8.3',
            'Environment' => $_ENV['APP_ENV'] ?? 'Unknown'
        ];

        return $this->render('info/index.html.twig', [
            'systemInfo' => $systemInfo
        ]);
    }
}