<?php

namespace App\Controller\Api;

use App\Entity\Enrollment;
use App\Service\EnrollmentService;
use App\Service\UtilityService;

use App\Exception\CustomException;
use Exception;

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
            return $this->json(['error' => 'Nie znaleziono kursów o podanych kryteriach', 'code' => Response::HTTP_NOT_FOUND], Response::HTTP_NOT_FOUND);
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
            return $this->json(['error' => 'Nie znaleziono zapisu o id ' . $id], Response::HTTP_NOT_FOUND);
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
            ],
            'setGrade' => [
                'route' => 'api_enrollments_grade',
                'param' => 'id',
                'method' => 'PATCH'
            ],
            'edit' => [
                'route' => 'api_students_id',
                'param' => 'id',
                'method' => 'PUT'
            ],
            'update' => [
                'route' => 'api_students_id',
                'param' => 'id',
                'method' => 'PATCH'
            ],
            'delete' => [
                'route' => 'api_students_id',
                'param' => 'id',
                'method' => 'DELETE'
            ],
            'create' => [
                'route' => 'api_students_add',
                'method' => 'POST'
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
            // Obsługa wyjątków
             return $this->utilityService->createErrorResponse('Zapis nie został dodany', 'Nie przekazano wymaganych danych', Response::HTTP_BAD_REQUEST);
        }

        try {
            // Dodanie nowego zapisu
            $newEnrollment = $this->enrollmentService->createEnrollment($data['studentId'], $data['courseId']);
        } catch (CustomException $e) {
            return $this->utilityService->createErrorResponse('Zapis nie został dodany', $e->getMessage(), $e->getStatusCode());
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

        return $this->utilityService->createSuccessResponse('Dodano nowy zapis na kurs.', ['enrollment' => $enrollmentData], Response::HTTP_CREATED); 
    }

    // Wystaw Ocenę
    #[OA\Response(response: 200, description: 'Oceniono zapis na kurs')]
    #[OA\Response(response: 400, description: 'Bad Request')]
    #[Route('/enrollments/{id}/grade', name: 'api_enrollments_grade', methods: ['PATCH'])]
    public function gradeEnrollment(int $id, Request $request): Response
    {
        try {
            // Pobranie i walidacja danych
            $data = $this->utilityService->validateAndDecodeJson($request, ['grade']);
        } catch (\Exception $e) {
            // Obsługa wyjątków
            return $this->utilityService->createErrorResponse('Zapis nie został edytowany', 'Nie przekazano wymaganych danych', Response::HTTP_BAD_REQUEST);
        }

        try {
            $enrollment = $this->enrollmentService->gradeEnrollment($id, $data['grade']);
        } catch (CustomException $e) {     
            return $this->utilityService->createErrorResponse('Zapis nie został zaktualizowany', $e->getMessage(), $e->getStatusCode());
        } catch (Exception $e) {
            return new JsonResponse([
                'error' => 'Wystąpił błąd',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
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

        return $this->utilityService->createSuccessResponse('Pomyślnie zaktualizowano ocenę dla zapisu.', ['enrollment' => $enrollmentData], Response::HTTP_OK);   
    }

    /**
     * Edytuje zapis o podanym id.
     *
     * Wywołanie edycji zapisu kursu o podanym id
     * 
     */
    #[OA\Response(response: 200, description: 'Zaktualizowano zapis na kurs')]
    #[OA\Response(response: 400, description: 'Bad Request')]
    #[OA\Response(response: 404, description: 'Not Found')]
    #[Route('/enrollments/{id}', name: 'api_enrollments_edit', methods: ['PUT'])]
    public function editEnrollment(int $id, Request $request): Response
    {
        try {
            //Pobieranie i walidacja danych
            $data = $this->utilityService->validateAndDecodeJson($request, 
                                                                        ['studentId', 
                                                                        'courseId', 'grade']);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Nie przekazano wymaganych danych'], Response::HTTP_BAD_REQUEST);
        }

        try {
            // Edycja zapisu
            $editedEnrollment = $this->enrollmentService->editEnrollment($id, $data['studentId'], $data['courseId'], $data['grade']);
        } catch (CustomException $e) {     
            return $this->utilityService->createErrorResponse('Zapis nie został zaktualizowany', $e->getMessage(), $e->getStatusCode());
        } catch (Exception $e) {
            return new JsonResponse([
                'error' => 'Wystąpił błąd',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $studentId = $editedEnrollment->getStudent()->getId();
        $courseId = $editedEnrollment->getCourse()->getId();
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

        $enrollmentData = $editedEnrollment->toArray();
        $enrollmentData['_links'] = $this->utilityService->generateHateoasLinks($editedEnrollment, $linksConfig);
        
        return $this->utilityService->createSuccessResponse('Pomyślnie zaktualizowano zapis na kurs.', ['enrollment' => $enrollmentData], Response::HTTP_OK);  
    }

    /**
     * Aktualizacja zapisu na kurs.
     *
     * Wywołanie aktualizuje użytkownika o podanym id lub tworzy nowego użytkownika.
     * 
     */
    #[OA\Response(response: 200, description: 'Zaktualizowano zapis na kurs')]
    #[OA\Response(response: 400, description: 'Bad Request')]
    #[Route('/enrollments/{id}', name: 'api_enrollments_update', methods: ['PATCH'])]
    public function updateEnrollment(int $id, Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json(['error' => 'Niepoprawny JSON'], Response::HTTP_BAD_REQUEST);
        }

        try {
            // Aktualizacja zapisu
            $updatedEnrollment = $this->enrollmentService->updateEnrollment($id, $data);
        } catch (CustomException $e) {     
            return $this->utilityService->createErrorResponse('Zapis nie został zaktualizowany', $e->getMessage(), $e->getStatusCode());
        } catch (Exception $e) {
            return new JsonResponse([
                'error' => 'Wystąpił błąd',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $studentId = $updatedEnrollment->getStudent()->getId();
        $courseId = $updatedEnrollment->getCourse()->getId();
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

        $enrollmentData = $updatedEnrollment->toArray();
        $enrollmentData['_links'] = $this->utilityService->generateHateoasLinks($updatedEnrollment, $linksConfig);
        
        return $this->utilityService->createSuccessResponse('Pomyślnie zaktualizowano zapis na kurs.', ['enrollment' => $enrollmentData], Response::HTTP_OK);  
    }


    /**
     * Usuwa zapis o podanym id.
     *
     * Wywołanie usuwa zapis na kurs o podanym id
     * 
     */
    #[OA\Response(response: 200, description: 'Usunięto zapis na kurs')]
    #[OA\Response(response: 404, description: 'Not Found')]
    #[Route('/enrollments/{id}', name: 'api_enrollments_delete', methods: ['DELETE'])]
    public function deleteEnrollment(int $id): Response
    {
        try {
            // Usunięcie zapisu
            $this->enrollmentService->deleteEnrollment($id);
        } catch (CustomException $e) {     
            return $this->utilityService->createErrorResponse('Zapis nie został usunięty', $e->getMessage(), $e->getStatusCode());
        } catch (Exception $e) {
            return new JsonResponse([
                'error' => 'Wystąpił błąd',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->utilityService->createSuccessResponse('Pomyślnie usunięto zapis na kurs.', [], Response::HTTP_OK);  
    }




}