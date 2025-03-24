<?php

namespace App\Twig;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('getUserCredits', [$this, 'getUserCredits']),
        ];
    }

    public function getUserCredits(): int
    {
        $user = $this->security->getUser();
        
        if ($user instanceof User) {
            return $user->getCredits();
        }
        
        return 0;
    }
}