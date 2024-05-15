<?php
namespace App\Service;

use App\Entity\Student;
use App\Entity\User;
use App\Security\Role;
use App\Service\EntityService;
use App\Service\UserService;
use App\Service\UtilityService;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


class StudentService
{
    private $entityManager;
    private $entityService;

    public function __construct(EntityManagerInterface $entityManager, EntityService $entityService, UserService $userService)
    {
        $this->entityManager = $entityManager;
        $this->entityService = $entityService;
        $this->userService = $userService;
    }

    // pobranie wszystkich użytkowników
    /**
     * @return Student[]
     */
    public function findAllStudents(): array
    {
        return $this->entityService->findAll(Student::class);
    }

    // pobranie studenta po id
    /**
     * @return Student|null
     */
    public function findStudent(int $id): ?Student
    {
        return $this->entityService->find(Student::class, $id);
    }

    // pobieranie studenta po nazwie użytkownika
    /**
     * @return Student|null
     */
    public function findStudentByStudentname(string $studentName): ?Student
    {
        return $this->entityService->findEntityByFiled(Student::class, 'Studentname', $studentName);       
    }

    // pobieranie studenta po emailu
    /**
     * @return Student|null
     */
    public function findStudentByEmail(string $email): ?Student
    {
        return $this->entityService->findEntityByFiled(Student::class, 'email', $email);       
    }

    // Pobieranie loginu studenta
    public function findStudentLogin(int $id): ?User
    {
        $student = $this->findStudent($id);
        if (!$student) {
            throw new \Exception('Student not found');
        }
        return $student->getUser();
    }

    // dodanie nowego studenta
    public function createStudentWithPassword(string $studentName, string $email, string $username, string $password): ?Student
    {
         // Sprawdź, czy użytkownik o danym username już istnieje
         $existingUser = $this->entityService->findEntityByFiled(User::class, 'username', $username);
         if ($existingUser) {
             return $this->json(['error' => "Użytkownik o nazwie {$username} już istnieje"], JsonResponse::HTTP_CONFLICT);
         }
 
        // Tworzenie instancji User

        $newUser = $this->userService->addUser($username, $password, [Role::ROLE_STUDENT]);
        // Dodanie użytkownika za pomocą EntityService
        $userData = [
            'username' => $newUser->getUsername(),
            'password' => $newUser->getPassword(),
            'roles' => $newUser->getRoles()
        ];

        // Tworzenie instancji Student i przypisanie użytkownika
        $studentData = [
            'name' => $studentName,
            'email' => $email,
            'user' => $newUser
        ];
        $savedStudent = $this->entityService->addEntity(Student::class, $studentData);

        return $savedStudent;
    }

    public function addStudentWithoutPassword(string $studentName, string $email): ?Student
    {
        // Tworzenie instancji Student
         // Dodanie użytkownika za pomocą EntityService
         $studentData = [
            'name' => $studentName,
            'email' => $email,
        ];
        $savedStudent = $this->entityService->addEntity(Student::class, $studentData);

        return $savedStudent;
    }

    // edycja użytkownika
    public function editStudent(int $id, string $studentName, string $email): ?Student
    {
        $student = $this->findStudent($id);
        if (!$student) {
            throw new \Exception('Student not found');
        }
        $student->setName($studentName);
        $student->setEmail($email);
        $this->entityService->updateEntity($student);

        return $student;
    }

    
    // AKtualizacja użytkownika
    public function updateStudentFields(int $id, array $data): Student
    {

        $Student = $this->entityService->find(Student::class, $id);
        if (!$Student) {
            throw new \Exception('Nie znaleziono studenta.');
        }

        return $this->entityService->updateEntityWithFields($Student, $data);         
    }


    // usunięcie użytkownika
    public function deleteStudent(int $id): void
    {
        $student = $this->findStudent($id);
        if (!$student) {
            throw new \Exception('Nie znaleziono użytkownika');
        }

        $user = $student->getUser();
        if ($user) {
            $this->entityService->delete($user);
        }

        $this->entityService->delete($student);
    }

}









?>