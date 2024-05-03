<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;

use OpenApi\Attributes as OA;

class SecurityController extends AbstractController
{
    #[OA\Tag(name: "Użytkownicy")]
    #[OA\Response(
        response: 201,
        description: 'Poprawnie zalogowano użytkownika',
        content: new OA\JsonContent(ref: "#/components/schemas/User")
    )]
    #[OA\RequestBody(
        description: 'Dane użytkownika do zalogowania',
        required: true,
        content: new OA\JsonContent(ref: "#/components/schemas/User")
    )]    
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
