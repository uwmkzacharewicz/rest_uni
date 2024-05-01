<?php
namespace App\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AccessDeniedListener
{
    public function onKernelException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();

        if ($exception instanceof AccessDeniedHttpException) {
            $response = new JsonResponse([
                'error' => 'Access Denied',
                'message' => 'You do not have the necessary permissions to access this resource.'
            ], 403);
        } elseif ($exception instanceof AuthenticationException) {
            $response = new JsonResponse([
                'error' => 'Authentication Required',
                'message' => 'You do not have the necessary permissions to access this resource.'
            ], 401);
        } else {
            return;
        }

        $event->setResponse($response);
    }
}