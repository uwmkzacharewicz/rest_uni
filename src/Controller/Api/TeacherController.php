<?php

namespace App\Controller\Api;

use App\Entity\Teacher;
use App\Service\UtilityService;
use App\Service\TeacherService;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use JMS\Serializer\SerializerInterface;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Annotation\Model;

use App\Exception\CustomException;
use Exception;



#[OA\Tag(name: "Nauczyciele")]
#[Security(name: 'Bearer')]
#[IsGranted('ROLE_ADMIN')]
#[OA\Response(response: 200, description: 'OK')]
#[OA\Response(response: 201, description: 'Zasób został dodany')]
#[OA\Response(response: 400, description: 'Błąd w przesłanych danych')]
#[OA\Response(response: 404, description: 'Zasób nie znaleziony')]
#[OA\Response(response: 409, description: 'Konflikt danych')]
#[OA\Response(response: 500, description: 'Błąd serwera')]
#[Route("/api", "")]
class TeacherController extends AbstractController
{
    private $serializer;
    private $teacherService;
    private $utilityService;

    public function __construct(TeacherService $teacherService, SerializerInterface $serializer, UtilityService $utilityService) {

            $this->teacherService = $teacherService;
            $this->serializer = $serializer;
            $this->utilityService = $utilityService;
    }


     /**
     * Wyświetla listę nauczycieli.
     *
     * Wywołanie wyświetla wszystkich nauczycieli wraz z ich linkiem do szczegółów.
     * 
     */
    #[Route('/teachers', name: 'api_teachers', methods: ['GET'])]
    public function getTeachers() : Response
    {
        $teachers = $this->teacherService->findAllTeachers();
        $data = [];    

        foreach ($teachers as $teacher) {
            $idUser = $teacher->getUser() ? $teacher->getUser()->getId() : null;

            $linksConfig = [
                'self' => [
                    'route' => 'api_teachers_id',
                    'param' => 'id',
                    'method' => 'GET'
                ],
                'loginData' => [
                    'route' => 'api_users_id',
                    'param' => 'id',
                    'method' => 'GET',
                    'value' => $idUser
                ]
            ];

            $teacherData = $teacher->toArray();
            $teacherData['_links'] = $this->utilityService->generateHateoasLinks($teacher, $linksConfig);

            $data[] = $teacherData;
        }    

        $jsonContent = $this->utilityService->serializeJson($data);
        return new Response($jsonContent, 200, ['Content-Type' => 'application/json']);
    }

