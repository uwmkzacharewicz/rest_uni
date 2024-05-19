<?php

namespace App\Controller\Api;

use App\Entity\Enrollment;
use App\Service\CourseService;
use App\Service\EnrollmentService;
use App\Service\UtilityService;
use App\Controller\Api\CourseController;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use OpenApi\Attributes as OA;
use Symfony\Component\Security\Core\Security;

use App\Exception\CustomException;
use Exception;

#[OA\Tag(name: "Akcje dla nauczyciela")]
#[Route("/api/teachers", "")]
class TeacherActionController extends AbstractController
{
    private $courseService;
    private $enrollmentService;
    private $utilityService;
    private $security;

    public function __construct(CourseService $courseService, EnrollmentService $enrollmentService, UtilityService $utilityService, Security $security) {
        $this->courseService = $courseService;
        $this->enrollmentService = $enrollmentService;
        $this->utilityService = $utilityService;
        $this->security = $security;
    }

    #[OA\Response(response: 201, description: 'Utworzono nowy kurs')]
    #[OA\Response(response: 400, description: 'Bad Request')]
    #[Route('/courses', name: 'api_teachers_courses_add', methods: ['POST'])]
    public function addCourseWithTeacher(Request $request): Response
    {
        try {
            // Pobieranie i walidacja danych
            $data = $this->utilityService->validateAndDecodeJson($request, 
                                                                        ['title', 
                                                                        'description',
                                                                        'capacity', 
                                                                        'active']);
        } catch (\Exception $e) {
            return $this->utilityService->createErrorResponse('Kurs nie został dodany', 'Nie przekazano wymaganych danych', Response::HTTP_BAD_REQUEST);
        }

        // // Pobranie identyfikatora nauczyciela z tokena
        // $user = $this->security->getUser();
        // if (!$user) {
        //     return $this->utilityService->createErrorResponse('Kurs nie został dodany', 'Nie znaleziono użytkownika', Response::HTTP_UNAUTHORIZED);
        // }
        // $teacherId = $user->getId();

        $teacherId = 2005;

        try {
            $newCourse = $this->courseService->createCourse($data['title'], $data['description'], $teacherId ,$data['capacity'], $data['active']);
        } catch (CustomException $e) {
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

        return $this->utilityService->createSuccessResponse('Dodano nowy kurs.', ['course' => $courseData], Response::HTTP_CREATED);
    }

    #[OA\Response(response: 200, description: 'Zaktualizowano kurs')]
    #[OA\Response(response: 404, description: 'Not Found')]
    #[Route('/courses/{courseId}/capacity', name: 'api_teachers_courses_capacity', methods: ['PATCH'])]
    public function changeCapacity(int $courseId, Request $request): Response
    {
        // Pobieranie i walidacja danych
        try {
            $data = $this->utilityService->validateAndDecodeJson($request, 
                                                                        ['capacity']);
        } catch (\Exception $e) {
            return $this->utilityService->createErrorResponse('Kurs nie został zaktualizowany', 'Nie przekazano wymaganych danych', Response::HTTP_BAD_REQUEST);
        }

        $capacity = $data['capacity'];

        // sprawdzenie czy kurs jest prowadzony przez nauczyciela
        $course = $this->courseService->findCourse($courseId);
        $teacherId = $course->getTeacher()->getId();

        if ($teacherId != 2005) {
            return $this->utilityService->createErrorResponse('Kurs nie został zaktualizowany', 'Nie masz uprawnień do edycji tego kursu', Response::HTTP_FORBIDDEN);
        }
        
        try {
            $updatedCourse = $this->courseService->updateCourseCapacity($courseId,  $capacity);

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
                    'value' => $teacherId
                ]
            ];    


        } catch (CustomException $e) {
            return $this->utilityService->createErrorResponse('Kurs nie został zaktualizowany', $e->getMessage(), $e->getStatusCode());
        }

        $courseData = $updatedCourse->toArray();
            $courseData['_links'] = $this->utilityService->generateHateoasLinks($updatedCourse, $linksConfig);

