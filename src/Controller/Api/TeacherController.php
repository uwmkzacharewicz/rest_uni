<?php

namespace App\Controller\Api;

use App\Entity\Teacher;
use App\Entity\Student;
use App\Entity\Course;
use App\Entity\Enrollment;
use App\Security\Role;
use App\Service\UtilityService;
use App\Service\TeacherService;

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
        $teachers = $this->teacherService->findAllStudents();
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
                'allCourses' => [
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
    #[OA\Response(
        response: 200,
        description: 'Zwraca nauczyciela o podanym identyfikatorze',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Teacher::class))
        ))
    ]
    #[OA\Response(
        response: 404,
        description: 'Not Found'
    )]   
    #[Route('/teachers/{id}', name: 'api_teachers_id', methods: ['GET'])]
    public function getStudentById(int $id): Response
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
            'allCourses' => [
                'route' => 'api_users_id',
                'param' => 'id',
                'method' => 'GET',
                'value' => $idUser
        ] ];

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
        response: 201,
        description: 'Dodaje nauczyciela',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Teacher::class))
        ))
    ]
    #[OA\Response(
        response: 400,
        description: 'Bad Request'
    )]
    #[OA\RequestBody(
        description: 'Dane nowego nauczyciela',
        required: true,
        content: new OA\JsonContent(ref: "#/components/schemas/NewTeacher")
    )] 
    #[Route('/teachers', name: 'api_teachers_add', methods: ['POST'])]
    public function addStudent(Request $request): Response
    {
        try {
            // Pobranie i walidacja danych
            $data = $this->utilityService->validateAndDecodeJson($request, ['name', 'email', 'specialization' ,'username', 'password']);
        } catch (\Exception $e) {
            // Obsługa wyjątków
            return $this->json(['error' => 'Nie przekazano wymaganych danych'], JsonResponse::HTTP_BAD_REQUEST);
        }

       
        // Dodanie nowego nauczyciela
        $newTeacher = $this->teacherService->createStudentWithPassword($data['name'], $data['email'], $data['username'], $data['password']);
        $idUser = $updatedStudent->getUser() ? $updatedStudent->getUser()->getId() : null;

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

        $teacherData = $newStudent->toArray();
        $teacherData['_links'] = $this->utilityService->generateHateoasLinks($newStudent, $linksConfig);
        $data[] = $teacherData;

        $jsonContent = $this->utilityService->serializeJson($data);
        return new Response($jsonContent, 200, ['Content-Type' => 'application/json']);

    }

    /**
     * Edytuje lub tworzy nauczyciela.
     *
     * Wywołanie pozwala na edycję nauczyciela o podanym identyfikatorze.
     * 
     */
    #[OA\Response(
        response: 200,
        description: 'Edytuje nauczyciela o podanym identyfikatorze',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Teacher::class))
        ))
    ]
    #[OA\Response(
        response: 400,
        description: 'Bad Request'
    )]   
    #[OA\RequestBody(
        description: 'Dane nauczyciela do aktualizacji',
        required: true,
        content: new OA\JsonContent(ref: "#/components/schemas/EditStudent")
    )] 
    #[Route('/teachers/{id}', name: 'api_students_update', methods: ['PUT'])]
    public function editStudent(int $id, Request $request): Response
    {
        try {
            // Pobranie i walidacja danych
            $data = $this->utilityService->validateAndDecodeJson($request, ['name', 'email']);
        } catch (\Exception $e) {
            // Obsługa wyjątków
            return $this->json(['error' => 'Nie przekazano wymaganych danych'], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Edycja nauczyciela
        $editedStudent = $this->teacherService->editStudent($id, $data['name'], $data['email']);
        $idUser = $updatedStudent->getUser() ? $updatedStudent->getUser()->getId() : null;

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

        $teacherData = $editedStudent->toArray();
        $teacherData['_links'] = $this->utilityService->generateHateoasLinks($editedStudent, $linksConfig);
        $data[] = $teacherData;

        $jsonContent = $this->utilityService->serializeJson($data);
        return new Response($jsonContent, 200, ['Content-Type' => 'application/json']);
    }


    /**
     * Aktualizacja nauczyciela. PATCH
     *
     * Wywołanie aktualizuje użytkownika o podanym id lub tworzy nowego użytkownika.
     * 
     */
    #[OA\Response(
        response: 200,
        description: 'Aktualizuje nauczyciela o podanym identyfikatorze',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Teacher::class))
        ))
    ]
    #[OA\Response(
        response: 400,
        description: 'Bad Request'
    )]
    #[OA\RequestBody(
        description: 'Dane nauczyciela do aktualizacji',
        required: true,
        content: new OA\JsonContent(ref: "#/components/schemas/EditStudent")
    )]
    #[Route('/teachers/{id}', name: 'api_students_patch', methods: ['PATCH'])]
    public function updateStudent(int $id, Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $updatedStudent = $this->teacherService->updateStudentFields($id, $data);
            $idUser = $updatedStudent->getUser() ? $updatedStudent->getUser()->getId() : null;

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

            $teacherData['student_info'] = $updatedStudent->toArray();
            $teacherData['_links'] = $this->utilityService->generateHateoasLinks($updatedStudent, $linksConfig);
            $status = ['status' => 'Zaktualizowano nauczyciela.' ];
            $data = array_merge($status, $teacherData);
            
            $jsonContent = $this->utilityService->serializeJson($data);
            return new Response($jsonContent, 200, ['Content-Type' => 'application/json']);
        
        } catch (\Exception $e) {           
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }   
    }


    /**
     * Usuwa nauczyciela.
     *
     * Wywołanie pozwala na usunięcie nauczyciela o podanym identyfikatorze.
     * 
     */
    #[OA\Response(
        response: 204,
        description: 'Usuwa nauczyciela o podanym identyfikatorze'
    )]
    #[OA\Response(
        response: 404,
        description: 'Not Found'
    )]
    #[Route('/teachers/{id}', name: 'api_students_delete', methods: ['DELETE'])]
    public function deleteStudent(int $id): Response
    {        
        try {
            $this->teacherService->deleteStudent($id);
            return $this->json(['status' => 'Usunięto nauczyciela.'], Response::HTTP_OK);
        } catch (\Exception $e) {           
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Wyświetla kursy nauczyciela.
     *
     * Wywołanie wyświetla kursy nauczyciela o podanym identyfikatorze.
     * 
     */
        // /teachers/3001/courses
    #[Route('/teachers/{id}/courses', name: 'api_students_courses', methods: ['GET'])]
    #[OA\Tag(name: "Operacje na kursach")]
    #[OA\Response(
        response: 200,
        description: 'Zwraca listę kursów nauczyciela o podanym identyfikatorze',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Course::class))
        ))
    ]
    #[OA\Response(
        response: 404,
        description: 'Not Found'
    )]
    public function getStudentCourses(int $id): Response
    {
        $teacher = $this->entityManager->getRepository(Teacher::class)->find($id);
        
        // Sprawdzenie, czy teacher został znaleziony
        if (!$teacher) {
            // Jeśli nie znaleziono nauczyciela, zwróć odpowiedź z błędem 404
            return $this->json(['error' => 'Nie znaleziono nauczyciela o id ' . $id], JsonResponse::HTTP_NOT_FOUND);
        }
        
        //$courses = $teacher->getCourses();
        $courses = $teacher->getEnrollments()->GetCourse()->find($id);

        
        $data = [];

        foreach ($courses as $course) {
            $teacher = $course->getTeacher();
            $data[] = [
                'id' => $course->getId(),
                'title' => $course->getTitle(),
                'description' => $course->getDescription(),
                'teacher' => [
                    'id' => $teacher ? $teacher->getId() : null,
                    'name' => $teacher ? $teacher->getName() : null,
                    '_links' => ['self' => [
                                'href' => $this->urlGenerator->generate('api_courses_id', ['id' => $teacher->getId()], UrlGeneratorInterface::ABSOLUTE_URL)
                            ]
                        ]       
                ],
                'capacity' => $course->getCapacity(),
                'active' => $course->isActive()             
            ];
        }
        
        $jsonContent = $this->serializer->serialize($data, 'json', \JMS\Serializer\SerializationContext::create()->setSerializeNull(true));
        return new Response($jsonContent, 200, ['Content-Type' => 'application/json']);
        return $this->json($data);
    }

}