     /**
     * Wyświetla szczegóły nauczyciela.
     *
     * Wywołanie wyświetla szczegóły nauczyciela o podanym identyfikatorze.
     * 
     */   
    #[Route('/teachers/{id}', name: 'api_teachers_id', methods: ['GET'])]
    public function getTeacherById(int $id): Response
    {
        $teacher = $this->teacherService->findTeacher($id);
       
         // Sprawdzenie, czy teacher został znaleziony
         if (!$teacher) {
            // Jeśli nie znaleziono nauczyciela, zwróć odpowiedź z błędem 404
            return $this->json(['error' => 'Nie znaleziono nauczyciela o id ' . $id], Response::HTTP_NOT_FOUND);
        }

        $data = [];
        $idUser = $teacher->getUser() ? $teacher->getUser()->getId() : null;

        $linksConfig = [
            'self' => [
                'route' => 'api_teachers_id',
                'param' => 'id',
                'method' => 'GET'
            ],
            'loginData' => [
                'route' => 'api_users_id',
                'param' => 'id',
                'method' => 'GET',
                'value' => $idUser
            ],
            'allCourses' => [],
            'gradesToBeGiven' => [],
            'edit' => [
                'route' => 'api_teachers_id',
                'param' => 'id',
                'method' => 'PUT'
            ],
            'update' => [
                'route' => 'api_teachers_id',
                'param' => 'id',
                'method' => 'PATCH'
            ],
            'delete' => [
                'route' => 'api_teachers_id',
                'param' => 'id',
                'method' => 'DELETE'
            ],
            'create' => [
                'route' => 'api_teachers_add',
                'method' => 'POST'
            ]
        ];

        // Dodajemy kursy prowadzone przez nauczyciela do sekcji allCourses
        $courses = $this->teacherService->findCoursesByTeacher($id);
        foreach ($courses as $course) {
            $courseId = $course->getId();
            $linksConfig['allCourses']['course_' . $courseId] = [
                'route' => 'api_courses_id',
                'param' => 'id',
                'method' => 'GET',
                'value' => $courseId
            ];
        }

        // Dodajemy zapisy, gdzie nauczyciel musi wystawić oceny do sekcji gradesToBeGiven
        $enrollmentsToBeGraded = $this->teacherService->findEnrollmentsToGrade($id);
        foreach ($enrollmentsToBeGraded as $enrollment) {
            $enrollmentId = $enrollment->getId();
            $linksConfig['gradesToBeGiven']['enrollment_' . $enrollmentId] = [
                'route' => 'api_enrollments_id',
                'param' => 'id',
                'method' => 'GET',
                'value' => $enrollmentId
            ];
        }

        $data = $teacher->toArray();
        $data['user'] = $teacher->getUser() ? $teacher->getUser()->toArray() : null;
        $data['_links'] = $this->utilityService->generateHateoasLinks($teacher, $linksConfig);

        $jsonContent = $this->utilityService->serializeJson($data);
        return new Response($jsonContent, 200, ['Content-Type' => 'application/json']);
    }

    
    /**
     * Dodaje nowego nauczyciela.
     *
     * Wywołanie dodaje nowego nauczyciela na podstawie przekazanych danych.
     * 
     */ 
    #[Route('/teachers', name: 'api_teachers_add', methods: ['POST'])]
    public function addTeacher(Request $request): Response
    {
        try {
            // Pobranie i walidacja danych
            $data = $this->utilityService->validateAndDecodeJson($request, ['name', 'email', 'specialization' ,'username', 'password']);
        } catch (\Exception $e) {
            // Obsługa wyjątków
            return $this->utilityService->createErrorResponse('Nauczyciel nie został dodany', 'Nie przekazano wymaganych danych', Response::HTTP_BAD_REQUEST);
        }
       
        // Dodanie nowego nauczyciela

        try {
            $newTeacher = $this->teacherService->createTeacherWithPassword($data['name'], $data['email'], $data['specialization'] ,$data['username'], $data['password']);
        } catch (CustomException $e) {
            return $this->utilityService->createErrorResponse('Nauczyciel nie został dodany', $e->getMessage(), $e->getStatusCode());
        }
        
        $idUser = $newTeacher->getUser() ? $newTeacher->getUser()->getId() : null;

        $data = [];

        $linksConfig = [
            'self' => [
                'route' => 'api_teachers_id',
                'param' => 'id',
                'method' => 'GET'
            ],
            'loginData' => [
                'route' => 'api_users_id',
                'param' => 'id',
                'method' => 'GET',
                'value' => $idUser
            ],
        ];

        $teacherData = $newTeacher->toArray();
        $teacherData['_links'] = $this->utilityService->generateHateoasLinks($newTeacher, $linksConfig);
        $data[] = $teacherData;

        return $this->utilityService->createSuccessResponse('Dodano nowego nauczyciela.', ['teacher' => $data], Response::HTTP_CREATED);

    }