        return $this->utilityService->createSuccessResponse('Pomyślnie zmieniono limit miejsc dla kursu.', ['course' => $courseData], Response::HTTP_OK);    

    }

    #[OA\Response(response: 200, description: 'Zablokowano rejestrację na kurs')]
    #[OA\Response(response: 404, description: 'Not Found')]
    #[Route('/courses/{courseId}/block', name: 'api_teachers_courses_block', methods: ['PATCH'])]
    public function blockCourse(int $courseId): Response
    {
        // SPrawdzenie nauczyciela
        // !!!!!!!!!!!!!!!!!!!

        // Załóżmy, że blokowanie kursu jest już zaimplementowane w CourseService
        $course = $this->courseService->updateCourseActive($courseId, false);
        // Generowanie odpowiedzi i linków HATEOAS dla zablokowanego kursu
        $courseData = $course->toArray();
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
                'value' => $course->getTeacher()->getId()
            ]
        ];
        $courseData['_links'] = $this->utilityService->generateHateoasLinks($course, $linksConfig);
        return $this->utilityService->createSuccessResponse('Pomyślnie zmieniono status kursu.', ['course' => $courseData], Response::HTTP_OK);    
    }

    #[OA\Response(response: 200, description: 'Zablokowano rejestrację na kurs')]
    #[OA\Response(response: 404, description: 'Not Found')]
    #[Route('/courses/{courseId}/unblock', name: 'api_teachers_courses_unblock', methods: ['PATCH'])]
    public function unblockCourse(int $courseId): Response
    {
        // SPrawdzenie nauczyciela
        // !!!!!!!!!!!!!!!!!!!

        // Załóżmy, że blokowanie kursu jest już zaimplementowane w CourseService
        $course = $this->courseService->updateCourseActive($courseId, true);
        // Generowanie odpowiedzi i linków HATEOAS dla zablokowanego kursu
        $courseData = $course->toArray();
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
                'value' => $course->getTeacher()->getId()
            ]
        ];
        $courseData['_links'] = $this->utilityService->generateHateoasLinks($course, $linksConfig);
        return $this->utilityService->createSuccessResponse('Pomyślnie zmieniono status kursu.', ['course' => $courseData], Response::HTTP_OK);    
    }

    #[OA\Response(response: 200, description: 'Lista studentów na kursie')]
    #[OA\Response(response: 404, description: 'Not Found')]
    #[Route('/courses/{courseId}/students', name: 'api_teachers_courses_students', methods: ['GET'])]
    public function getCourseStudents(int $courseId): Response
    {
        $students = $this->enrollmentService->findStudentsByCourse($courseId);

        if (empty($students)) {
            return $this->utilityService->createErrorResponse('Nie znaleziono studentów', 'Nie znaleziono studentów na kursie', Response::HTTP_NOT_FOUND);
        }

        $studentsData = [];
        foreach ($students as $student) {
            $studentData = $student->toArray();
            $studentData['_links'] = $this->utilityService->generateHateoasLinks($student, [
                'self' => [
                    'route' => 'api_students_id',
                    'param' => 'id',
                    'method' => 'GET'
                ],
                'grades' => [
                    'route' => 'api_students_grade',
                    'param' => ['courseId','studentId'],
                    'method' => 'GET',
                    'value' => [$courseId, $student->getId()]
                ]
            ]);
            $studentsData[] = $studentData;
        }

        return $this->utilityService->createSuccessResponse('Lista studentów na kursie.', ['students' => $studentsData], Response::HTTP_OK);

    }

    #[OA\Response(response: 200, description: 'Wystawienie oceny dla studenta')]
    #[OA\Response(response: 404, description: 'Not Found')]
    #[Route('/enrollments/{enrollmentsId}/grade', name: 'api_teachers_enrollments_grade', methods: ['PATCH'])]
    public function gradeStudent(int $enrollmentsId, Request $request): Response
    {
        // Pobieranie i walidacja danych
        try {
            $data = $this->utilityService->validateAndDecodeJson($request, 
                                                                        ['grade']);
        } catch (\Exception $e) {
            return $this->utilityService->createErrorResponse('Ocena nie została wystawiona', 'Nie przekazano wymaganych danych', Response::HTTP_BAD_REQUEST);
        }


        try{
            $enrollment = $this->enrollmentService->findEnrollment($enrollmentsId);
            if (!$enrollment) {
                throw CustomException::enrollmentNotFound($enrollmentsId);
        } 
        } catch (CustomException $e) {
            return $this->utilityService->createErrorResponse('Ocena nie została wystawiona', $e->getMessage(), $e->getStatusCode());
        }

        $courseId = $enrollment->getCourse()->getId();
        $studentId = $enrollment->getStudent()->getId();
        $grade = $data['grade'];

        // sprawdzenie czy kurs jet prowadzony przez nauczyciela
        $course = $this->courseService->findCourse($courseId);
        $teacherId = $course->getTeacher()->getId();

        if ($teacherId != 2005) {
            return $this->utilityService->createErrorResponse('Ocena nie została wystawiona', 'Nie masz uprawnień do edycji tego kursu', Response::HTTP_FORBIDDEN);
        }

        try {
            $student = $this->enrollmentService->gradeEnrollment($enrollmentsId, $grade);
        } catch (CustomException $e) {
            return $this->utilityService->createErrorResponse('Ocena nie została wystawiona', $e->getMessage(), $e->getStatusCode());
        }

        $studentData = $student->toArray();
        $linksConfig = [
            'self' => [
                'route' => 'api_students_id',
                'param' => 'id',
                'method' => 'GET'
            ],
            'grades' => [
                'route' => 'api_students_grade',
                'param' => ['courseId','studentId'],
                'method' => 'GET',
                'value' => [$courseId, $studentId]
            ]
        ];
        $studentData['_links'] = $this->utilityService->generateHateoasLinks($student, $linksConfig);

        return $this->utilityService->createSuccessResponse('Pomyślnie wystawiono ocenę.', ['student' => $studentData], Response::HTTP_OK);    
    }

}
