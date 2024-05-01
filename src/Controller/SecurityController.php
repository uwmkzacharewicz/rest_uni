<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class SecurityController extends AbstractController
{
    
    public function login(Request $request, UserInterface $user)
    {
        return $this->json([
            'username' => $user->getUsername(),
            'roles' => $user->getRoles(),
            'token' => $this->container->get('lexik_jwt_authentication.encoder')
                ->encode(['username' => $user->getUsername()]),
        ]);
    }
}
