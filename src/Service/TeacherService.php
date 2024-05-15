<?php
namespace App\Service;

use App\Entity\Teacher;
use App\Entity\User;
use App\Security\Role;
use App\Service\EntityService;
use App\Service\UserService;
use App\Service\UtilityService;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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
        return $this->entityService->findEntityByFiled(Teacher::class, 'Teachername', $TeacherName);       
    }

    // pobieranie Teachera po emailu
    /**
     * @return Teacher|null
     */
    public function findTeacherByEmail(string $email): ?Teacher
    {
        return $this->entityService->findEntityByFiled(Teacher::class, 'email', $email);       
    }

    // Pobieranie loginu Teachera
    public function findTeacherLogin(int $id): ?User
    {
        $Teacher = $this->findTeacher($id);
        if (!$Teacher) {
            throw new \Exception('Teacher not found');
        }
        return $Teacher->getUser();
    }

    // dodanie nowego Teachera
    public function createTeacherWithPassword(string $TeacherName, string $email, string $username, string $password): ?Teacher
    {
         // Sprawdź, czy użytkownik o danym username już istnieje
         $existingUser = $this->entityService->findEntityByFiled(User::class, 'username', $username);
         if ($existingUser) {
             return $this->json(['error' => "Użytkownik o nazwie {$username} już istnieje"], JsonResponse::HTTP_CONFLICT);
         }
 
        // Tworzenie instancji User

        $newUser = $this->userService->addUser($username, $password, [Role::ROLE_Teacher]);
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
            'user' => $newUser
        ];
        $savedTeacher = $this->entityService->addEntity(Teacher::class, $TeacherData);

        return $savedTeacher;
    }

    public function addTeacherWithoutPassword(string $TeacherName, string $email): ?Teacher
    {
        // Tworzenie instancji Teacher
         // Dodanie użytkownika za pomocą EntityService
         $TeacherData = [
            'name' => $TeacherName,
            'email' => $email,
        ];
        $savedTeacher = $this->entityService->addEntity(Teacher::class, $TeacherData);

        return $savedTeacher;
    }

    // edycja użytkownika
    public function editTeacher(int $id, string $TeacherName, string $email): ?Teacher
    {
        $Teacher = $this->findTeacher($id);
        if (!$Teacher) {
            throw new \Exception('Teacher not found');
        }
        $Teacher->setName($TeacherName);
        $Teacher->setEmail($email);
        $this->entityService->updateEntity($Teacher);

        return $Teacher;
    }

    
    // AKtualizacja użytkownika
    public function updateTeacherFields(int $id, array $data): Teacher
    {

        $Teacher = $this->entityService->find(Teacher::class, $id);
        if (!$Teacher) {
            throw new \Exception('Nie znaleziono Teachera.');
        }

        return $this->entityService->updateEntityWithFields($Teacher, $data);         
    }


    // usunięcie użytkownika
    public function deleteTeacher(int $id): void
    {
        $Teacher = $this->findTeacher($id);
        if (!$Teacher) {
            throw new \Exception('Nie znaleziono użytkownika');
        }

        $user = $Teacher->getUser();
        if ($user) {
            $this->entityService->delete($user);
        }

        $this->entityService->delete($Teacher);
    }

}
