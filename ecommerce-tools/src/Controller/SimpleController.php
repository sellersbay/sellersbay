<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class SimpleController extends AbstractController
{
    public function hello()
    {
        return new Response('Hello from SimpleController!');
    }
}