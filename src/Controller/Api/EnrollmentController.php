<?php

namespace App\Controller\Api;

use App\Entity\Enrollment;
use App\Service\EnrollmentService;
use App\Service\UtilityService;

use App\Exception\StudentNotFoundException;
use App\Exception\CourseNotFoundException;
use App\Exception\CourseNotActiveException;
use App\Exception\CourseFullException;
use App\Exception\StudentAlreadyEnrolledException;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use JMS\Serializer\SerializerInterface;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Annotation\Model;

#[OA\Tag(name: "Zapisy na kursy")]
#[Route("/api", "")]
class EnrollmentController extends AbstractController
{
    private $serializer;
    private $enrollmentService;
    private $utilityService;

    public function __construct(EnrollmentService $enrollmentService, SerializerInterface $serializer, UtilityService $utilityService) {

            $this->enrollmentService = $enrollmentService;
            $this->serializer = $serializer;
            $this->utilityService = $utilityService;
    }

    /**
     * Wyświetla listę zapisów.
     *
     * Wywołanie wyświetla zapisy na kursy
     * 
     */
    #[OA\Response(response: 200, description: 'Zwraca listę zapisów na kursy')]
    #[OA\Response(response: 404, description: 'Not Found')]
    #[Route('/enrollments', name: 'api_enrollments', methods: ['GET'])]
    public function getEnrollments(Request $request): Response
    {
        $studentId = $request->query->get('studentId');
        $courseId = $request->query->get('courseId');

        if ($studentId) {
            $enrollments = $this->enrollmentService->findEnrollmentsByStudent((int) $studentId);
        } elseif ($courseId) {
            $enrollments = $this->enrollmentService->findEnrollmentsByCourse((int) $courseId);
        } else {
            $enrollments = $this->enrollmentService->findAllEnrollments();
        }

        if (!$enrollments) {
            return $this->json(['error' => 'Nie znaleziono kursów'], JsonResponse::HTTP_NOT_FOUND);
        }

        $data = [];

        foreach ($enrollments as $enrollment) {
            $studentId = $enrollment->getStudent()->getId();
            $courseId = $enrollment->getCourse()->getId();
            $linksConfig = [
                'self' => [
                    'route' => 'api_enrollments_id',
                    'param' => 'id',
                    'method' => 'GET'
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
                    'value' => $courseId
                ]
            ];

            $enrollmentData = $enrollment->toArray();
            $enrollmentData['_links'] = $this->utilityService->generateHateoasLinks($enrollment, $linksConfig);

            $data[] = $enrollmentData;
        }

        $jsonContent = $this->utilityService->serializeJson($data);
        return new Response($jsonContent, 200, ['Content-Type' => 'application/json']);  

    }

    /**
     * Wyświetla zapis na kurs o podanym id.
     *
     * Wywołanie wyświetla zapis na kurs o podanym id
     * 
     */
    #[OA\Response(response: 200, description: 'Zwraca zapis na kurs')]
    #[OA\Response(response: 404, description: 'Not Found')]
    #[Route('/enrollments/{id}', name: 'api_enrollments_id', methods: ['GET'])]
    public function getEnrollment(int $id): Response
    {
        $enrollment = $this->enrollmentService->findEnrollment($id);

        if (!$enrollment) {
            return $this->json(['error' => 'Nie znaleziono zapisu o id ' . $id], JsonResponse::HTTP_NOT_FOUND);
        }

        $studentId = $enrollment->getStudent()->getId();
        $courseId = $enrollment->getCourse()->getId();
        $linksConfig = [
            'self' => [
                'route' => 'api_enrollments_id',
                'param' => 'id',
                'method' => 'GET'
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
                'value' => $courseId
            ]
        ];

        $enrollmentData = $enrollment->toArray();
        $enrollmentData['_links'] = $this->utilityService->generateHateoasLinks($enrollment, $linksConfig);

        $jsonContent = $this->utilityService->serializeJson($enrollmentData);
        return new Response($jsonContent, 200, ['Content-Type' => 'application/json']);  
    }

    /**
     * Dodaje nowy zapis na kurs.
     *
     * Wywołanie dodaje nowy zapis na kurs
     * 
     */
    #[OA\Response(response: 201, description: 'Zapisano na kurs')]
    #[OA\Response(response: 400, description: 'Bad Request')]
    #[Route('/enrollments', name: 'api_enrollments_add', methods: ['POST'])]
    public function addEnrollment(Request $request): Response
    {
        try {
            //Pobieranie i walidacja danych
            $data = $this->utilityService->validateAndDecodeJson($request, 
                                                                        ['studentId', 
                                                                        'courseId']);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Nie przekazano wymaganych danych'], JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            // Dodanie nowego zapisu
            $newEnrollment = $this->enrollmentService->createEnrollment($data['studentId'], $data['courseId']);
        } catch (StudentNotFoundException | CourseNotFoundException | CourseNotActiveException | CourseFullException $e) {
            return $this->json(['error' => $e->getMessage()], JsonResponse::HTTP_CONFLICT);
        }

        $studentId = $newEnrollment->getStudent()->getId();
        $courseId = $newEnrollment->getCourse()->getId();
        $linksConfig = [
            'self' => [
                'route' => 'api_enrollments_id',
                'param' => 'id',
                'method' => 'GET'
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
                'value' => $courseId
            ]
        ];

        $enrollmentData = $newEnrollment->toArray();
        $enrollmentData['_links'] = $this->utilityService->generateHateoasLinks($newEnrollment, $linksConfig);

        $jsonContent = $this->utilityService->serializeJson($enrollmentData);
        return new Response($jsonContent, 200, ['Content-Type' => 'application/json']);  
    }

    // Wystaw Ocenę
    #[OA\Response(response: 200, description: 'Oceniono zapis na kurs')]
    #[OA\Response(response: 400, description: 'Bad Request')]
    #[Route('/enrollments/{id}/grade', name: 'api_enrollments_grade', methods: ['POST'])]
    public function gradeEnrollment(int $id, Request $request): Response
    {
        try {
            //Pobieranie i walidacja danych
            $data = $this->utilityService->validateAndDecodeJson($request, 
                                                                        ['grade']);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Nie przekazano wymaganych danych'], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Dodanie nowego zapisu
        $enrollment = $this->enrollmentService->gradeEnrollment($id, $data['grade']);
        
        $studentId = $enrollment->getStudent()->getId();
        $courseId = $enrollment->getCourse()->getId();
        $linksConfig = [
            'self' => [
                'route' => 'api_enrollments_id',
                'param' => 'id',
                'method' => 'GET'
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
                'value' => $courseId
            ]
        ];

        $enrollmentData = $enrollment->toArray();
        $enrollmentData['_links'] = $this->utilityService->generateHateoasLinks($enrollment, $linksConfig);

        $jsonContent = $this->utilityService->serializeJson($enrollmentData);
        return new Response($jsonContent, 200, ['Content-Type' => 'application/json']);  
    }

    







}