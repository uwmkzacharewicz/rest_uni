<?php

namespace App\Controller\Api;

use App\Entity\Student;
use App\Service\UtilityService;
use App\Service\StudentService;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use JMS\Serializer\SerializerInterface;
use OpenApi\Attributes as OA;

use Nelmio\ApiDocBundle\Annotation\Model;


#[OA\Tag(name: "Studenci")]
#[Security(name: 'Bearer')]
#[Route("/api", "")]
class StudentController extends AbstractController
{
    private $serializer;
    private $studentService;
    private $utilityService;

    public function __construct(StudentService $studentService, SerializerInterface $serializer, UtilityService $utilityService) {

            $this->studentService = $studentService;
            $this->serializer = $serializer;
            $this->utilityService = $utilityService;
    }

    /**
     * Wyświetla listę studentów.
     *
     * Wywołanie wyświetla wszystkich studentów wraz z ich linkiem do szczegółów.
     * 
     */
    #[OA\Response(response: 200, description: 'Zwraca listę studentów')]
    #[OA\Response(response: 404, description: 'Not Found')]
    #[Route('/students', name: 'api_students', methods: ['GET'])]
    public function getStudents() : Response
    {
        $students = $this->studentService->findAllStudents();
        $data = [];    

        foreach ($students as $student) {
            $idUser = $student->getUser() ? $student->getUser()->getId() : null;

            $linksConfig = [
                'self' => [
                    'route' => 'api_students_id',
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
            $studentData = $student->toArray();
            $studentData['_links'] = $this->utilityService->generateHateoasLinks($student, $linksConfig);

            $data[] = $studentData;
        }    

        $jsonContent = $this->utilityService->serializeJson($data);
        return new Response($jsonContent, 200, ['Content-Type' => 'application/json']);
    }

     /**
     * Wyświetla szczegóły studenta.
     *
     * Wywołanie wyświetla szczegóły studenta o podanym identyfikatorze.
     * 
     */     
    #[OA\Response(
        response: 200,
        description: 'Zwraca studenta o podanym identyfikatorze',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Student::class))
        ))
    ]
    #[OA\Response(
        response: 404,
        description: 'Not Found'
    )]   
    #[Route('/students/{id}', name: 'api_students_id', methods: ['GET'])]
    public function getStudentById(int $id): Response
    {
        $student = $this->studentService->findStudent($id);
       
         // Sprawdzenie, czy student został znaleziony
         if (!$student) {
            // Jeśli nie znaleziono studenta, zwróć odpowiedź z błędem 404
            return $this->json(['error' => 'Nie znaleziono studenta o id ' . $id], JsonResponse::HTTP_NOT_FOUND);
        }

        $data = [];
        $idUser = $student->getUser() ? $student->getUser()->getId() : null;

        $linksConfig = [
            'self' => [
                'route' => 'api_students_id',
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

        $data = $student->toArray();
        //$data['user'] = $student->getUser() ? $student->getUser()->toArray() : null;
        $data['_links'] = $this->utilityService->generateHateoasLinks($student, $linksConfig);
        
        $jsonContent = $this->utilityService->serializeJson($data);
        return new Response($jsonContent, 200, ['Content-Type' => 'application/json']);
    }

    
    /**
     * Dodaje nowego studenta.
     *
     * Wywołanie dodaje nowego studenta na podstawie przekazanych danych.
     * 
     */ 
    #[OA\Response(
        response: 201,
        description: 'Dodaje studenta',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Student::class))
        ))
    ]
    #[OA\Response(
        response: 400,
        description: 'Bad Request'
    )]
    #[OA\RequestBody(
        description: 'Dane nowego użytkownika',
        required: true,
        content: new OA\JsonContent(ref: "#/components/schemas/NewStudent")
    )] 
    #[Route('/students', name: 'api_students_add', methods: ['POST'])]
    public function addStudent(Request $request): Response
    {
        try {
            // Pobranie i walidacja danych
            $data = $this->utilityService->validateAndDecodeJson($request, ['name', 'email', 'username', 'password']);
        } catch (\Exception $e) {
            // Obsługa wyjątków
            return $this->json(['error' => 'Nie przekazano wymaganych danych'], JsonResponse::HTTP_BAD_REQUEST);
        }

       
        // Dodanie nowego studenta
        $newStudent = $this->studentService->createStudentWithPassword($data['name'], $data['email'], $data['username'], $data['password']);
        $idUser = $newStudent->getUser() ? $newStudent->getUser()->getId() : null;

        $data = [];

        $linksConfig = [
            'self' => [
                'route' => 'api_students_id',
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

        $studentData = $newStudent->toArray();
        $studentData['_links'] = $this->utilityService->generateHateoasLinks($newStudent, $linksConfig);
        $data[] = $studentData;

        $jsonContent = $this->utilityService->serializeJson($data);
        return new Response($jsonContent, 200, ['Content-Type' => 'application/json']);

    }

    /**
     * Edytuje lub tworzy studenta.
     *
     * Wywołanie pozwala na edycję studenta o podanym identyfikatorze.
     * 
     */
    #[OA\Response(
        response: 200,
        description: 'Edytuje studenta o podanym identyfikatorze',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Student::class))
        ))
    ]
    #[OA\Response(
        response: 400,
        description: 'Bad Request'
    )]   
    #[OA\RequestBody(
        description: 'Dane studenta do aktualizacji',
        required: true,
        content: new OA\JsonContent(ref: "#/components/schemas/EditStudent")
    )] 
    #[Route('/students/{id}', name: 'api_students_update', methods: ['PUT'])]
    public function editStudent(int $id, Request $request): Response
    {
        try {
            // Pobranie i walidacja danych
            $data = $this->utilityService->validateAndDecodeJson($request, ['name', 'email']);
        } catch (\Exception $e) {
            // Obsługa wyjątków
            return $this->json(['error' => 'Nie przekazano wymaganych danych'], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Edycja studenta
        $editedStudent = $this->studentService->editStudent($id, $data['name'], $data['email']);
        $idUser = $editedStudent->getUser() ? $editedStudent->getUser()->getId() : null;

        $data = [];

        $linksConfig = [
            'self' => [
                'route' => 'api_students_id',
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

        $studentData = $editedStudent->toArray();
        $studentData['_links'] = $this->utilityService->generateHateoasLinks($editedStudent, $linksConfig);
        $data[] = $studentData;

        $jsonContent = $this->utilityService->serializeJson($data);
        return new Response($jsonContent, 200, ['Content-Type' => 'application/json']);
    }


    /**
     * Aktualizacja studenta. PATCH
     *
     * Wywołanie aktualizuje użytkownika o podanym id lub tworzy nowego użytkownika.
     * 
     */
    #[OA\Response(
        response: 200,
        description: 'Aktualizuje studenta o podanym identyfikatorze',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Student::class))
        ))
    ]
    #[OA\Response(
        response: 400,
        description: 'Bad Request'
    )]
    #[OA\RequestBody(
        description: 'Dane studenta do aktualizacji',
        required: true,
        content: new OA\JsonContent(ref: "#/components/schemas/EditStudent")
    )]
    #[Route('/students/{id}', name: 'api_students_patch', methods: ['PATCH'])]
    public function updateStudent(int $id, Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $updatedStudent = $this->studentService->updateStudentFields($id, $data);
            $idUser = $updatedStudent->getUser() ? $updatedStudent->getUser()->getId() : null;

            $linksConfig = [
                'self' => [
                    'route' => 'api_students_id',
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

            $studentData['student_info'] = $updatedStudent->toArray();
            $studentData['_links'] = $this->utilityService->generateHateoasLinks($updatedStudent, $linksConfig);
            $status = ['status' => 'Zaktualizowano studenta.' ];
            $data = array_merge($status, $studentData);
            
            $jsonContent = $this->utilityService->serializeJson($data);
            return new Response($jsonContent, 200, ['Content-Type' => 'application/json']);
        
        } catch (\Exception $e) {           
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }   
    }


    /**
     * Usuwa studenta.
     *
     * Wywołanie pozwala na usunięcie studenta o podanym identyfikatorze.
     * 
     */
    #[OA\Response(
        response: 204,
        description: 'Usuwa studenta o podanym identyfikatorze'
    )]
    #[OA\Response(
        response: 404,
        description: 'Not Found'
    )]
    #[Route('/students/{id}', name: 'api_students_delete', methods: ['DELETE'])]
    public function deleteStudent(int $id): Response
    {        
        try {
            $this->studentService->deleteStudent($id);
            return $this->json(['status' => 'Usunięto studenta.'], Response::HTTP_OK);
        } catch (\Exception $e) {           
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    /**
     * Wyświetla kursy studenta.
     *
     * Wywołanie wyświetla kursy studenta o podanym identyfikatorze.
     * 
     */
        // /students/3001/courses
    #[Route('/students/{id}/courses', name: 'api_students_courses', methods: ['GET'])]
    #[OA\Tag(name: "Operacje na kursach")]
    #[OA\Response(
        response: 200,
        description: 'Zwraca listę kursów studenta o podanym identyfikatorze',
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
        $student = $this->entityManager->getRepository(Student::class)->find($id);
        
        // Sprawdzenie, czy student został znaleziony
        if (!$student) {
            // Jeśli nie znaleziono studenta, zwróć odpowiedź z błędem 404
            return $this->json(['error' => 'Nie znaleziono studenta o id ' . $id], JsonResponse::HTTP_NOT_FOUND);
        }
        
        //$courses = $student->getCourses();
        $courses = $student->getEnrollments()->GetCourse()->find($id);

        
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
