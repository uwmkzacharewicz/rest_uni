<?php
namespace App\Tests\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use App\Service\StudentService;
use App\Service\UtilityService;
use App\Entity\Student;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use PHPUnit\Framework\MockObject\MockObject;

class StudentControllerTest extends WebTestCase
{
    /**
     * @var MockObject|StudentService
     */
    private $studentService;

    /**
     * @var MockObject|UtilityService
     */
    private $utilityService;

    /**
     * @var MockObject|EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ContainerInterface
     */
    private $container;

    protected function setUp(): void
    {
        $client = static::createClient();

        $this->container = $client->getContainer();
        $this->studentService = $this->createMock(StudentService::class);
        $this->utilityService = $this->createMock(UtilityService::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->container->set(StudentService::class, $this->studentService);
        $this->container->set(UtilityService::class, $this->utilityService);
        $this->container->set(EntityManagerInterface::class, $this->entityManager);
    }

    public function testGetStudents()
    {
        $client = static::createClient();

        // Mock the service response
        $students = [new Student()];
        $this->studentService->method('findAllStudents')->willReturn($students);
        $this->utilityService->method('serializeJson')->willReturn(json_encode($students));

        $client->request('GET', '/api/students');

        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());
        $this->assertJson($client->getResponse()->getContent());
    }

    public function testGetStudentById()
    {
        $client = static::createClient();

        // Mock the service response
        $student = new Student();
        $this->studentService->method('findStudent')->willReturn($student);
        $this->utilityService->method('serializeJson')->willReturn(json_encode($student));

        $client->request('GET', '/api/students/1');

        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());
        $this->assertJson($client->getResponse()->getContent());
    }
}


?>