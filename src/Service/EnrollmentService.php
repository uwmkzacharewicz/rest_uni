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

use App\Exception\CustomException;
use Exception;

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

    // Pokaż zapis po id studenta i od kursu
    /**
     * @return Enrollment|null
     */
    public function findEnrollmentByStudentAndCourse(int $studentId, int $courseId): ?Enrollment
    {
        $enrollment = $this->entityService->findEntityByFields(Enrollment::class, [
            'student' => $studentId,
            'course' => $courseId
        ]);

        if (!$enrollment) {
            throw CustomException::enrollmentByStudentAndCourseNotFound($studentId, $courseId);
        }
        
        return $enrollment;
    }

    // Pokaz listę studentów na danym kursie
    /**
     * @return Student[]
     */
    public function findStudentsByCourse(int $courseId): array
    {
        $enrollments = $this->findEnrollmentsByCourse($courseId);

        $students = [];
        foreach ($enrollments as $enrollment) {
            $students[] = $enrollment->getStudent();
        }

        return $students;
    }

    
    // Dodaj zapis
    /**
     * @return Enrollment|null
     */   
    public function createEnrollment(int $studentId, int $courseId): Enrollment
    {
        $this->validateEnrollment($studentId, $courseId);
    
        $enrollmentData = [
            'student' => $this->entityService->find(Student::class, $studentId),
            'course' => $this->entityService->find(Course::class, $courseId)
        ];
    
        $savedEnrollment = $this->entityService->addEntity(Enrollment::class, $enrollmentData);
    
        return $savedEnrollment;
    }

    // edytuj zapis
    public function editEnrollment(int $id, int $studentId, int $courseId, ?string $grade): Enrollment
    {
        $this->validateEnrollment($studentId, $courseId);

        $enrollment = $this->findEnrollment($id);
        if (!$enrollment) {
            throw CustomException::enrollmentNotFound($id);
        }

        $student = $this->entityService->find(Student::class, $studentId);
        $course = $this->entityService->find(Course::class, $courseId);

        $enrollmentData = [
            'student' => $student,
            'course' => $course,
            'grade' => $grade ?? null
        ];

        return $this->entityService->updateEntityWithFields($enrollment, $enrollmentData);
    }

    // Aktualizacja zapisu
    public function updateEnrollment(int $id, array $data): Enrollment
    {
        $enrollment = $this->findEnrollment($id);
        if (!$enrollment) {
            throw CustomException::enrollmentNotFound($id);
        }

        $studentId = $data['studentId'] ?? $enrollment->getStudent()->getId();
        $courseId = $data['courseId'] ?? $enrollment->getCourse()->getId();

        $this->validateEnrollment($studentId, $courseId);

        if (isset($data['grade'])) {
            $enrollment->setGrade($data['grade']);
        }

        $data['student'] = $this->entityService->find(Student::class, $studentId);
        $data['course'] = $this->entityService->find(Course::class, $courseId);

        unset($data['studentId'], $data['courseId']);

        return $this->entityService->updateEntityWithFields($enrollment, $data);
    }

    // Wystawienie oceny
    public function gradeEnrollment(int $id, string $grade): Enrollment
    {
        $enrollment = $this->findEnrollment($id);
        if (!$enrollment) {
            throw CustomException::enrollmentNotFound($id);
        }

        $enrollment->setGrade($grade);

        $this->entityService->setFieldValue($enrollment, 'grade', $grade);

        return $enrollment;
    }


    // Usuń zapis
    public function deleteEnrollment(int $id): void
    {
        $enrollment = $this->findEnrollment($id);
        if (!$enrollment) {
            throw CustomException::enrollmentNotFound($id);
        }

        $this->entityService->deleteEntity($enrollment);
    }



    private function validateEnrollment(int $studentId, int $courseId): void
    {
        $student = $this->entityService->find(Student::class, $studentId);
        $course = $this->entityService->find(Course::class, $courseId);

        if (!$student) {
            throw CustomException::studentNotFound($studentId);
        }

        if (!$course) {
            throw CustomException::courseNotFound($courseId);
        }

        // Sprawdzenie, czy student jest już zapisany na ten kurs
        $existingEnrollment = $this->entityService->findEntityByFields(Enrollment::class, [
            'student' => $studentId,
            'course' => $courseId
        ]);

        if ($existingEnrollment) {
            throw CustomException::studentAlreadyEnrolled($studentId, $courseId);
        }

        // Sprawdzenie czy kurs jest aktywny
        if (!$course->isActive()) {
            throw CustomException::courseNotActive($courseId);
        }

        // Sprawdzenie czy na kursie jest jeszcze miejsce
        $enrollments = $this->findEnrollmentsByCourse($courseId);
        if (count($enrollments) >= $course->getCapacity()) {
            throw CustomException::courseNotActive($courseId);
        }
    }

    
}


