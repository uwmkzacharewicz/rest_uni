<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;

class AccessDeniedHandler implements AccessDeniedHandlerInterface
{
    public function handle(Request $request, AccessDeniedException $accessDeniedException): ?Response
    {
        return new JsonResponse([
            'error' => 'Odmowa dostępu.',
            'message' => 'Nie masz uprawnień, aby uzyskać dostęp do tego zasobu.',
            'code' => Response::HTTP_FORBIDDEN
        ], Response::HTTP_FORBIDDEN);
    }
}










?>