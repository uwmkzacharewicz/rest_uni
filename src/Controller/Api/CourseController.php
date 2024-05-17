<?php

namespace App\Controller\Api;

use App\Entity\Course;
use App\Service\CourseService;
use App\Service\UtilityService;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use JMS\Serializer\SerializerInterface;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Annotation\Model;

#[OA\Tag(name: "Kursy")]
#[Route("/api", "")]
class CourseController extends AbstractController
{
    private $serializer;
    private $courseService;
    private $utilityService;

    public function __construct(CourseService $courseService, SerializerInterface $serializer, UtilityService $utilityService) {

            $this->courseService = $courseService;
            $this->serializer = $serializer;
            $this->utilityService = $utilityService;
    }

    /**
     * Wyświetla listę kursów.
     *
     * Wywołanie wyświetla wszystkie kursy
     * 
     */
    #[OA\Response(response: 200, description: 'Zwraca listę kursów')]
    #[OA\Response(response: 404, description: 'Not Found')]
    #[Route('/courses', name: 'api_courses', methods: ['GET'])]
    public function getCourses(Request $request): Response
    {
        $teacherId = $request->query->get('teacherId');

        if ($teacherId !== null) {
            try {
                $courses = $this->courseService->findCoursesByTeacherId((int)$teacherId);
            } catch (\Exception $e) {
                return $this->json(['error' => $e->getMessage()], JsonResponse::HTTP_NOT_FOUND);
            }
        } else {
            $courses = $this->courseService->findAllCourses();
        }

        // Sprawdzenie, czy kursy zostały znalezione
        if (!$courses) {
            return $this->json(['error' => 'Nie znaleziono żadnego kursu'], JsonResponse::HTTP_NOT_FOUND);
        }

        $data = [];

        foreach ($courses as $course ) {
            $idTeacher = $course->getTeacher()->getId();
            $linksConfig = [
                'self' => [
                    'route' => 'api_courses_id',
                    'param' => 'id',
                    'method' => 'GET'
                ],
                'teacherData' => [
                    'route' => 'api_teachers_id',
                    'param' => 'id',
                    'method' => 'GET',
                    'value' => $idTeacher
                ]
            ];

            $courseData = $course->toArray();
            $courseData['_links'] = $this->utilityService->generateHateoasLinks($course, $linksConfig);

            $data[] = $courseData;

        }

        $jsonContent = $this->utilityService->serializeJson($data);
        return new Response($jsonContent, 200, ['Content-Type' => 'application/json']);        
    }

    /**
     * Wyświetla kurs o podanym id.
     *
     * Wywołanie wyświetla kurs o podanym id
     * 
     */
    #[OA\Response(response: 200, description: 'Zwraca kurs o podanym id')]
    #[OA\Response(response: 404, description: 'Not Found')]
    #[Route('/courses/{id}', name: 'api_courses_id', methods: ['GET'])]
    public function getCourseById(int $id): Response
    {
        $course = $this->courseService->findCourse($id);
         // Sprawdzenie, czy teacher został znaleziony
         if (!$course) {
            // Jeśli nie znaleziono nauczyciela, zwróć odpowiedź z błędem 404
            return $this->json(['error' => 'Nie znaleziono kursu o id ' . $id], JsonResponse::HTTP_NOT_FOUND);
        }

        $data = [];
        $idTeacher = $course->getTeacher()->getId();
        
        $linksConfig = [
            'self' => [
                'route' => 'api_courses_id',
                'param' => 'id',
                'method' => 'GET'
            ],
            'teacherData' => [
                'route' => 'api_teachers_id',
                'param' => 'id',
                'method' => 'GET',
                'value' => $idTeacher
            ]
        ];

        $courseData = $course->toArray();
        $courseData['_links'] = $this->utilityService->generateHateoasLinks($course, $linksConfig);

        $jsonContent = $this->utilityService->serializeJson($courseData);
        return new Response($jsonContent, 200, ['Content-Type' => 'application/json']);        
    }

