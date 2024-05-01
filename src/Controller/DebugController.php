<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DebugController extends AbstractController
{
    #[Route('/debug', methods: ['GET'])]
    public function test(): Response
    {
        $variable = "Xdebug works!";
        dump($variable); // Umożliwia wyświetlenie zmiennej w profilerze Symfony

        return new Response('<html><body><h1>Debugging Test</h1></body></html>');
    }
}
