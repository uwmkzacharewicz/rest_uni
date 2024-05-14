<?php
namespace App\Service;

use App\Entity\Student;
use App\Entity\User;
use App\Security\Role;
use App\Service\EntityService;
use App\Service\UtilityService;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


class StudentService
{
    private $entityManager;
    private $entityService;

    public function __construct(EntityManagerInterface $entityManager, EntityService $entityService)
    {
        $this->entityManager = $entityManager;
        $this->entityService = $entityService;
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
    public function addStudent(string $studentName, string $email): ?Student
    {
        // Tworzenie instancji Student
         // Dodanie użytkownika za pomocą EntityService
        $data = [
        'name' => $Studentname,
        'email' => $hashedPassword
        ];
        
        // Skorzystanie z EntityService do dodania nowej encji
        $savedStudent = $this->entityService->addEntity(Student::class, $data);

        return $savedStudent;
    }

    // edycja użytkownika
    public function editStudent(int $id, string $Studentname, string $password, array $roles): ?Student
    {
        $student = $this->findStudent($id);
        if (!$student) {
            throw new \Exception('Student not found');
        }
        $student->setStudentname($Studentname);
        $student->setPassword($this->passwordHasher->hashPassword($student, $password));
        $student->setRoles($roles);
        $this->entityService->updateEntity($student);

        return $student;
    }

    
    // AKtualizacja użytkownika
    public function updateStudentFields(int $id, array $data): Student
    {

        $Student = $this->entityService->find(Student::class, $id);
        if (!$Student) {
            throw new \Exception('Student not found');
        }

        if (isset($data['password'])) {
            $data['password'] = $this->passwordHasher->hashPassword($Student, $data['password']);
        }

        // jezeli zmieniamy role to sprawdzamy czy nowa rola jest poprawna
        if (isset($data['roles'])) {
            foreach ($data['roles'] as $role) {
                if (!in_array($role, Role::ROLES)) {
                    throw new \Exception('Nieznana rola użytkownika.');
                }
            }
        }

        return $this->entityService->updateEntityWithFields($Student, $data);         
    }


    // usunięcie użytkownika
    public function deleteStudent(int $id): void
    {
        $Student = $this->findStudent($id);
        if (!$Student) {
            throw new \Exception('Nie znaleziono użytkownika');
        } 

        $this->entityService->deleteEntity($Student);       
    }






}









?>