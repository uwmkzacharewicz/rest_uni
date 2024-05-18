<?php

namespace App\Service;

use App\Entity\Student;
use App\Entity\User;
use App\Entity\Course;
use App\Entity\Enrollment;
use App\Security\Role;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use App\Exception\CustomException;
use Exception;

class StudentService
{
    private $entityManager;
    private $entityService;
    private $userService;

    public function __construct(EntityManagerInterface $entityManager, EntityService $entityService, UserService $userService)
    {
        $this->entityManager = $entityManager;
        $this->entityService = $entityService;
        $this->userService = $userService;
    }

    public function findAllStudents(): array
    {
        return $this->entityService->findAll(Student::class);
    }

    public function findStudent(int $id): ?Student
    {
        return $this->entityService->find(Student::class, $id);
    }

    public function findStudentByField(string $field, $value): ?Student
    {
        return $this->entityService->findEntityByField(Student::class, $field, $value);
    }

    public function findStudentLogin(int $id): ?User
    {
        $student = $this->findStudent($id);
        if (!$student) {
            throw new \Exception('Student not found');
        }
        return $student->getUser();
    }

    public function createStudent(string $name, string $email, ?string $username = null, ?string $password = null): ?Student
    {
        $newUser = null;

        if ($username && $password) {
            $existingUser = $this->entityService->findEntityByField(User::class, 'username', $username);
            if ($existingUser) {
                throw CustomException::userAlreadyExists($username);
            }
            $newUser = $this->userService->addUser($username, $password, [Role::ROLE_STUDENT]);
        }

        $studentData = [
            'name' => $name,
            'email' => $email,
            'user' => $newUser
        ];

        return $this->entityService->addEntity(Student::class, $studentData);
    }

    public function findEnrolledCourses(int $studentId): array
    {
        $student = $this->findStudent($studentId);
        if (!$student) {
            throw new \Exception('Student not found');
        }

        $courses = [];
        foreach ($student->getEnrollments() as $enrollment) {
            $courses[] = $enrollment->getCourse();
        }

        return $courses;
    }

    public function findActiveCoursesWithFreeSpots(): array
    {
        $courses = $this->entityService->findAll(Course::class);
        return array_filter($courses, function ($course) {
            return $course->isActive() && count($course->getEnrollments()) < $course->getCapacity();
        });
    }

    public function findEnrollment(int $studentId, int $courseId): ?Enrollment
    {
        return $this->entityService->findEntityByFields(Enrollment::class, [
            'student' => $studentId,
            'course' => $courseId
        ]);
    }

    public function editStudent(int $id, string $name, string $email): Student
    {
        $student = $this->findStudent($id);
        if (!$student) {
            throw CustomException::studentNotFound($id);
        }

        $studentData = [
            'name' => $name,
            'email' => $email
        ];

        return $this->entityService->updateEntityWithFields($student, $studentData);
    }

    public function updateStudentFields(int $id, array $data): Student
    {
        $student = $this->findStudent($id);
        if (!$student) {
            throw CustomException::studentNotFound($id);
        }

        return $this->entityService->updateEntityWithFields($student, $data);
    }

    public function deleteStudent(int $id): void
    {
        $student = $this->findStudent($id);
        if (!$student) {
            throw CustomException::studentNotFound($id);
        }

        $user = $student->getUser();
        if ($user) {
            $this->entityService->deleteEntity($user);
        }

        $this->entityService->deleteEntity($student);
    }
}
?>
