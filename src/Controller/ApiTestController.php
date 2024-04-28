<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

use OpenApi\Attributes as OA;
use Symfony\Component\Routing\Annotation\Route;

use Nelmio\ApiDocBundle\Annotation\Model;



class ApiTestController extends AbstractController
{
    /**
     * List the rewards of the specified user.
     *
     * This call takes into account all confirmed awards, but not pending or refused awards.
     * 
     */
    #[Route('/api/test1', methods: ['GET'])]
    #[OA\Parameter(
        name: 'order',
        in: 'query',
        description: 'The field used to order rewards',
        schema: new OA\Schema(type: 'string')
    )]
    public function index(): Response
    {
        return $this->render('api_test/index.html.twig', [
            'controller_name' => 'ApiTestController',
        ]);
    }
}
