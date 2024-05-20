<?php

namespace App\Controller\Api;

use App\Service\UserService;
use App\Service\UtilityService;
use App\Security\Role;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use JMS\Serializer\SerializerInterface;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Attributes as OA;

use App\Exception\CustomException;
use Exception;


#[OA\Tag(name: "Użytkownicy")]
#[Security(name: 'Bearer')]
#[IsGranted('ROLE_ADMIN')]
#[OA\Response(response: 200, description: 'OK')]
#[OA\Response(response: 201, description: 'Zasób został dodany')]
#[OA\Response(response: 400, description: 'Błąd w przesłanych danych')]
#[OA\Response(response: 404, description: 'Zasób nie znaleziony')]
#[OA\Response(response: 409, description: 'Konflikt danych')]
#[OA\Response(response: 500, description: 'Błąd serwera')]
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
    #[Route('/users/{id}', name: 'api_users_id', methods: ['GET'])]
    public function getUserbyId(int $id) : Response
    {
        $user = $this->userService->findUser($id);

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
    #[OA\RequestBody(description: 'Dane nowego użytkownika', required: true, content: new OA\JsonContent(ref: "#/components/schemas/NewUser"))]    
    #[Route('/users', name: 'api_users_add', methods: ['POST'])]
    public function addUser(Request $request) : Response
    {
        try {
            // Pobranie i walidacja danych
            $data = $this->utilityService->validateAndDecodeJson($request, ['username', 'password', 'roles']);
        } catch (\Exception $e) {
            // Obsługa wyjątków
            return $this->utilityService->createErrorResponse('Użytkownik nie został dodany', 'Nie przekazano wymaganych danych', JsonResponse::HTTP_BAD_REQUEST);
        }

       // Sprawdź, czy użytkownik o danym username już istnieje
        $existingUser = $this->userService->findUserByUsername($data['username']);
        if ($existingUser) {
            return $this->utilityService->createErrorResponse('Użytkownik nie został dodany', 'Użytkownik o podanej nazwie już istnieje', JsonResponse::HTTP_CONFLICT);
        }
       // utworzenie user
       $newUser = $this->userService->addUser($data['username'], $data['password'], $data['roles']);  
       
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
       $userData['_links'] = $this->utilityService->generateHateoasLinks($newUser, $linksConfig);
 
        return $this->utilityService->createSuccessResponse('Dodano nowego użytkownika.', ['user' => $userData], JsonResponse::HTTP_CREATED);
    }


    /**
     * Edytowanie użytkownika.
     *
     * Wywołanie edycji użytkownika o podanym id lub tworzy nowego użytkownika.
     * 
     */
   
    #[OA\RequestBody(description: 'Dane użytkownika do aktualizacji', required: true, content: new OA\JsonContent(ref: "#/components/schemas/NewUser"))] 
    #[Route('/users/{id}', name: 'api_users_edit', methods: ['PUT'])]
    public function editUser(Request $request, int $id): Response
    {

        try {
            // Pobranie i walidacja danych
            $data = $this->utilityService->validateAndDecodeJson($request, ['username', 'password']);
        } catch (\Exception $e) {
            // Obsługa wyjątków
            return $this->utilityService->createErrorResponse('Użytkownik nie został edytowany', 'Nie przekazano wymaganych danych', Response::HTTP_BAD_REQUEST);
        }

        $roles = $data['roles'] ?? [Role::ROLE_USER];

        try {

            $user = $this->userService->editUser($id, $data['username'], $data['password'], $roles);
            
            return $this->utilityService->createSuccessResponse('Edytowano użytkownika.', ['user' => $user], Response::HTTP_CREATED);

        } catch (CustomException $e) {     
            return $this->utilityService->createErrorResponse('Użytkownik nie został zaktualizowany', $e->getMessage(), $e->getStatusCode());
        } 
        // catch (Exception $e) {
        //     return new JsonResponse([
        //         'error' => 'Wystąpił błąd',
        //         'message' => $e->getMessage()
        //     ], Response::HTTP_INTERNAL_SERVER_ERROR);
        // } 
}

    /**
     * Aktualizacja użytkownika.
     *
     * Wywołanie aktualizuje użytkownika o podanym id.
     * 
     */
    
    #[OA\RequestBody( description: 'Dane użytkownika do aktualizacji', required: true, content: new OA\JsonContent(ref: "#/components/schemas/Roles") )]
    #[Route('/users/{id}', name: 'api_users_update', methods: ['PATCH'])]
    public function updateUser(Request $request, int $id): Response
    {
        $data = json_decode($request->getContent(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->utilityService->createErrorResponse('Użytkownik nie został zaktualizowany.', 'Nie przekazano wymaganych danych', Response::HTTP_BAD_REQUEST);
        }

        try {
            $updatedUser = $this->userService->updateUserFields($id, $data);

            $linksConfig = [
                'self' => [
                    'route' => 'api_users_id',
                    'param' => 'id',
                    'method' => 'GET'
                ]
            ];
        
            $userData['user_info'] = $updatedUser->toArray();
            $userData['_links'] = $this->utilityService->generateHateoasLinks($updatedUser, $linksConfig);        
           
            return $this->utilityService->createSuccessResponse('Zauktualizowano użytkownika.', ['user' => $userData], Response::HTTP_OK);
        
        } catch (CustomException $e) {     
            return $this->utilityService->createErrorResponse('Użytkownik nie został zaktualizowany', $e->getMessage(), $e->getStatusCode());
        } catch (Exception $e) {
            return new JsonResponse([
                'error' => 'Wystąpił błąd',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }       
  
    }
   

    /**
     * Usuwa użytkownika.
     *
     * Wywołanie usuwa użytkownika o podanym id.
     * 
     */
    #[Route('/users/{id}', name: 'api_users_delete', methods: ['DELETE'])]
    public function deleteUser(int $id): Response
    {
        try {
            $this->userService->deleteUser($id);
            return $this->json(['status' => 'Usunięto użytkownika.', 'code' => Response::HTTP_OK], Response::HTTP_OK);
        } catch (CustomException $e) {     
            return $this->utilityService->createErrorResponse('Użytkownik nie został usunięty', $e->getMessage(), $e->getStatusCode());
        } 
        catch (Exception $e) {
            return new JsonResponse([
                'error' => 'Wystąpił błąd',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        } 
    }

    // /**
    //  * Loguje użytkownika.
    //  *
    //  * Wywołanie loguje użytkownika o podanym id.
    //  * 
    //  */
    // #[OA\RequestBody(
    //     description: 'Dane użytkownika do aktualizacji',
    //     required: true,
    //     content: new OA\JsonContent(ref: "#/components/schemas/User")
    // )]
    // #[Route('/users/{id}/login', name: 'api_users_login', methods: ['POST'])]
    // public function loginUser(int $id, Request $request) : Response
    // {
    //     $user = $this->userService->findUser($id);

    //     if (!$user) {
    //         throw new HttpException(404, 'User not found');
    //     }

    //     // Pobranie danych z żądania
    //     $data = json_decode($request->getContent(), true);
    //     if (json_last_error() !== JSON_ERROR_NONE) {
    //         throw new \Exception('JSON decode error: ' . json_last_error_msg());
    //     }

    //     // Sprawdzenie, czy przekazano hasło
    //     if (!isset($data['password'])) {
    //         return $this->json(['error' => 'Password is required'], JsonResponse::HTTP_BAD_REQUEST);
    //     }

    //     // Sprawdzenie poprawności hasła
    //     if (!$this->userService->isPasswordValid($user, $data['password'])) {
    //         return $this->json(['error' => 'Invalid password'], JsonResponse::HTTP_UNAUTHORIZED);
    //     }

    //     return $this->json([
    //         'username' => $user->getUsername(),
    //         'roles' => $user->getRoles(),
    //         'token' => $this->container->get('lexik_jwt_authentication.encoder')
    //             ->encode(['username' => $user->getUsername()]),
    //     ]);
    // }

   
}
