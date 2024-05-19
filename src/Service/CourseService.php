<?php
namespace App\Service;

use App\Entity\Teacher;
use App\Entity\Student;
use App\Entity\Course;
use App\Service\EntityService;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\Exception\CustomException;
use Exception;


class CourseService
{

    private $entityService;

    public function __construct(EntityService $entityService)
    {
        $this->entityService = $entityService;
    }

    // pobranie wszystkich kursów
    /**
     * @return Course[]
     */
    public function findAllCourses(): array
    {
        return $this->entityService->findAll(Course::class);
    }

    // pobranie kursu po id
    /**
     * @return Course|null
     */
    public function findCourse(int $id): ?Course
    {
        return $this->entityService->find(Course::class, $id);
    }

    // pobieranie kursu po nazwie
    /**
     * @return Course|null
     */
    public function findCourseByTitle(string $title): ?Course
    {
        return $this->entityService->findEntityByFiled(Course::class, 'title', $title);       
    }

    // pobieranie kursu po nauczycielu
    /**
     * @return Course[]
     */
    public function findCoursesByTeacher(Teacher $teacher): array
    {
        return $this->entityService->findEntityByFiled(Course::class, 'teacher', $teacher);       
    }


    public function findCoursesByActive(bool $active): array
    {
        return $this->entityService->findEntitiesByField(Course::class, 'active', $active);
    }

    // pobieranie nauczyciela kursu
    public function findCourseTeacher(int $id): ?Teacher
    {
        $course = $this->findCourse($id);
        if (!$course) {
            throw CustomException::courseNotFound($id);
        }
        return $course->getTeacher();
    }

    // pobieranie wszystkich kursów nauczyciela
    /**
     * @return Course[]
     */
    public function findTeacherCourses(int $teacherId): array
    {
        $teacher = $this->entityService->find(Teacher::class, $teacherId);
        if (!$teacher) {
            throw CustomException::teacherNotFound($teacherId);
        }
        return $teacher->getCourses()->toArray();
    }

    public function findCoursesByTeacherId(int $teacherId): array
    {
        $teacher = $this->entityService->find(Teacher::class, $teacherId);
        if (!$teacher) {
            throw CustomException::teacherNotFound($teacherId);
        }

        return $this->entityService->findEntitiesByField(Course::class, 'teacher', $teacher);
    }

    // pobieranie wszystkich studentów danego kursu
    /**
     * @return Student[]
     */
    public function findCourseStudents(int $courseId): array
    {
        $course = $this->findCourse($courseId);
        if (!$course) {
            throw CustomException::courseNotFound($courseId);
        }
        $students = [];
        foreach ($course->getEnrollments() as $enrollment) {
            $students[] = $enrollment->getStudent();
        }
        return $students;
    }

    // pobieranie wszystkich kursów studenta
    /**
     * @return Course[]
     */
    public function findStudentCourses(int $studentId): array
    {
        $student = $this->findStudent($studentId);
        if (!$student) {
            throw CustomException::studentNotFound($studentId);
        }
        return $student->getCourses()->toArray();
    }

    // Znajdź studentów zapisanych na kurs
    public function findStudentsByCourse(int $courseId): array
    {
        $course = $this->findCourse($courseId);
        if (!$course) {
            throw CustomException::courseNotFound($id);
        }

        $students = [];
        foreach ($course->getEnrollments() as $enrollment) {
            $students[] = $enrollment->getStudent();
        }

        return $students;
    }


    // dodawanie kursu    
    public function createCourse(string $title, string $description, string $teacherId, string $capacity, string $active): ?Course
    {
        $teacher = $this->entityService->find(Teacher::class, $teacherId);
        if (!$teacher) {
            throw CustomException::teacherNotFound($teacherId);
        }

        $courseData = [
            'title' => $title,
            'description' => $description,
            'teacher' => $teacher,
            'capacity' => $capacity,
            'active' => $active
        ];

        $savedCourse = $this->entityService->addEntity(Course::class, $courseData);

        return $savedCourse;      
    }

    // edycja kursu
    public function editCourse(int $id, string $title, string $description, string $teacherId, string $capacity, string $active): ?Course
    {
        $course = $this->findCourse($id);
        if (!$course) {
            throw CustomException::courseNotFound($id);
        }        

       // Znajdź nauczyciela
        $teacher = $this->entityService->find(Teacher::class, $teacherId);
        if (!$teacher) {
            throw CustomException::teacherNotFound($teacherId);
        }

        $courseData = [
            'title' => $title,
            'description' => $description,
            'teacher' => $teacher,
            'capacity' => $capacity,
            'active' => $active
        ];

        $savedCourse = $this->entityService->updateEntityWithFields($course, $courseData);

        return $savedCourse;      
    }

    // Aktualizacja pol 
    public function updateCourseFields(int $id, array $data): Course
    {
        $course = $this->findCourse($id);
        if (!$course) {
            throw CustomException::courseNotFound($id);
        }

        // Obsłuż nauczyciela, jeśli jest przekazany w danych
        if (isset($data['teacherId'])) {
            $teacher = $this->entityService->find(Teacher::class, $data['teacherId']);
            if (!$teacher) {
                throw CustomException::teacherNotFound($data['teacherId']);
            }
            $data['teacher'] = $teacher;
            unset($data['teacherId']); // Usuiecie teacherId z danych, aby nie powodować błędów przy aktualizacji pól
        }

        $course = $this->entityService->updateEntityWithFields($course, $data);
        
        return $course;
    }

    // aktualizacja liczby miejsc
    public function updateCourseCapacity(int $id, int $capacity): Course
    {
        $course = $this->findCourse($id);
        if (!$course) {
            throw CustomException::courseNotFound($id);
        }

        $course->setCapacity($capacity);
        $this->entityService->updateEntity($course);

        return $course;
    }

    // aktualizacja statusu kursu
    public function updateCourseActive(int $id, bool $active): Course
    {
        $course = $this->findCourse($id);
        if (!$course) {
            throw CustomException::courseNotFound($id);
        }

        $course->setActive($active);
        $this->entityService->updateEntity($course);

        return $course;
    }

    // usunięcie kursu
    public function deleteCourse(int $id): void
    {
        $course = $this->findCourse($id);
        if (!$course) {
            throw CustomException::courseNotFound($id);
        }

        $this->entityService->deleteEntity($course);
    }
















}