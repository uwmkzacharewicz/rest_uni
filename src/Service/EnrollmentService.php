<?php

namespace App\Service;

use App\Entity\Enrollment;
use App\Entity\Student;
use App\Entity\Course;

class EnrollmentService
{
    private $entityService;

    public function __construct(EntityService $entityService)
    {
        $this->entityService = $entityService;
    }

    // Pokaż wszystkie zapisy
    /**
     * @return Enrollment[]
     */
    public function findAllEnrollments(): array
    {
        return $this->entityService->findAll(Enrollment::class);
    }

    // Pokaż zapis po id
    /**
     * @return Enrollment|null
     */
    public function findEnrollment(int $id): ?Enrollment
    {
        return $this->entityService->find(Enrollment::class, $id);
    }

    // Pokaż zapis po id studenta
    /**
     * @return Enrollment|null
     */
    public function findEnrollmentByStudent(int $studentId): ?Enrollment
    {
        return $this->entityService->findEntityByFiled(Enrollment::class, 'student', $studentId);
    }

    // Pokaż zapis po id kursu
    /**
     * @return Enrollment|null
     */
    public function findEnrollmentByCourse(int $courseId): ?Enrollment
    {
        return $this->entityService->findEntityByFiled(Enrollment::class, 'course', $courseId);
    }

    
    // Dodaj zapis
    /**
     * @return Enrollment|null
     */   
    public function addEnrollment(int $studentId, int $courseId): Enrollment
    {
        $student = $this->entityService->find(Student::class, $studentId);
        $course = $this->entityService->find(Course::class, $courseId);

        if (!$student) {
            throw new \Exception('Student not found');
        }

        if (!$course) {
            throw new \Exception('Course not found');
        }

        $enrollment = new Enrollment();
        $enrollment->setStudent($student);
        $enrollment->setCourse($course);

        return $this->entityService->addEntity($enrollment);
    }

    // edytuj zapis
    public function editEnrollment(int $id, ?string $grade): Enrollment
    {
        $enrollment = $this->findEnrollment($id);
        if (!$enrollment) {
            throw new \Exception('Enrollment not found');
        }

        if ($grade !== null) {
            $enrollment->setGrade($grade);
        }

        return $this->entityService->updateEntity($enrollment);
    }

    // Usuń zapis
        public function deleteEnrollment(int $id): void
    {
        $enrollment = $this->findEnrollment($id);
        if (!$enrollment) {
            throw new \Exception('Enrollment not found');
        }

        $this->entityService->deleteEntity($enrollment);
    }

    public function updateEnrollment(int $id, ?string $grade): Enrollment
    {
        $enrollment = $this->findEnrollment($id);
        if (!$enrollment) {
            throw new \Exception('Enrollment not found');
        }

        if ($grade !== null) {
            $enrollment->setGrade($grade);
        }

        return $this->entityService->updateEntity($enrollment);
    }
}


