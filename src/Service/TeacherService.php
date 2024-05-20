<?php
namespace App\Service;

use App\Entity\Teacher;
use App\Entity\Course;
use App\Entity\Enrollment;
use App\Entity\User;
use App\Security\Role;
use App\Service\EntityService;
use App\Service\UserService;
use App\Service\UtilityService;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use App\Exception\CustomException;
use Exception;

class TeacherService
{
    private $entityManager;
    private $entityService;

    public function __construct(EntityManagerInterface $entityManager, EntityService $entityService, UserService $userService)
    {
        $this->entityManager = $entityManager;
        $this->entityService = $entityService;
        $this->userService = $userService;
    }

    // pobranie wszystkich nauczycieli
    /**
     * @return Teacher[]
     */
    public function findAllTeachers(): array
    {
        return $this->entityService->findAll(Teacher::class);
    }

    // pobranie nauczyciela po id
    /**
     * @return Teacher|null
     */
    public function findTeacher(int $id): ?Teacher
    {
        return $this->entityService->find(Teacher::class, $id);
    }

    // pobieranie nauczyciela po nazwie
    /**
     * @return Teacher|null
     */
    public function findTeacherByTeachername(string $TeacherName): ?Teacher
    {
        return $this->entityService->findEntityByField(Teacher::class, 'Teachername', $TeacherName);       
    }

    // pobieranie Teachera po emailu
    /**
     * @return Teacher|null
     */
    public function findTeacherByEmail(string $email): ?Teacher
    {
        return $this->entityService->findEntityByField(Teacher::class, 'email', $email);       
    }

    // Znajdź nauczyciela po użytkowniku
    public function findTeacherByUser(User $user): ?Teacher
    {
        return $this->entityService->findEntityByField(Teacher::class, 'user', $user);       
    }

    // Pobieranie loginu Teachera
    public function findTeacherLogin(int $id): ?User
    {
        $Teacher = $this->findTeacher($id);
        if (!$Teacher) {
            throw CustomException::teacherNotFound($id);
        }
        return $Teacher->getUser();
    }

    // dodanie nowego Teachera
    public function createTeacher(string $TeacherName, string $email, string $specialization ,string $username, string $password): ?Teacher
    {
         // Sprawdź, czy użytkownik o danym username już istnieje
         $existingUser = $this->entityService->findEntityByFiled(User::class, 'username', $username);
         if ($existingUser) {
            throw CustomException::userAlreadyExists($id);
         }
 
        // Tworzenie instancji User
        $newUser = $this->userService->addUser($username, $password, [Role::ROLE_TEACHER]);
        // Dodanie użytkownika za pomocą EntityService
        $userData = [
            'username' => $newUser->getUsername(),
            'password' => $newUser->getPassword(),
            'roles' => $newUser->getRoles()
        ];

        // Tworzenie instancji Teacher i przypisanie użytkownika
        $TeacherData = [
            'name' => $TeacherName,
            'email' => $email,
            'specialization' => $specialization,
            'user' => $newUser
        ];
        $savedTeacher = $this->entityService->addEntity(Teacher::class, $TeacherData);

        return $savedTeacher;
    }


    // Znajdz kursy prowadzone przez nauczyciela
    public function findCoursesByTeacher(int $teacherId): array
    {
        $teacher = $this->findTeacher($teacherId);
        if (!$teacher) {
            throw CustomException::teacherNotFound($id);
        }

        return $teacher->getCourses()->toArray();
    }

    // Znajdz zapisy, gdzie nauczyciel może wystawić ocenę
    public function findEnrollmentsToGrade(int $teacherId): array
    {
        $courses = $this->findCoursesByTeacher($teacherId);
        $enrollmentsToBeGranted = [];


        foreach ($courses as $course) {
            foreach ($course->getEnrollments() as $enrollment) {
                if (!$enrollment->getGrade()) {
                    $enrollmentsToBeGranted[] = $enrollment;
                }

            }
        }

        return $enrollmentsToBeGranted;
    }

    // edycja użytkownika
    public function editTeacher(int $id, string $teacherName, string $email): ?Teacher
    {
        $teacher = $this->findTeacher($id);
        if (!$teacher) {
            throw CustomException::teacherNotFound($id);
        }

        $teacherData = [
            'name' => $teacherName,
            'email' => $email,
        ];

        $teacher = $this->entityService->updateEntityWithFields($teacher, $teacherData);

        return $teacher;
    }

    
    // AKtualizacja 
    public function updateTeacherFields(int $id, array $data): Teacher
    {

        //$teacher = $this->entityService->find(Teacher::class, $id);
        $teacher = $this->findTeacher($id);
        if (!$teacher) {
            throw CustomException::teacherNotFound($id);
        }

        return $this->entityService->updateEntityWithFields($teacher, $data);         
    }


     // usunięcie użytkownika
     public function deleteTeacher(int $id): void
     {
         $teacher = $this->findTeacher($id);
         if (!$teacher) {
            throw CustomException::teacherNotFound($id);
         }
 
         $user = $teacher->getUser();       
         
         // Usuń referencję do użytkownika w obiekcie nauczyciela
        if ($user) {
            $teacher->setUser(null);
            $this->entityManager->persist($teacher);
            $this->entityManager->flush(); // Zapisz zmiany, aby usunięcie referencji miało efekt
        }

        $this->entityService->deleteEntity($teacher);

         
         if ($user) {
             $this->entityService->deleteEntity($user);
         }
        
     }

}
