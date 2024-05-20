<?php

namespace App\Controller\Api;

use App\Service\StudentService;
use App\Service\EnrollmentService;
use App\Service\UtilityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use OpenApi\Attributes as OA;

use App\Exception\CustomException;
use Exception;



#[OA\Tag(name: "Akcje dla studenta")]
#[Security(name: "Bearer")]
#[OA\Response(response: 200, description: 'OK')]
#[OA\Response(response: 201, description: 'Zasób został dodany')]
#[OA\Response(response: 400, description: 'Błąd w przesłanych danych')]
#[OA\Response(response: 403, description: 'Brak dostępu')]
#[OA\Response(response: 404, description: 'Zasób nie znaleziony')]
#[OA\Response(response: 409, description: 'Konflikt danych')]
#[OA\Response(response: 500, description: 'Błąd serwera')]
#[Route("/api/students", "")]
class StudentActionController extends AbstractController
{
    private $studentService;
    private $enrollmentService;
    private $utilityService;
    private $tokenStorage;


    public function __construct(StudentService $studentService, EnrollmentService $enrollmentService, UtilityService $utilityService, TokenStorageInterface $tokenStorage) {
        $this->studentService = $studentService;
        $this->enrollmentService = $enrollmentService;
        $this->utilityService = $utilityService;
        $this->tokenStorage = $tokenStorage;
    }

    private function checkStudentAccess(int $studentId): void
    {
        //$this->denyAccessUnlessGranted('ROLE_STUDENT');
        $token = $this->tokenStorage->getToken();
        $user = $token->getUser();

        $student = $this->studentService->findStudentByUser($user);

        if (!$student || $student->getId() !== $studentId) {
            throw CustomException::accessDenied();
        }        
    }

    /** Zapisuje studenta na kurs
     * 
     * Wywołanie zapisuje studenta na kurs
     * 
    */
    #[OA\RequestBody(description: 'Dane zapisu', required: true, content: new OA\JsonContent(ref: "#/components/schemas/EnrollCourse"))]
    #[Route('/{studentId}/enrollments', name: 'api_students_enroll', methods: ['POST'])]
    public function enrollStudent(int $studentId, Request $request): Response
    {  
        try {
            $this->checkStudentAccess($studentId);
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
            return $this->utilityService->createErrorResponse('Zapis nie został dodany', $e->getMessage(), $e->getStatusCode());
        } catch (Exception $e) {
            return new JsonResponse([
                'error' => 'Wystąpił błąd',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /** Wypisuje studenta na kurs
     * 
     *  Wywołanie wypisuje studenta z kursu
     * 
    */
    #[Route('/{studentId}/enrollments/{enrollmentId}', name: 'api_students_unenroll', methods: ['DELETE'])]
    public function unenrollStudent(int $studentId, int $enrollmentId): Response
    {
        try {
            $this->checkStudentAccess($studentId);
            $this->enrollmentService->deleteEnrollment($enrollmentId);
            return $this->utilityService->createSuccessResponse('Pomyślnie wypisano z kursu.', [], Response::HTTP_OK);  
        } catch (Exception $e) {
            return new JsonResponse([
                'error' => 'Wystąpił błąd',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /** Wyświetla ocenę z kursu
     * 
     * Wywołanie zwraca ocenę studenta z kursu
     * 
    */
    #[Route('/enrollments/{enrollmentsId}/grade', name: 'api_students_enrollments_grade', methods: ['GET'])]
    public function getGrade(int $enrollmentsId): Response
    {
        try{
            $enrollment = $this->enrollmentService->findEnrollment($enrollmentsId);
            if (!$enrollment) {
                throw CustomException::enrollmentNotFound($enrollmentsId);
            } 
            $studentId = $enrollment->getStudent()->getId();
            $this->checkStudentAccess($studentId);

        } catch (CustomException $e) {
            return $this->utilityService->createErrorResponse('Ocena nie została wystawiona', $e->getMessage(), $e->getStatusCode());
        }

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
    }

}
