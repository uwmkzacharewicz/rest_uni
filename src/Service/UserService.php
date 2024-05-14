<?php
namespace App\Service;

use App\Entity\User;
use App\Security\Role;
use App\Service\EntityService;
use App\Service\UtilityService;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserService
{
    private $entityManager;
    private $entityService;
    private $passwordHasher;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher, EntityService $entityService)
    {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
        $this->entityService = $entityService;
    }

    // pobranie wszystkich użytkowników
    /**
     * @return User[]
     */
    public function findAllUsers(): array
    {
        return $this->entityService->findAll(User::class);
    }

    // pobranie użytkownika po id
    /**
     * @return User|null
     */
    public function findUser(int $id): ?User
    {
        return $this->entityService->find(User::class, $id);
        //return $this->entityManager->getRepository(User::class)->find($id);
    }

    // pobieranie użytkownika po nazwie użytkownika
    /**
     * @return User|null
     */
    public function findUserByUsername(string $username): ?User
    {
        return $this->entityService->findEntityByFiled(User::class, 'username', $username);
       // return $this->entityManager->getRepository(User::class)->findOneBy(['username' => $username]);        
    }

    // dodanie nowego użytkownika
    public function addUser(string $username, string $password, array $roles): ?User
    {
        // Tworzenie instancji User
        $newUser = new User();
        $newUser->setUsername($username);
        $newUser->setRoles($roles);

        // Hashowanie hasła
        $hashedPassword = $this->passwordHasher->hashPassword($newUser, $password);
        $newUser->setPassword($hashedPassword);

        // Dodanie użytkownika za pomocą EntityService
        $data = [
            'username' => $username,
            'password' => $hashedPassword,
            'roles' => $roles
        ];

        // Skorzystanie z EntityService do dodania nowej encji
        $savedUser = $this->entityService->addEntity(User::class, $data);

        return $savedUser;
    }

    // edycja użytkownika
    public function editUser(int $id, string $username, string $password, array $roles): ?User
    {
        $user = $this->findUser($id);
        if (!$user) {
            throw new \Exception('User not found');
        }
        $user->setUsername($username);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $user->setRoles($roles);
        $this->entityService->updateEntity($user);

        return $user;
    }

    
    // AKtualizacja użytkownika
    public function updateUserFields(int $id, array $data): User
    {

        $user = $this->entityService->find(User::class, $id);
        if (!$user) {
            throw new \Exception('User not found');
        }

        if (isset($data['password'])) {
            $data['password'] = $this->passwordHasher->hashPassword($user, $data['password']);
        }

        // jezeli zmieniamy role to sprawdzamy czy nowa rola jest poprawna
        if (isset($data['roles'])) {
            foreach ($data['roles'] as $role) {
                if (!in_array($role, Role::ROLES)) {
                    throw new \Exception('Nieznana rola użytkownika.');
                }
            }
        }

        return $this->entityService->updateEntityWithFields($user, $data);         
    }


    // usunięcie użytkownika
    public function deleteUser(int $id): void
    {
        $user = $this->findUser($id);
        if (!$user) {
            throw new \Exception('Nie znaleziono użytkownika');
        } 

        $this->entityService->deleteEntity($user);       
    }






}









?>