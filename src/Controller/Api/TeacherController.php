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


#[OA\Tag(name: "Nauczyciele")]
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
    #[OA\Response(response: 200, description: 'Zwraca listę nauczycieli')]
    #[OA\Response(response: 404, description: 'Not Found')]
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
                ],
                'allCourses' => []
            ];


            // Dodajemy kursy, które prowadzi nauczyciel
            $courses = $teacher->getCourses();
            foreach ($courses as $course) {
                $linksConfig['allCourses']['course_' . $course->getId()] = [
                    'route' => 'api_courses_id',
                    'param' => 'id',
                    'method' => 'GET',
                    'value' => $course->getId()
                ];
            }




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
   
    #[OA\Response(
        response: 404,
        description: 'Not Found'
    )]   
    #[Route('/teachers/{id}', name: 'api_teachers_id', methods: ['GET'])]
    public function getTeacherById(int $id): Response
    {
        $teacher = $this->teacherService->findTeacher($id);
       
         // Sprawdzenie, czy teacher został znaleziony
         if (!$teacher) {
            // Jeśli nie znaleziono nauczyciela, zwróć odpowiedź z błędem 404
            return $this->json(['error' => 'Nie znaleziono nauczyciela o id ' . $id], JsonResponse::HTTP_NOT_FOUND);
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
            'gradesToBeGiven' => []
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

    #[OA\Response(
        response: 400,
        description: 'Bad Request'
    )]
    
    #[Route('/teachers', name: 'api_teachers_add', methods: ['POST'])]
    public function addTeacher(Request $request): Response
    {
        try {
            // Pobranie i walidacja danych
            $data = $this->utilityService->validateAndDecodeJson($request, ['name', 'email', 'specialization' ,'username', 'password']);
        } catch (\Exception $e) {
            // Obsługa wyjątków
            return $this->json(['error' => 'Nie przekazano wymaganych danych'], JsonResponse::HTTP_BAD_REQUEST);
        }

       
        // Dodanie nowego nauczyciela
        $newTeacher = $this->teacherService->createTeacherWithPassword($data['name'], $data['email'], $data['specialization'] ,$data['username'], $data['password']);
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

        $jsonContent = $this->utilityService->serializeJson($data);
        return new Response($jsonContent, 200, ['Content-Type' => 'application/json']);

    }

    /**
     * Edytuje nauczyciela.
     *
     * Wywołanie pozwala na edycję danych nauczyciela.
     * 
     */

    #[OA\Response(
        response: 400,
        description: 'Bad Request'
    )]   
    #[Route('/teachers/{id}', name: 'api_teachers_edit', methods: ['PUT'])]
    public function editTeacher(int $id, Request $request): Response
    {

        try{
            //pobieranie i walidacja danych
            $data = $this->utilityService->validateAndDecodeJson($request, ['name', 'email', 'specialization']);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Nie przekazano wymaganych danych'], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Edycja nauczyciela
        $editedTeacher = $this->teacherService->editTeacher($id, $data['name'], $data['email'], $data['specialization']);
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

        $jsonContent = $this->utilityService->serializeJson($data);
        return new Response($jsonContent, 200, ['Content-Type' => 'application/json']);

    }

    /**
     * Aktualizacja nauczyciela.
     *
     * Wywołanie pozwala na aktualizację wybranych pól nauczyciela.
     * 
     */
    #[OA\Response(
        response: 400,
        description: 'Bad Request'
    )]

    #[Route('/teachers/{id}', name: 'api_teachers_update', methods: ['PATCH'])]
    public function updateTeacherFields(int $id, Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $updatedTeacher = $this->teacherService->updateTeacherFields($id, $data);
            $idUser = $updatedTeacher->getUser() ? $updatedTeacher->getUser()->getId() : null;

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

            $teacherData = $updatedTeacher->toArray();
            $teacherData['_links'] = $this->utilityService->generateHateoasLinks($updatedTeacher, $linksConfig);
            $status = ['status' => 'Zaktualizowano studenta.' ];
            $data = array_merge($status, $teacherData);

            $jsonContent = $this->utilityService->serializeJson($data);
            return new Response($jsonContent, 200, ['Content-Type' => 'application/json']);
        
        } catch (\Exception $e) {           
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }   
    }

     /**
     * Usuwa nauczyciela
     *
     * Wywołanie pozwala na usunięcie nauczyciela o podanym identyfikatorze.
     * 
     */

    #[OA\Response(
        response: 204,
        description: 'Usuwa nauczyciela'
    )]
    #[OA\Response(
        response: 404,
        description: 'Not Found'
    )]
    #[Route('/teachers/{id}', name: 'api_teachers_delete', methods: ['DELETE'])]
    public function deleteTeacher(int $id): Response
    {
        try {
            $this->teacherService->deleteTeacher($id);
            return $this->json(['status' => 'Usunięto nauczyciela.'], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    


}