    /**
     * Dodaje nowy kurs.
     *
     * Wywołanie dodaje nowy kurs
     * 
     */
    #[OA\Response(response: 201, description: 'Dodaje nowy kurs')]
    #[OA\Response(response: 400, description: 'Bad Request')]
    #[Route('/courses', name: 'api_courses_add', methods: ['POST'])]
    public function addCourse(Request $request): Response
    {
        try {
            //Pobieranie i walidacja danych
            $data = $this->utilityService->validateAndDecodeJson($request, 
                                                                        ['title', 
                                                                        'description', 
                                                                        'teacherId',
                                                                        'capacity', 
                                                                        'active']);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Nie przekazano wymaganych danych'], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Dodanie nwoego kursu
        $newCourse = $this->courseService->createCourse($data['title'], $data['description'], $data['teacherId'] ,$data['capacity'], $data['active']);
        $idTeacher = $newCourse->getTeacher()->getId();

        $linksConfig = [
            'self' => [
                'route' => 'api_courses_id',
                'param' => 'id',
                'method' => 'GET'
            ],
            'teacherData' => [
                'route' => 'api_teachers_id',
                'param' => 'id',
                'method' => 'GET',
                'value' => $idTeacher
            ]
        ];

        $courseData = $newCourse->toArray();
        $courseData['_links'] = $this->utilityService->generateHateoasLinks($newCourse, $linksConfig);

        $jsonContent = $this->utilityService->serializeJson($courseData);
        return new Response($jsonContent, 200, ['Content-Type' => 'application/json']);  
    }

    /**
     * Edytuje kurs o podanym id.
     *
     * Wywołanie edycji kursu o podanym id
     * 
     */
    #[OA\Response(response: 200, description: 'Aktualizuje kurs o podanym id')]
    #[OA\Response(response: 400, description: 'Bad Request')]
    #[OA\Response(response: 404, description: 'Not Found')]
    #[Route('/courses/{id}', name: 'api_courses_edit', methods: ['PUT'])]
    public function editCourse(int $id, Request $request): Response
    {
        try{
            //pobieranie i walidacja danych
            $data = $this->utilityService->validateAndDecodeJson($request, ['title', 'description', 'teacherId', 'capacity', 'active']);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Nie przekazano wymaganych danych'], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Edycja kursu
        $editedCourse = $this->courseService->editCourse($id, $data['title'], $data['description'], $data['teacherId'] ,$data['capacity'], $data['active']);
        $idTeacher = $editedCourse->getTeacher()->getId();

        $data = [];

        $linksConfig = [
            'self' => [
                'route' => 'api_courses_id',
                'param' => 'id',
                'method' => 'GET'
            ],
            'teacherData' => [
                'route' => 'api_teachers_id',
                'param' => 'id',
                'method' => 'GET',
                'value' => $idTeacher
            ]
        ];

        $courseData = $editedCourse->toArray();
        $courseData['_links'] = $this->utilityService->generateHateoasLinks($editedCourse, $linksConfig);

        $jsonContent = $this->utilityService->serializeJson($courseData);
        return new Response($jsonContent, 200, ['Content-Type' => 'application/json']);  
    }

    /** Aktualizacja kursu o podanym id 
     * 
     * Wywołanie aktualizuje kurs o podanym id
     * 
    */
    #[OA\Response(response: 200, description: 'Aktualizuje kurs o podanym id')]
    #[OA\Response(response: 400, description: 'Bad Request')]
    #[OA\Response(response: 404, description: 'Not Found')]
    #[Route('/courses/{id}', name: 'api_courses_update', methods: ['PATCH'])]
    public function patchCourse(int $id, Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }


        try {
            $updatedCourse = $this->courseService->updateCourseFields($id, $data);
            $idTeacher = $updatedCourse->getTeacher()->getId();

            $data = [];

            $linksConfig = [
                'self' => [
                    'route' => 'api_courses_id',
                    'param' => 'id',
                    'method' => 'GET'
                ],
                'teacherData' => [
                    'route' => 'api_teachers_id',
                    'param' => 'id',
                    'method' => 'GET',
                    'value' => $idTeacher
                ]
            ];
    
            $courseData = $updatedCourse->toArray();
            $courseData['_links'] = $this->utilityService->generateHateoasLinks($updatedCourse, $linksConfig);
    
            $jsonContent = $this->utilityService->serializeJson($courseData);
            return new Response($jsonContent, 200, ['Content-Type' => 'application/json']);  




        } catch (\Exception $e) {           
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

    }

    /**
     * Usuwa kurs o podanym id.
     *
     * Wywołanie usuwa kurs o podanym id
     * 
     */
    #[OA\Response(response: 200, description: 'Usuwa kurs o podanym id')]
    #[OA\Response(response: 404, description: 'Not Found')]
    #[Route('/courses/{id}', name: 'api_courses_delete', methods: ['DELETE'])]
    public function deleteCourse(int $id): Response
    {
        try {
            $this->courseService->deleteCourse($id);
            return $this->json(['message' => 'Kurs został usunięty'], JsonResponse::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], JsonResponse::HTTP_NOT_FOUND);
        }
    }

}
