<?php
namespace App\Service;

use App\Entity\Teacher;
use App\Entity\Student;
use App\Entity\Course;
use App\Service\EntityService;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use App\Exception\CourseNotFoundException;
use App\Exception\TeacherNotFoundException;
use App\Exception\StudentNotFoundException;


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
            throw new \Exception('Course not found');
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
            throw new \Exception('Teacher not found');
        }
        return $teacher->getCourses()->toArray();
    }

    public function findCoursesByTeacherId(int $teacherId): array
    {
        $teacher = $this->entityService->find(Teacher::class, $teacherId);
        if (!$teacher) {
            throw new \Exception("Brak nauczyciela o id: {$teacherId}");
        }

        return $this->entityService->findEntitiesByField(Course::class, 'teacher', $teacher);
    }

    // pobieranie wszystkich kursów studenta
    /**
     * @return Course[]
     */
    public function findStudentCourses(int $studentId): array
    {
        $student = $this->findStudent($studentId);
        if (!$student) {
            throw new \Exception('Student not found');
        }
        return $student->getCourses()->toArray();
    }

    // Znajdź studentów zapisanych na kurs
    public function findStudentsByCourse(int $courseId): array
    {
        $course = $this->findCourse($courseId);
        if (!$course) {
            throw new \Exception('Course not found');
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
            return $this->json(['error' => "Brak nauczyciela o id: {$teacherId}"], JsonResponse::HTTP_CONFLICT);
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
            throw new CourseNotFoundException("Brak kursu o id: {$id}");
        }        

       // Znajdź nauczyciela
        $teacher = $this->entityService->find(Teacher::class, $teacherId);
        if (!$teacher) {
            throw new TeacherNotFoundException("Brak nauczyciela o id: {$teacherId}");
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
            throw new CourseNotFoundException("Nie znaleziono kursu o id {$courseId}");
        }

        // Obsłuż nauczyciela, jeśli jest przekazany w danych
        if (isset($data['teacherId'])) {
            $teacher = $this->entityService->find(Teacher::class, $data['teacherId']);
        if (!$teacher) {
            throw new TeacherNotFoundException("Brak nauczyciela o id");
        }

        $data['teacher'] = $teacher;
        unset($data['teacherId']); // Usuń teacherId z danych, aby nie powodować błędów przy aktualizacji pól
        }

        $course = $this->entityService->updateEntityWithFields($course, $data);
        
        return $course;
    }

    // usunięcie kursu
    public function deleteCourse(int $id): void
    {
        $course = $this->findCourse($id);
        if (!$course) {
            throw new \Exception('Nie znaleziono kursu');
        }

        $this->entityService->deleteEntiy($course);
    }
















}