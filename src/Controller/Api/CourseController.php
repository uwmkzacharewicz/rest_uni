<?php

namespace App\Controller\Api;

use App\Entity\Student;
use App\Entity\Teacher;
use App\Entity\Course;
use App\Entity\Enrollment;
use App\Security\Role;
use App\Service\EntityService;

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
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Doctrine\ORM\QueryBuilder;

use Psr\Log\LoggerInterface;
use OpenApi\Attributes as OA;

#[Route("/api", "")]
class CourseController extends AbstractController
{
    private $urlGenerator;
    private $serializer;
    private $entityService;

    public function __construct(EntityService $entityService, UrlGeneratorInterface $urlGenerator, SerializerInterface $serializer) {

            $this->entityService = $entityService;
            $this->urlGenerator = $urlGenerator;
            $this->serializer = $serializer;
    }


    #[Route('/test/{id}', name: 'api_test', methods: ['GET'])]
    public function test(int $id) : Response
    {
        $course = $this->entityManager->getRepository(Course::class)->find($id);
        
        
        if (!$course) {
            return new JsonResponse(['message' => 'Course not found'], 404);
        }
        
        $teacher = $course->getTeacher();
     
        $data = $course->toArray();
        $data['teacher'] = $teacher ? $teacher->toArray() : null;
        $data['teacher']['_links'] = ['self' => [
                'href' => $this->urlGenerator->generate('api_teachers_id', ['id' => $teacher->getId()], UrlGeneratorInterface::ABSOLUTE_URL)
                ]];
        $jsonContent = $this->serializer->serialize($data, 'json', \JMS\Serializer\SerializationContext::create()->setSerializeNull(true));
        return new Response($jsonContent, 200, ['Content-Type' => 'application/json']);
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
        description: 'Zwraca listę kursów',
        content: new OA\JsonContent(ref: "#/components/schemas/Course")
    )]
    #[OA\Response(
        response: 404,
        description: 'Not Found'
    )]

    #[Route('/courses', name: 'api_courses', methods: ['GET'])]
    public function getCourses(
        #[MapQueryParameter] int $page,
        #[MapQueryParameter] int $limit
    ) : Response
    {  
        
        $query = $this->entityManager->getRepository(Course::class)
                ->createQueryBuilder('c')
                ->getQuery(); 
                
        $paginator = new Doctrine\ORM\Tools\Pagination\Paginator($query);
        $paginator->getQuery()
                    ->setFirstResult($limit * ($page - 1)) // Przesunięcie zależne od numeru strony
                    ->setMaxResults($limit); // Maksymalna liczba wyników


                    $courses = [];
                    foreach ($paginator as $course) {
                        $teacher = $course->getTeacher();
                        $courses[] = [
                            'id' => $course->getId(),
                            'title' => $course->getTitle(),
                            'description' => $course->getDescription(),
                            'teacher' => $teacher ? [
                                'id' => $teacher->getId(),
                                'name' => $teacher->getName(),
                            ] : null,
                            'capacity' => $course->getCapacity(),
                            'active' => $course->isActive(),
                        ];
                    }
                
                    $jsonContent = $this->serializer->serialize([
                        'data' => $courses,
                        'current_page' => $page,
                        'items_per_page' => $limit,
                        'total_items' => count($paginator),
                    ], 'json');
                
        return new Response($jsonContent, 200, ['Content-Type' => 'application/json']);           

        // $courses = $this->entityManager->getRepository(Course::class)->findAll();       
        // $data = [];
        
        // foreach ($courses as $course) {
        //     $teacher = $course->getTeacher();
        //     $data[] = [                
        //         'id' => $course->getId(),
        //         'title' => $course->getTitle(),
        //         'description' => $course->getDescription(),
        //         'teacher' => [
        //             'id' => $teacher ? $teacher->getId() : null,
        //             'name' => $teacher ? $teacher->getName() : null,
        //             '_links' => ['self' => [
        //                         'href' => $this->urlGenerator->generate('api_students_id', ['id' => $teacher->getId()], UrlGeneratorInterface::ABSOLUTE_URL)
        //                     ]
        //                 ]       
        //         ],
        //         'capacity' => $course->getCapacity(),
        //         'active' => $course->isActive()             
        //     ];
        // }
    
        //$jsonContent = $this->serializer->serialize($data, 'json', \JMS\Serializer\SerializationContext::create()->setSerializeNull(true));
        //return new Response($jsonContent, 200, ['Content-Type' => 'application/json']);
    }
    
    /**
     * Wyświetla kurs.
     *
     * Wywołanie wyświetla kurs wraz z jego linkiem do szczegółów.
     * 
     */
    #[OA\Tag(name: "Operacje na kursach")]
    #[OA\Response(
        response: 200,
        description: 'Zwraca kursów o podanym identyfikatorze',
        content: new OA\JsonContent(ref: "#/components/schemas/Course")
    )]
    #[Route('/courses/{id}', name: 'api_courses_id', methods: ['GET'])]
    public function getCourse(int $id) : Response
    {
        $course = $this->entityManager->getRepository(Course::class)->find($id);
        
        if (!$course) {
            return new JsonResponse(['message' => 'Course not found'], 404);
        }
        
        $teacher = $course->getTeacher();
        $data = [
            'id' => $course->getId(),
            'title' => $course->getTitle(),
            'description' => $course->getDescription(),
            'teacher' => [
                'id' => $teacher ? $teacher->getId() : null,
                'name' => $teacher ? $teacher->getName() : null,
                '_links' => ['self' => [
                            'href' => $this->urlGenerator->generate('api_teachers_id', ['id' => $teacher->getId()], UrlGeneratorInterface::ABSOLUTE_URL)
                        ]
                    ]       
            ],
            'capacity' => $course->getCapacity(),
            'active' => $course->isActive()             
        ];
        
        $jsonContent = $this->serializer->serialize($data, 'json', \JMS\Serializer\SerializationContext::create()->setSerializeNull(true));
        return new Response($jsonContent, 200, ['Content-Type' => 'application/json']);
    }
    


    /**
     * Dodaje kurs.
     *
     * Wywołanie dodaje nowy kurs.
     * 
     */
    #[OA\Tag(name: "Operacje na kursach")]
    #[OA\Response(
        response: 201,
        description: 'Dodaje kurs',
        content: new OA\JsonContent(ref: "#/components/schemas/Course")
    )]
    #[OA\RequestBody(
        description: 'Dane kursu',
        required: true,
        content: new OA\JsonContent(ref: "#/components/schemas/NewCourse")
    )]
    #[Route('/courses', name: 'api_courses_add', methods: ['POST'])]
    public function addCourse(Request $request) : Response
    {
        $data = json_decode($request->getContent(), true);

        if (empty($data['title']) || empty($data['description']) || empty($data['teacher_id']) || !isset($data['capacity']) || !isset($data['active'])) {
            return $this->json(['error' => 'Missing data'], Response::HTTP_BAD_REQUEST);
        }

        $teacher = $entityService->findTeacher($data['teacher_id']);       
        if (!$teacher) {
             return $this->json(['error' => 'Teacher not found'], Response::HTTP_NOT_FOUND);
        }

        $course = new Course();
        $course->setTitle($data['title']);
        $course->setDescription($data['description']);
        $course->setCapacity($data['capacity']);
        $course->setActive($data['active']);
        
        $teacher = $this->entityManager->getRepository(Teacher::class)->find($data['teacher_id']);
        $course->setTeacher($teacher);
        
        $this->entityManager->persist($course);
        $this->entityManager->flush();
        
        $data = [
            'id' => $course->getId(),
            'title' => $course->getTitle(),
            'description' => $course->getDescription(),
            'teacher' => [
                'id' => $teacher ? $teacher->getId() : null,
                'name' => $teacher ? $teacher->getName() : null,
                '_links' => ['self' => [
                            'href' => $this->urlGenerator->generate('api_teachers_id', ['id' => $teacher->getId()], UrlGeneratorInterface::ABSOLUTE_URL)
                        ]
                    ]       
            ],
            'capacity' => $course->getCapacity(),
            'active' => $course->isActive()             
        ];
        
        $jsonContent = $this->serializer->serialize($data, 'json', \JMS\Serializer\SerializationContext::create()->setSerializeNull(true));
        return new Response($jsonContent, 201, ['Content-Type' => 'application/json']);
    }
}
