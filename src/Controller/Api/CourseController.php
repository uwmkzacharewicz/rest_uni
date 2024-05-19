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

use App\Exception\CustomException;
use Exception;

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
        $active = $request->query->get('active');

        try {
            if ($teacherId !== null) {
                $courses = $this->courseService->findCoursesByTeacherId((int)$teacherId);
            } elseif ($active !== null) {
                $isActive = filter_var($active, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                if ($isActive === null) {
                    throw new \InvalidArgumentException('Invalid value for "active" parameter');
                }
                $courses = $this->courseService->findCoursesByActive($isActive);
            } else {
                $courses = $this->courseService->findAllCourses();
            }
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }

        if (!$courses) {
            return $this->json(['error' => 'Nie znaleziono żadnego kursu spełniającego podane kryteria'], JsonResponse::HTTP_NOT_FOUND);
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
            return $this->json(['error' => 'Nie znaleziono kursu o id ' . $id, 'code' => Response::HTTP_NOT_FOUND], Response::HTTP_NOT_FOUND);
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
            ],
            'allStudents' => [],
            'edit' => [
                'route' => 'api_courses_id',
                'param' => 'id',
                'method' => 'PUT'
            ],
            'update' => [
                'route' => 'api_courses_id',
                'param' => 'id',
                'method' => 'PATCH'
            ],
            'delete' => [
                'route' => 'api_courses_id',
                'param' => 'id',
                'method' => 'DELETE'
            ],
            'create' => [
                'route' => 'api_courses_add',
                'method' => 'POST'
            ]
        ];

         // Dodajemy studentów zapisanych na kurs do sekcji allStudents
         $students = $this->courseService->findStudentsByCourse($id);
         foreach ($students as $student) {
             $studentId = $student->getId();
             $linksConfig['allStudents']['student_' . $studentId] = [
                 'route' => 'api_students_id',
                 'param' => 'id',
                 'method' => 'GET',
                 'value' => $studentId
             ];
         }
 

        $courseData = $course->toArray();
        $courseData['availableSpots'] = $course->getCapacity() - count($students); // Obliczanie wolnych miejsc
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
            return $this->utilityService->createErrorResponse('Kurs nie został dodany', 'Nie przekazano wymaganych danych', Response::HTTP_BAD_REQUEST);
        }

        try{
            $newCourse = $this->courseService->createCourse($data['title'], $data['description'], $data['teacherId'] ,$data['capacity'], $data['active']);
        }  catch (CustomException $e) {
            return $this->utilityService->createErrorResponse('Kurs nie został dodany', $e->getMessage(), $e->getStatusCode());
        }

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

        return $this->utilityService->createSuccessResponse('Dodano nowy kurs.', ['course' => $data], Response::HTTP_CREATED);
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
            // Obsługa wyjątków
             return $this->utilityService->createErrorResponse('Kurs nie został edytowany', 'Nie przekazano wymaganych danych', Response::HTTP_BAD_REQUEST);         
        }

        // Edycja kursu
        try {
            $editedCourse = $this->courseService->editCourse($id, $data['title'], $data['description'], $data['teacherId'] ,$data['capacity'], $data['active']);
        } catch (CustomException $e) {
            return $this->utilityService->createErrorResponse('Kurs nie został edytowany', $e->getMessage(), $e->getStatusCode());
        }

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
        $data[] = $courseData;

        return $this->utilityService->createSuccessResponse('Pomyślnie edytowano kurs.', ['course' => $data], Response::HTTP_OK);
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
    
            return $this->utilityService->createSuccessResponse('Pomyślnie zaktualizowano kurs.', ['course' => $courseData], Response::HTTP_OK);     


        } catch (CustomException $e) {     
            return $this->utilityService->createErrorResponse('Kurs nie został zaktualizowany', $e->getMessage(), $e->getStatusCode());
        } catch (Exception $e) {
            return new JsonResponse([
                'error' => 'Wystąpił błąd',
                'message' => $e->getMessage()
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
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
            return $this->json(['status' => 'Kurs został usunięty.', 'code' => Response::HTTP_OK], Response::HTTP_OK);
        } catch (CustomException $e) {     
            return $this->utilityService->createErrorResponse('Kurs nie został usunięty', $e->getMessage(), $e->getStatusCode());
        } catch (Exception $e) {
            return new JsonResponse([
                'error' => 'Wystąpił błąd',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }  
    }

}
