<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TeacherController extends AbstractController
{
    #[Route('/api/teacher', name: 'app_api_teacher')]
    public function index(): Response
    {
        return $this->render('api/teacher/index.html.twig', [
            'controller_name' => 'TeacherController',
        ]);
    }
}