    /**
     * Edytuje nauczyciela.
     *
     * Wywołanie pozwala na edycję danych nauczyciela.
     * 
     */ 
    #[OA\RequestBody(
        description: 'Dane nauczyciela do aktualizacji',
        required: true,
        content: new OA\JsonContent(ref: "#/components/schemas/EditTeacher")
    )] 
    #[Route('/teachers/{id}', name: 'api_teachers_edit', methods: ['PUT'])]
    public function editTeacher(int $id, Request $request): Response
    {

        try{
            //pobieranie i walidacja danych
            $data = $this->utilityService->validateAndDecodeJson($request, ['name', 'email', 'specialization']);
        } catch (\Exception $e) {
            return $this->utilityService->createErrorResponse('Nauczyciel nie został edytowany', 'Nie przekazano wymaganych danych', Response::HTTP_BAD_REQUEST);
        }

        try {
            $editedTeacher = $this->teacherService->editTeacher($id, $data['name'], $data['email'], $data['specialization']);
        } catch (CustomException $e) {
            return $this->utilityService->createErrorResponse('Nauczyciel nie został edytowany', $e->getMessage(), $e->getStatusCode());
        }

        $idUser = $editedTeacher->getUser() ? $editedTeacher->getUser()->getId() : null;

        $data = [];

        $linksConfig = [
            'self' => [
                'route' => 'api_teachers_id',
                'param' => 'id',
                'method' => 'GET'
            ],
            'loginData' => [
                'route' => 'api_users_id',
                'param' => 'id',
                'method' => 'GET',
                'value' => $idUser
            ],
        ];

        $teacherData = $editedTeacher->toArray();
        $teacherData['_links'] = $this->utilityService->generateHateoasLinks($editedTeacher, $linksConfig);
        $data[] = $teacherData;

        return $this->utilityService->createSuccessResponse('Pomyślnie edytowano nauczyciela.', ['techaer' => $data], Response::HTTP_OK);

    }

    /**
     * Aktualizacja nauczyciela.
     *
     * Wywołanie pozwala na aktualizację wybranych pól nauczyciela.
     * 
     */
    #[Route('/teachers/{id}', name: 'api_teachers_update', methods: ['PATCH'])]
    public function updateTeacherFields(int $id, Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json(['error' => 'Niepoprawny JSON'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $updatedTeacher = $this->teacherService->updateTeacherFields($id, $data);
            $idUser = $updatedTeacher->getUser() ? $updatedTeacher->getUser()->getId() : null;

            $linksConfig = [
                'self' => [
                    'route' => 'api_teachers_id',
                    'param' => 'id',
                    'method' => 'GET'
                ],
                'loginData' => [
                    'route' => 'api_users_id',
                    'param' => 'id',
                    'method' => 'GET',
                    'value' => $idUser
                ],
            ];

            $teacherData = $updatedTeacher->toArray();
            $teacherData['_links'] = $this->utilityService->generateHateoasLinks($updatedTeacher, $linksConfig);

            return $this->utilityService->createSuccessResponse('Pomyślnie zaktualizowano nauczyciela.', ['teacher' => $teacherData], Response::HTTP_OK);
        
        } catch (CustomException $e) {     
            return $this->utilityService->createErrorResponse('Nauczyciel nie został zaktualizowany', $e->getMessage(), $e->getStatusCode());
        } catch (Exception $e) {
            return new JsonResponse([
                'error' => 'Wystąpił błąd',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }   
    }

     /**
     * Usuwa nauczyciela
     *
     * Wywołanie pozwala na usunięcie nauczyciela o podanym identyfikatorze.
     * 
     */
    #[Route('/teachers/{id}', name: 'api_teachers_delete', methods: ['DELETE'])]
    public function deleteTeacher(int $id): Response
    {
        try {
            $this->teacherService->deleteTeacher($id);
            return $this->json(['status' => 'Usunięto nauczyciela.', 'code' => Response::HTTP_OK], Response::HTTP_OK);
        } catch (CustomException $e) {     
            return $this->utilityService->createErrorResponse('Nauczyciel nie został usunięty', $e->getMessage(), $e->getStatusCode());
        } catch (Exception $e) {
            return new JsonResponse([
                'error' => 'Wystąpił błąd',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }  
    }

    


}
