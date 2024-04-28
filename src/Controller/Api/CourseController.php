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
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;

#[Route("/api", "")]
class CourseController extends AbstractController
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
     * Wyświetla listę kursów.
     *
     * Wywołanie wyświetla wszystkie kursy wraz z ich linkiem do szczegółów.
     * 
     */
    #[OA\Tag(name: "Operacje na kursach")]
    #[OA\Response(
        response: 200,
        description: 'Zwraca kurs o podanym identyfikatorze',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Student::class))
        ))
    ]
    #[OA\Response(
        response: 404,
        description: 'Not Found'
    )]   
    #[Route('/courses', name: 'api_course', methods: ['GET'])]
    public function getCourses() 
    {
        
        $courses = $this->entityManager->getRepository(Course::class)->findAll();        

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
                                'href' => $this->urlGenerator->generate('api_students-id', ['id' => $teacher->getId()], UrlGeneratorInterface::ABSOLUTE_URL)
                            ]
                        ]       
                ],
                'capacity' => $course->getCapacity(),
                'active' => $course->isActive()             
            ];
        }
    
        $jsonContent = $this->serializer->serialize($data, 'json', \JMS\Serializer\SerializationContext::create()->setSerializeNull(true));
        return new Response($jsonContent, 200, ['Content-Type' => 'application/json']);
    }
    
    
    
    public function index(): Response
    {
        return $this->render('api/course/index.html.twig', [
            'controller_name' => 'CourseController',
        ]);
    }
}
