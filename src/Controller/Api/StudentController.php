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
class StudentController extends AbstractController
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
     * Wyświetla listę studentów.
     *
     * Wywołanie wyświetla wszystkich studentów wraz z ich linkiem do szczegółów.
     * 
     */
    #[Route('/students', name: 'api_students', methods: ['GET'])]
    #[OA\Tag(name: "Operacje na studentach")]
    #[OA\Response(
        response: 200,
        description: 'Zwraca listę studentów'       
        )
    ]
    #[OA\Response(
        response: 404,
        description: 'Not Found'
    )]
    public function getStudents()
    {
        $students = $this->entityService->findAllStudents();

        $data = [];

        $linksConfig = [
            'self' => [
                'route' => 'api_students_id',
                'param' => 'id',
                'method' => 'GET'
            ],
            'login' => [
                'route' => 'api_students_login',
                'param' => 'id',
                'method' => 'GET'
            ]
        ];
       
        foreach ($students as $student) {
            $studentData = $student->toArray();
            $studentData['_links'] = $this->entityService->generateHateoasLinks($student, $linksConfig, $this->urlGenerator);
            $data[] = $studentData;
            }    

        $jsonContent = $this->serializer->serialize($data, 'json', \JMS\Serializer\SerializationContext::create()->setSerializeNull(true));
        return new Response($jsonContent, 200, ['Content-Type' => 'application/json']);
        return $this->json($data);
    }

    /**
     * Wyświetla login studenta.
     *
     * Wywołanie wyświetla login studenta o podanym identyfikatorze.
     * 
     */ 
    #[Route('/students/{id}/login', name: 'api_students_login', methods: ['GET'])]
    #[OA\Tag(name: "Operacje na studentach")]
    #[OA\Response(
        response: 200,
        description: 'Zwraca login studenta o podanym identyfikatorze',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Student::class))
        ))
    ]
    #[OA\Response(
        response: 404,
        description: 'Not Found'
    )]
    public function getStudentLogin(int $id) : Response
    {
        $student = $this->entityService->findStudent($id);
        
        if (!$student) {
            return new Response(null, 404);
        }
        
        $login = $student->getLogin();
        $data = [
            'id' => $login->getId(),
            'username' => $login->getUsername(),
            'roles' => $login->getRoles(),
            '_links' => [
                'self' => [
                    'href' => $this->urlGenerator->generate('api_students_login', ['id' => $student->getId()], UrlGeneratorInterface::ABSOLUTE_URL)
                ]
            ]
        ];

        $jsonContent = $this->serializer->serialize($data, 'json', \JMS\Serializer\SerializationContext::create()->setSerializeNull(true));
        return new Response($jsonContent, 200, ['Content-Type' => 'application/json']);
    }

    /**
     * Wyświetla szczegóły studenta.
     *
     * Wywołanie wyświetla szczegóły studenta o podanym identyfikatorze.
     * 
     */ 
    #[Route('/students/{id}', name: 'api_students_id', methods: ['GET'])]
    #[OA\Tag(name: "Operacje na studentach")]
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
    public function getStudent(int $id): Response
    {
        $student = $this->entityService->findStudent($id);
       
         // Sprawdzenie, czy student został znaleziony
         if (!$student) {
            // Jeśli nie znaleziono studenta, zwróć odpowiedź z błędem 404
            return $this->json(['error' => 'Nie znaleziono studenta o id ' . $id], JsonResponse::HTTP_NOT_FOUND);
        }

        $data = $student->toArray();
        $data['student'] = $student ? $student->toArray() : null;
        $data['student']['_links'] = ['self' => ['href' => $this->urlGenerator->generate('api_students_id', ['id' => $student->getId()], UrlGeneratorInterface::ABSOLUTE_URL)],
                    'allCourses' => ['href' => $this->urlGenerator->generate('api_students_courses', ['id' => $student->getId()], UrlGeneratorInterface::ABSOLUTE_URL)],
                ];
        
        $jsonContent = $this->serializer->serialize($data, 'json', \JMS\Serializer\SerializationContext::create()->setSerializeNull(true));
        return new Response($jsonContent, 200, ['Content-Type' => 'application/json']);
        return $this->json($data);
    }

    /**
     * Dodaje nowego studenta.
     *
     * Wywołanie dodaje nowego studenta na podstawie przekazanych danych.
     * 
     */ 
    #[Route('/students', name: 'api_students_create', methods: ['POST'])]
    #[OA\Tag(name: "Operacje na studentach")]
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
    public function createStudent(Request $request): Response
    {
        // Pobranie danych z żądania
        $data = json_decode($request->getContent(), true);
        
        // Sprawdzenie, czy przekazano wymagane dane
        if (!isset($data['name']) || !isset($data['email']) || !isset($data['username']) || !isset($data['password'])){        
            // Jeśli nie przekazano wymaganych danych, zwróć odpowiedź z błędem 400
            return $this->json(['error' => 'Nie przekazano wymaganych danych'], JsonResponse::HTTP_BAD_REQUEST);
        }

        // utworzenie studenta
        $newStudent = $this->entityService->addStudent($data['name'], $data['email'], $data['username'], $data['password']);  
        
        $data = [];

        $linksConfig = [
            'self' => [
                'route' => 'api_students_id',
                'param' => 'id',
                'method' => 'GET'
            ],
            'login' => [
                'route' => 'api_students_login',
                'param' => 'id',
                'method' => 'GET'
            ]
        ];

        $studentData = $newStudent->toArray();
        $studentData['_links'] = $this->entityService->generateHateoasLinks($newStudent, $linksConfig, $this->urlGenerator);
        $data[] = $studentData;

        $jsonContent = $this->serializer->serialize($data, 'json', \JMS\Serializer\SerializationContext::create()->setSerializeNull(true));
        return new Response($jsonContent, 201, ['Content-Type' => 'application/json']);
        return $this->json($data, JsonResponse::HTTP_CREATED);
    }







    // /students/3001/courses
    #[Route('/students/{id}/courses', name: 'api_students_courses', methods: ['GET'])]
    #[OA\Tag(name: "Operacje na studentach")]
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
