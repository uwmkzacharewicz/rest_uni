<?php

namespace App\Service;

use App\Entity\Enrollment;
use App\Entity\Student;
use App\Entity\Course;

use App\Exception\StudentNotFoundException;
use App\Exception\CourseNotFoundException;
use App\Exception\CourseNotActiveException;
use App\Exception\CourseFullException;
use App\Exception\StudentAlreadyEnrolledException;


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
    public function findEnrollmentsByStudent(int $studentId): array
    {
        return $this->entityService->findEntitiesByField(Enrollment::class, 'student', $studentId);
    }

    // Pokaż zapis po id kursu
    /**
     * @return Enrollment|null
     */
    public function findEnrollmentsByCourse(int $courseId): array
    {
        return $this->entityService->findEntitiesByField(Enrollment::class, 'course', $courseId);
    }

    
    // Dodaj zapis
    /**
     * @return Enrollment|null
     */   
    public function createEnrollment(int $studentId, int $courseId): Enrollment
    {
        $student = $this->entityService->find(Student::class, $studentId);
        $course = $this->entityService->find(Course::class, $courseId);

        if (!$student) {
            throw new StudentNotFoundException("Nie znaleziono studenta o id {$studentId}");
        }

        if (!$course) {
            throw new CourseNotFoundException("Nie znaleziono kursu o id {$courseId}");
        }

        // Sprawdzenie, czy student jest już zapisany na ten kurs
        $existingEnrollment = $this->entityService->findEntityByFields(Enrollment::class, [
            'student' => $studentId,
            'course' => $courseId
        ]);

        if ($existingEnrollment) {
            throw new StudentAlreadyEnrolledException("Student {$studentId} jest już zapisany na kurs o id {$courseId}");
        }

        // Sprawdzenie czy kurs jest aktywny
        if (!$course->isActive()) {
            throw new CourseNotActiveException("Kurs o id {$courseId} jest nieaktywny");
        }

        // Sprawdzenie czy na kursie jest jeszcze miejsce
        $enrollments = $this->findEnrollmentsByCourse($courseId);
        if (count($enrollments) >= $course->getCapacity()) {
            throw new CourseFullException("Brak miejsc na kursie o id {$courseId}");
        }

        $enrollmentData = [
            'student' => $student,
            'course' => $course,
        ];

        $savedEnrollment = $this->entityService->addEntity(Enrollment::class, $enrollmentData);

        return $savedEnrollment;
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


