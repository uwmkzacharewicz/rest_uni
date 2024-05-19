<?php

namespace App\Controller\Api;

use App\Service\StudentService;
use App\Service\EnrollmentService;
use App\Service\UtilityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use OpenApi\Attributes as OA;

use App\Exception\CustomException;
use Exception;


#[OA\Tag(name: "Akcje dla studenta")]
#[Security(name: 'Bearer')]
#[Route("/api/students", "")]
class StudentActionController extends AbstractController
{
    private $studentService;
    private $enrollmentService;
    private $utilityService;

    public function __construct(StudentService $studentService, EnrollmentService $enrollmentService, UtilityService $utilityService) {
        $this->studentService = $studentService;
        $this->enrollmentService = $enrollmentService;
        $this->utilityService = $utilityService;
    }

    #[OA\Response(response: 201, description: 'Student zapisany na kurs')]
    #[OA\Response(response: 400, description: 'Bad Request')]
    #[Route('/{studentId}/enrollments', name: 'api_students_enroll', methods: ['POST'])]
    public function enrollStudent(int $studentId, Request $request): Response
    {
        try {
            $data = $this->utilityService->validateAndDecodeJson($request, ['courseId']);
            $enrollment = $this->enrollmentService->createEnrollment($studentId, $data['courseId']);

            $linksConfig = [
                'self' => [
                    'route' => 'api_enrollments_id',
                    'param' => 'id',
                    'method' => 'GET',
                    'value' => $enrollment->getId()
                ],
                'studentData' => [
                    'route' => 'api_students_id',
                    'param' => 'id',
                    'method' => 'GET',
                    'value' => $studentId
                ],
                'courseData' => [
                    'route' => 'api_courses_id',
                    'param' => 'id',
                    'method' => 'GET',
                    'value' => $data['courseId']
                ]
            ];

            $enrollmentData = $enrollment->toArray();
            $enrollmentData['_links'] = $this->utilityService->generateHateoasLinks($enrollment, $linksConfig);

            return $this->utilityService->createSuccessResponse('Pomyślnie zapisano na kurs.', ['enrollment' => $enrollmentData], Response::HTTP_OK);  
        
        } catch (CustomException $e) {     
            return $this->utilityService->createErrorResponse('Zapis nie został usunięty', $e->getMessage(), $e->getStatusCode());
        } catch (Exception $e) {
            return new JsonResponse([
                'error' => 'Wystąpił błąd',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Response(response: 200, description: 'Student wypisany z kursu')]
    #[OA\Response(response: 404, description: 'Not Found')]
    #[Route('/{studentId}/enrollments/{enrollmentId}', name: 'api_students_unenroll', methods: ['DELETE'])]
    public function unenrollStudent(int $studentId, int $enrollmentId): Response
    {
        try {
            $this->enrollmentService->deleteEnrollment($enrollmentId);
            return $this->utilityService->createSuccessResponse('Pomyślnie wypisano z kursu.', [], Response::HTTP_OK);  
        } catch (Exception $e) {
            return new JsonResponse([
                'error' => 'Wystąpił błąd',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[OA\Response(response: 200, description: 'Ocena za kurs')]
    #[OA\Response(response: 404, description: 'Not Found')]
    #[Route('/{studentId}/courses/{courseId}/grade', name: 'api_students_grade', methods: ['GET'])]
    public function getGrade(int $studentId, int $courseId): Response
    {
        try {
            $enrollment = $this->enrollmentService->findEnrollmentByStudentAndCourse($studentId, $courseId);

            $grade = $enrollment->getGrade();

            $studentName = $enrollment->getStudent()->getName();
            $courseTitle = $enrollment->getCourse()->getTitle();

            $data = [
                'student' =>  $studentName,
                'courseId' => $courseTitle,
                'grade' => $grade
            ];

            $jsonContent = $this->utilityService->serializeJson($data);
            return new Response($jsonContent, Response::HTTP_OK, ['Content-Type' => 'application/json']);
        
        } catch (CustomException $e) {     
            return $this->utilityService->createErrorResponse('Student nie został zaktualizowany', $e->getMessage(), $e->getStatusCode());
        } catch (Exception $e) {
            return new JsonResponse([
                'error' => 'Wystąpił błąd',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }  
    }




}
