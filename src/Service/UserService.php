<?php
namespace App\Service;

use App\Entity\User;
use App\Security\Role;
use App\Service\UtilityService;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserService
{
    private $entityManager;
    private $passwordHasher;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher)
    {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    // pobranie wszystkich użytkowników
    /**
     * @return User[]
     */
    public function findAllUsers(): array
    {
        return $this->entityManager->getRepository(User::class)->findAll();
    }

    // pobranie użytkownika po id
    /**
     * @return User|null
     */
    public function findUser(int $id): ?User
    {
        return $this->entityManager->getRepository(User::class)->find($id);
    }

    // pobieranie użytkownika po nazwie użytkownika
    /**
     * @return User|null
     */
    public function findUserByUsername(string $username): ?User
    {
        return $this->entityManager->getRepository(User::class)->findOneBy(['username' => $username]);
    }


    // dodanie nowego użytkownika
    public function addUser(string $username, string $password, array $roles): ?User
    {
        // Rozpoczęcie transakcji
        $this->entityManager->beginTransaction();
        try {               
            // Sprawdzenie, czy użytkownik z daną nazwą użytkownika już istnieje
        $existingUser = $this->findUserByUsername($username);
        if ($existingUser) {
            throw new \Exception('Istnieje już użytkownik o podanym username.');
        }    
            // Tworzenie nowego użytkownika
            $newUser = new User();
            $newUser->setUsername($username);
            $hashedPassword = $this->passwordHasher->hashPassword($newUser, $password);
            $newUser->setPassword($hashedPassword);
            $newUser->setRoles($roles);
            $this->entityManager->persist($newUser);   
           
            // Zatwierdzenie transakcji
            $this->entityManager->flush();
            $this->entityManager->commit();
    
            return $newUser;

        } catch (UniqueConstraintViolationException $e) {
            // Obsługa specyficznych wyjątków związanych z naruszeniem ograniczeń
            $this->entityManager->rollback();
            throw new \Exception('Database error: ' . $e->getMessage());
        } catch (\Exception $e) {
            // Ogólna obsługa wyjątków
            $this->entityManager->rollback();
            throw new \Exception('Application error: ' . $e->getMessage());
        }
    }

    // edycja użytkownika
    public function editUser(int $id, string $username, string $password, array $roles): ?User
    {
        // Rozpoczęcie transakcji
        $this->entityManager->beginTransaction();
        try {               
            // Sprawdzenie, czy użytkownik istnieje
            $user = $this->findUser($id);
            if (!$user) {
                $this->entityManager->rollback(); // Wycofaj transakcję jeśli użytkownik nie istnieje
                throw new \Exception('Nie istnieje użytkownik o podanym id.');
            }    
            // Edycja użytkownika
            $user->setUsername($username);

            if ($password !== null) {
                $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
                $user->setPassword($hashedPassword);
            }
            $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
            $user->setPassword($hashedPassword);


            $user->setRoles($roles);

            $this->entityManager->persist($user);
            $this->entityManager->flush();
            $this->entityManager->commit();  // Zatwierdzenie transakcji

            return $user;
        } catch (\Exception $e) {
            $this->entityManager->rollback(); // Wycofaj transakcję w przypadku błędów
            throw new \Exception('Application error: ' . $e->getMessage());
        }
    }

    
    // AKtualizacja użytkownika
    public function updateUser(User $user): User
    {
          // Rozpoczęcie transakcji
          $this->entityManager->beginTransaction();
            try {               
                // Aktualizacja użytkownika
                $this->entityManager->persist($user);
                $this->entityManager->flush();
                $this->entityManager->commit();  // Zatwierdzenie transakcji
                return $user;
            } catch (\Exception $e) {
                $this->entityManager->rollback(); // Wycofaj transakcję w przypadku błędów
                throw new \Exception('Application error: ' . $e->getMessage());
            }     
    }


    // usunięcie użytkownika
    public function deleteUser(int $id): void
    {
        // Rozpoczęcie transakcji
        $this->entityManager->beginTransaction();
        try {               
            // Sprawdzenie, czy użytkownik istnieje
            $user = $this->findUser($id);
            if (!$user) {
                $this->entityManager->rollback(); // Wycofaj transakcję jeśli użytkownik nie istnieje
                throw new \Exception('Nie istnieje użytkownik o podanym id.');
            }    
            // Usunięcie użytkownika
            $this->entityManager->remove($user);
            $this->entityManager->flush();
            $this->entityManager->commit();  // Zatwierdzenie transakcji
        } catch (\Exception $e) {
            $this->entityManager->rollback(); // Wycofaj transakcję w przypadku błędów
            throw new \Exception('Application error: ' . $e->getMessage());
        }
    }






}









?>