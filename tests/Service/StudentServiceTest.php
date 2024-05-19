<?php

namespace App\Tests\Service;

use App\Entity\Student;
use App\Entity\Course;
use App\Entity\Enrollment;
use App\Entity\User;
use App\Security\Role;
use App\Repository\StudentRepository;
use App\Service\EntityService;
use App\Service\StudentService;
use App\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class StudentServiceTest extends TestCase
{
    private $entityManager;
    private $entityService;
    private $userService;
    private $studentService;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->entityService = $this->createMock(EntityService::class);
        $this->userService = $this->createMock(UserService::class);

        $this->studentService = new StudentService(
            $this->entityManager,
            $this->entityService,
            $this->userService
        );
    }

    public function testFindStudent(): void
    {
        $student = new Student();
        $student->setId(1);
        $student->setName('John Doe');
        $student->setEmail('john.doe@example.com');

        $this->entityService
            ->method('find')
            ->with(Student::class, 1)
            ->willReturn($student);

        $result = $this->studentService->findStudent(1);

        $this->assertSame($student, $result);
    }

    public function testFindEnrolledCourses(): void
    {
        $student = new Student();
        $student->setId(1);
        $student->setName('John Doe');
        $student->setEmail('john.doe@example.com');

        $course = new Course();
        $course->setId(1);
        $course->setTitle('Sample Course');

        $enrollment = new Enrollment();
        $enrollment->setStudent($student);
        $enrollment->setCourse($course);

        $student->addEnrollment($enrollment);

        $this->entityService
            ->method('find')
            ->with(Student::class, 1)
            ->willReturn($student);

        $result = $this->studentService->findEnrolledCourses(1);

        $this->assertCount(1, $result);
        $this->assertSame($course, $result[0]);
    }

    public function testCreateStudent(): void
    {
        $name = 'Jane Smith';
        $email = 'jane.smith@example.com';
        $username = 'jane123';
        $password = 'password123';

        $this->entityService
            ->method('findEntityByField')
            ->with(User::class, 'username', $username)
            ->willReturn(null);

        $newUser = new User();
        $this->userService
            ->expects($this->once())
            ->method('addUser')
            ->with($username, $password, [Role::ROLE_STUDENT])
            ->willReturn($newUser);

        $this->entityService
            ->expects($this->once())
            ->method('addEntity')
            ->with(
                $this->equalTo(Student::class),
                $this->callback(function($data) use ($newUser) {
                    return $data['name'] === 'Jane Smith' &&
                           $data['email'] === 'jane.smith@example.com' &&
                           $data['user'] === $newUser;
                })
            )
            ->willReturn(new Student());

        $result = $this->studentService->createStudent($name, $email, $username, $password);

        $this->assertInstanceOf(Student::class, $result);
    }

    public function testEditStudent(): void
    {
        $id = 1;
        $name = 'Jane Smith';
        $email = 'jane.smith@example.com';

        $student = new Student();
        $student->setId($id);
        $student->setName('John Doe');
        $student->setEmail('john.doe@example.com');

        $this->entityService
            ->method('find')
            ->with(Student::class, 1)
            ->willReturn($student);

        $updatedStudent = new Student();
        $updatedStudent->setId($id);
        $updatedStudent->setName($name);
        $updatedStudent->setEmail($email);

        $this->entityService
            ->expects($this->once())
            ->method('updateEntityWithFields')
            ->with(
                $this->equalTo($student),
                $this->callback(function($data) use ($name, $email) {
                    return $data['name'] === $name &&
                           $data['email'] === $email;
                })
            )
            ->willReturn($updatedStudent);

        $result = $this->studentService->editStudent($id, $name, $email);
        $this->assertSame($updatedStudent, $result);
    }

    public function testDeleteStudent(): void
    {
        $id = 1;
        $student = new Student();
        $student->setId($id);
        $student->setName('John Doe');
        $student->setEmail('john.doe@example.com');
        $user = new User();
        $student->setUser($user);

        $this->entityService
            ->method('find')
            ->with(Student::class, $id)
            ->willReturn($student);

        // Verify that deleteEntity is called twice, once for User and once for Student
        $this->entityService
            ->expects($this->exactly(2))
            ->method('deleteEntity')
            ->withConsecutive(
                [$this->equalTo($user)],
                [$this->equalTo($student)]
            );

        $this->studentService->deleteStudent($id);
    }


}
