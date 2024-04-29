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
class TeacherController extends AbstractController
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
     * Wyświetla listę nauczycieli.
     *
     * Wywołanie wyświetla wszystkich nauczycieli wraz z ich linkiem do szczegółów.
     * 
     */
    #[OA\Tag(name: "Operacje na nauczycielach")]
    #[OA\Response(
        response: 200,
        description: 'Zwraca listę nauczycieli.',
        content: new OA\JsonContent(ref: "#/components/schemas/Teacher")
    )]
    #[OA\Response(
        response: 404,
        description: 'Not Found'
    )]
    #[Route('/teachers', name: 'api_teachers', methods: ['GET'])]
    public function getTeachers() : Response
    {        
        $teachers = $this->entityManager->getRepository(Teacher::class)->findAll();  
        $data = [];
        
        foreach ($teachers as $teacher) {
            $data[] = [
                'id' => $teacher->getId(),
                'name' => $teacher->getName(),
                'email' => $teacher->getEmail(),
                'specialization' => $teacher->getSpecialization(),                
                '_links' => [
                    'self' => [
                       'href' => $this->urlGenerator->generate('api_teachers_id', ['id' => $teacher->getId()], UrlGeneratorInterface::ABSOLUTE_URL)
                    ],
                    'courses' => [
                       'href' => $this->urlGenerator->generate('api_teachers_courses', ['id' => $teacher->getId()], UrlGeneratorInterface::ABSOLUTE_URL)
                    ]                    
                ]
            ];
        }
    
        $jsonContent = $this->serializer->serialize($data, 'json', \JMS\Serializer\SerializationContext::create()->setSerializeNull(true));
        return new Response($jsonContent, 200, ['Content-Type' => 'application/json']);
    }

    /**
     * Wyświetla szczegóły nauczyciela.
     *
     * Wywołanie wyświetla szczegóły nauczyciela o podanym identyfikatorze.
     * 
     */
    #[Route('/teachers/{id}', name: 'api_teachers_id', methods: ['GET'])]
    public function getTeacher(int $id) : Response
    {
        $teacher = $this->entityManager->getRepository(Teacher::class)->find($id);  
        
        if (!$teacher) {
            return new Response(null, 404);
        }
        
        $data = [
            'id' => $teacher->getId(),
            'name' => $teacher->getName(),
            'email' => $teacher->getEmail(),
            'specialization' => $teacher->getSpecialization(),
            '_links' => [
                'self' => [
                    'href' => $this->urlGenerator->generate('api_teachers_id', ['id' => $teacher->getId()], UrlGeneratorInterface::ABSOLUTE_URL)
                ],
                'courses' => [
                    'href' => $this->urlGenerator->generate('api_teachers_courses', ['id' => $teacher->getId()], UrlGeneratorInterface::ABSOLUTE_URL)
                ]                
            ]
        ];
    
        $jsonContent = $this->serializer->serialize($data, 'json', \JMS\Serializer\SerializationContext::create()->setSerializeNull(true));
        return new Response($jsonContent, 200, ['Content-Type' => 'application/json']);
    }
    


    #[Route('/teachers/{id}/courses', name: 'api_teachers_courses', methods: ['GET'])]
    public function getTeacherCourses(int $id) : Response
    {
        $teacher = $this->entityManager->getRepository(Teacher::class)->find($id);  
        
        if (!$teacher) {
            return new Response(null, 404);
        }
        
        $courses = $teacher->getCourses();
        $data = [];
        
        foreach ($courses as $course) {
            $data[] = [
                'id' => $course->getId(),
                'title' => $course->getTitle(),
                'description' => $course->getDescription(),
                'capacity' => $course->getCapacity(),
                'active' => $course->isActive(),
                '_links' => [
                    'self' => [
                        'href' => $this->urlGenerator->generate('api_courses_id', ['id' => $course->getId()], UrlGeneratorInterface::ABSOLUTE_URL)
                    ]
                ]
            ];
        }
    
        $jsonContent = $this->serializer->serialize($data, 'json', \JMS\Serializer\SerializationContext::create()->setSerializeNull(true));
        return new Response($jsonContent, 200, ['Content-Type' => 'application/json']);
    }

    

















}
