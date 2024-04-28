<?php

namespace App\Controller\Api;

use App\Entity\Student;
use App\Entity\Teacher;
use App\Entity\Course;
use App\Entity\Enrollment;
use App\Security\Role;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use JMS\Serializer\SerializerBuilder;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

use OpenApi\Attributes as OA;



#[Route("/api", "")]
class StudentController extends AbstractController
{
    private $urlGenerator;
    private $serializer;
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager, UrlGeneratorInterface $urlGenerator, SerializerInterface $serializer) {

        $this->entityManager = $entityManager;
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
        
        $students = $this->entityManager->getRepository(Student::class)->findAll();
        
        $data = [];
        
        foreach ($students as $student) {
            $data[] = [
                'id' => $student->getId(),
                'name' => $student->getName(),
                '_links' => [
                    'self' => [
                        'href' => $this->urlGenerator->generate('api_students_id', ['id' => $student->getId()], UrlGeneratorInterface::ABSOLUTE_URL)
                    ]
                ]                
            ];
        }
        
        $jsonContent = $this->serializer->serialize($data, 'json', \JMS\Serializer\SerializationContext::create()->setSerializeNull(true));
        return new Response($jsonContent, 200, ['Content-Type' => 'application/json']);
        return $this->json($data);
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
        $student = $this->entityManager->getRepository(Student::class)->find($id);

         // Sprawdzenie, czy student został znaleziony
         if (!$student) {
            // Jeśli nie znaleziono studenta, zwróć odpowiedź z błędem 404
            return $this->json(['error' => 'Nie znaleziono studenta o id ' . $id], JsonResponse::HTTP_NOT_FOUND);
        }
        
        // Jeśli student został znaleziony, przygotuj i zwróć dane studenta
        $data = [
            'id' => $student->getId(),
            'name' => $student->getName(),
            'email' => $student->getEmail(),
            '_links' => [
                'self' => ['href' => $this->urlGenerator->generate('api_students_id', ['id' => $student->getId()], UrlGeneratorInterface::ABSOLUTE_URL)],
                'allCourses' => ['href' => $this->urlGenerator->generate('api_students_id', ['id' => $student->getId()], UrlGeneratorInterface::ABSOLUTE_URL)],
                
            ]  
        ];
        
        $jsonContent = $this->serializer->serialize($data, 'json', \JMS\Serializer\SerializationContext::create()->setSerializeNull(true));
        return new Response($jsonContent, 200, ['Content-Type' => 'application/json']);
        return $this->json($data);
    }


}
