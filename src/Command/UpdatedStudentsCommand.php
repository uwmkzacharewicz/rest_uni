<?php

namespace App\Command;

use App\Entity\User;
use App\Entity\Student;
use App\Security\Role;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:update-students',
    description: 'Add login to students',
)]
class UpdatedStudentsCommand extends Command
{
    
    private $entityManager;
    private $passwordHasher;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
{
    $students = $this->entityManager->getRepository(Student::class)->findAll();

    foreach ($students as $student) {
        if (!$student->getUser()) { // Zakładamy, że Student ma pole User
            // Tworzenie nowego użytkownika dla studenta
            $user = new User();

            // Ustawienie nazwy użytkownika na podstawie e-maila przed @
            $emailParts = explode('@', $student->getEmail());
            $username = $emailParts[0];  // Używamy części przed @ jako username
            $user->setUsername($username);

            // Generowanie hasła
            $password = $this->generatePassword($student->getEmail(), $student->getId());
            $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
            $user->setPassword($hashedPassword);
            $user->setRoles(['ROLE_STUDENT']);
            $student->setUser($user);

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            // Wypisywanie szczegółowych informacji o studencie
            $output->writeln(sprintf(
                "Updated student: %s, ID: %d, User ID: %d, Username: %s, Password: %s",
                $student->getName(),
                $student->getId(),
                $user->getId(),
                $user->getUsername(),
                $password // Uwaga: w produkcji nigdy nie loguj haseł w czytelnej postaci!
            ));
        }
    }

    return Command::SUCCESS;
}
    private function generatePassword($email, $id): string
    {
        $nameParts = explode('@', $email)[0]; // Zakładamy, że nazwa użytkownika to część przed '@' w e-mailu
        $nameParts = preg_replace('/\d+/', '', $nameParts); // Usuwamy cyfry
        $firstName = substr($nameParts, 0, 3); // Pierwsze trzy litery imienia
        $lastName = substr($nameParts, -3); // Trzy ostatnie litery nazwiska (zakładając, że jest to część po kropce)
        return $firstName . $lastName . $id; // Łączymy wszystko z ID
    }
}