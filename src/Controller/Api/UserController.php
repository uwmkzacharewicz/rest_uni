<?php

namespace App\Controller\Api;

use App\Entity\Student;
use App\Entity\Teacher;
use App\Entity\Course;
use App\Entity\Enrollment;
use App\Security\Role;
use App\Service\UserService;
use App\Service\UtilityService;



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
use Nelmio\ApiDocBundle\Annotation\Security;

use Psr\Log\LoggerInterface;
use OpenApi\Attributes as OA;


#[OA\Tag(name: "Użytkownicy")]
#[Security(name: 'Bearer')]
#[Route("/api", "")]
class UserController extends AbstractController
{
    private $serializer;
    private $userService;
    private $utilityService;

    public function __construct(UserService $userService, SerializerInterface $serializer, UtilityService $utilityService) {

            $this->userService = $userService;
            $this->serializer = $serializer;
            $this->utilityService = $utilityService;
    }
    
    /**
     * Wyświetla listę użytkowników.
     *
     * Wywołanie wyświetla wszystkich użytkowników wraz z ich linkiem do szczegółów.
     * 
     */
    
    #[OA\Response(response: 200, ref: '#/components/responses/UserList200')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound404')]
    #[Route('/users', name: 'api_users', methods: ['GET'])]
    public function getUsers() : Response
    {
        $users = $this->userService->findAllUsers();
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
                'method' => 'POST'
            ]
        ];
       
        foreach ($users as $user) {
            $userData = $user->toArray();            
            $userData['_links'] = $this->utilityService->generateHateoasLinks($user, $linksConfig);
            $data[] = $userData;
        }    

        $jsonContent = $this->utilityService->serializeJson($data);
        return new Response($jsonContent, 200, ['Content-Type' => 'application/json']);
    }

    /**
     * Wyświetla szczegóły użytkownika.
     *
     * Wywołanie wyświetla szczegóły użytkownika o podanym id.
     * 
     */
    
    #[OA\Response(response: 200, ref: '#/components/responses/UserList200')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound404')]
    #[Route('/users/{id}', name: 'api_users_id', methods: ['GET'])]
    public function getUserbyId(int $id) : Response
    {
        $users = $this->userService->findUser($id);
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
                'method' => 'POST'
            ]
        ];       

        $userData = $user->toArray();            
        $userData['_links'] = $this->utilityService->generateHateoasLinks($user, $linksConfig);
        $data[] = $userData;
        

        $jsonContent = $this->utilityService->serializeJson($data);
        return new Response($jsonContent, 200, ['Content-Type' => 'application/json']);
    }

    
    /**
     * Dodaje nowego użytkownika.
     *
     * Wywołanie dodaje nowego użytkownika.
     * 
     */
    #[OA\Response(response: 201, description: 'Poprawnie dodano użytkownika', content: new OA\JsonContent(ref: "#/components/responses/UserList200"))]
    //#[OA\Response(response: 400, description: 'Nie przekazano wymaganych danych', content: new OA\JsonContent(ref: "#/components/responses/Error400"))]
    //#[OA\Response(response: 404, description: 'Nie znaleziono', content: new OA\JsonContent(ref: "#/components/schemas/NotFound404"))]
    #[OA\RequestBody(description: 'Dane nowego użytkownika', required: true, content: new OA\JsonContent(ref: "#/components/schemas/NewUser"))]    
    //#[QA\Security(name: [['bearerAuth' => []]])]
    #[Route('/users', name: 'api_users_add', methods: ['POST'])]
    public function addUser(Request $request) : Response
    {
       // Pobranie danych z żądania
       $data = json_decode($request->getContent(), true);
       if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('JSON decode error: ' . json_last_error_msg());
        }

       // Sprawdzenie, czy przekazano wymagane dane
       if (!isset($data['username']) || !isset($data['password']) || !isset($data['roles'])){        
           // Jeśli nie przekazano wymaganych danych, zwróć odpowiedź z błędem 400
           return $this->json(['error' => 'Nie przekazano wymaganych danych'], JsonResponse::HTTP_BAD_REQUEST);
       }

       // utworzenie user
       $newUser = $this->entityService->addUser($data['username'], $data['password'], $data['roles']);  
       
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

       $userData = $newUser->toArray();
       $userData['_links'] = $this->entityService->generateHateoasLinks($newUser, $linksConfig, $this->urlGenerator);
       $data[] = $userData;

       $jsonContent = $this->utilityService->serializeJson($data);
        return new Response($jsonContent, 200, ['Content-Type' => 'application/json']);
    }


    /**
     * Aktualizacja lub tworzenie użytkownika.
     *
     * Wywołanie aktualizuje użytkownika o podanym id lub tworzy nowego użytkownika.
     * 
     */
    #[Route('/users/{id}', name: 'api_users_update', methods: ['PUT'])]
    
    #[OA\RequestBody(description: 'Dane użytkownika do aktualizacji', required: true, content: new OA\JsonContent(ref: "#/components/schemas/NewUser"))] 
    #[OA\Response(response: 200, ref: '#/components/responses/UserList200' )]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound404'
    )]
    public function updateUser(Request $request, int $id, LoggerInterface $logger): Response
    {
        $data = json_decode($request->getContent(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Błąd dekodowania JSON: ' . json_last_error_msg());
        }

        // Sprawdzenie, czy kluczowe dane zostały przekazane
        if (empty($data['username']) || empty($data['password'])) {           
            return $this->json(['error' => 'Wymagane są username i password.'], Response::HTTP_BAD_REQUEST);
        }

        $roles = $data['roles'] ?? [];

        try {
            $user = $this->userService->editUser($id, $data['username'], $data['password'], $roles);
            return $this->json(['status' => 'Użytkownik został zaktualizowany', 'user' => $user], Response::HTTP_OK);
        } catch (\Exception $e) {
            $logger->error('Błąd aktualizacji użytkownika: ' . $e->getMessage(), ['id' => $id]);
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Aktualizacja użytkownika.
     *
     * Wywołanie aktualizuje użytkownika o podanym id.
     * 
     */
    #[Route('/users/{id}', name: 'api_users_patch', methods: ['PATCH'])]
    #[OA\RequestBody(
        description: 'Dane użytkownika do aktualizacji',
        required: true,
        content: new OA\JsonContent(ref: "#/components/schemas/Roles")
    )]
    #[OA\Response(response: 200, ref: '#/components/responses/UserList200')]
    #[OA\Response(response: 404, ref: '#/components/responses/NotFound404')]
    public function patchUser(Request $request, int $id, LoggerInterface $logger): Response
    {
        $data = json_decode($request->getContent(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->userService->findUser($id);
        if (!$user) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        // Aktualizacja dostępnych pól
        if (isset($data['username'])) {
            $user->setUsername($data['username']);
        }
        if (isset($data['password'])) {
            // hashowanie hasła
            $password = $this->utilityService->hashPassword($user, $data['password']);
            $user->setPassword($password);
        }

        if (isset($data['roles'])) {
            $user->setRoles($data['roles']);
        }

        try {
            $updatedUser = $this->userService->updateUser($user);
            return $this->json(['status' => 'User updated', 'user' => $updatedUser], Response::HTTP_OK);
        } catch (\Exception $e) {
            $logger->error('Error updating user: ' . $e->getMessage(), ['id' => $id]);
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Usuwa użytkownika.
     *
     * Wywołanie usuwa użytkownika o podanym id.
     * 
     */
    #[Route('/users/{id}', name: 'api_users_delete', methods: ['DELETE'])]
    #[OA\Response(
        response: 200,
        ref: '#/components/responses/UserList200'
    )]
    #[OA\Response(
        response: 404,
        ref: '#/components/responses/NotFound404'
    )]
    public function deleteUser(int $id, LoggerInterface $logger): Response
    {
        try {
            $this->userService->deleteUser($id);
            return $this->json(['status' => 'User deleted'], Response::HTTP_OK);
        } catch (\Exception $e) {
            $logger->error('Error deleting user: ' . $e->getMessage(), ['id' => $id]);
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    


    /**
     * Loguje użytkownika.
     *
     * Wywołanie loguje użytkownika o podanym id.
     * 
     */
    #[Route('/users/{id}/login', name: 'api_users_login', methods: ['POST'])]
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
