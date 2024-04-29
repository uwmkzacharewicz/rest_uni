<?php

namespace App\Controller\Api;

use App\Entity\Student;
use App\Entity\Teacher;
use App\Entity\Course;
use App\Entity\Enrollment;
use App\Security\Role;
use App\Service\EntityService;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use JMS\Serializer\SerializerBuilder;
use JMS\Serializer\SerializerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;

use Psr\Log\LoggerInterface;
use OpenApi\Attributes as OA;

#[Route("/api", "")]
class UserController extends AbstractController
{
    private $urlGenerator;
    private $serializer;
    private $entityService;

    public function __construct(EntityService $entityService, UrlGeneratorInterface $urlGenerator, SerializerInterface $serializer) {

            $this->entityService = $entityService;
            $this->urlGenerator = $urlGenerator;
            $this->serializer = $serializer;
    }
    
    /**
     * Wyświetla listę użytkowników.
     *
     * Wywołanie wyświetla wszystkich użytkowników wraz z ich linkiem do szczegółów.
     * 
     */
    #[Route('/users', name: 'api_users', methods: ['GET'])]
    public function getUsers() : Response
    {
        $users = $this->entityService->findAllUsers();

        $data = [];

        $linksConfig = [
            'self' => [
                'route' => 'api_users_id',
                'param' => 'id',
                'method' => 'GET'
            ],
            'login' => [
                'route' => 'api_users_login',
                'param' => 'id',
                'method' => 'GET'
            ]
        ];
       
        foreach ($users as $user) {
            $userData = $user->toArray();
            $userData['_links'] = $this->entityService->generateHateoasLinks($user, $linksConfig, $this->urlGenerator);
            $data[] = $userData;
            }    

        $jsonContent = $this->serializer->serialize($data, 'json', \JMS\Serializer\SerializationContext::create()->setSerializeNull(true));
        return new Response($jsonContent, 200, ['Content-Type' => 'application/json']);
        return $this->json($data);

    }

    /**
     * Wyświetla szczegóły użytkownika.
     *
     * Wywołanie wyświetla szczegóły użytkownika o podanym id.
     * 
     */
    #[Route('/users/{id}', name: 'api_users_id', methods: ['GET'])]
    public function getUserById(int $id) : Response
    {
        $user = $this->entityService->findUser($id);
        if (!$user) {
            throw new HttpException(404, 'User not found');
        }
        return new JsonResponse([
            'user' => $user
        ]);
    }



    /**
     * Loguje użytkownika.
     *
     * Wywołanie loguje użytkownika o podanym id.
     * 
     */
    #[Route('/users/{id}/login', name: 'api_users_login', methods: ['GET'])]
    public function login(int $id) : Response
    {
        $user = $this->entityService->findUser($id);
        if (!$user) {
            throw new HttpException(404, 'User not found');
        }
        return new JsonResponse([
            'user' => $user
        ]);
    }

    
    
    
   
}
