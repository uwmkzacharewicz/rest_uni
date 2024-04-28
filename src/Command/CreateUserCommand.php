<?php

namespace App\Command;

use App\Entity\User;
use App\Security\Role;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;

class CreateUserCommand extends Command
{
    protected static $defaultName = 'app:create-user';

    private $passwordHasher;
    private $entityManager;

    public function __construct(UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->passwordHasher = $passwordHasher;
        $this->entityManager = $entityManager;
    }
    
    /**
    * @return void
    */
    protected function configure()
    {
        $this
            ->setDescription('Creates a new user with a role.')
            ->addArgument('username', InputArgument::OPTIONAL, 'The username of the user');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $username = $input->getArgument('username');
        $helper = $this->getHelper('question');

        if (null === $username) {
            // Zapytaj użytkownika o nazwę użytkownika
            $usernameQuestion = new Question('Please enter the username:');
            $username = $helper->ask($input, $output, $usernameQuestion);
        }

        // Zmienna do śledzenia, czy hasła się zgadzają
        $passwordsAreEqual = false;

        // Pętla do...while do ponownego wpisywania hasła, jeśli się nie zgadzają
        do {
            // Zapytaj o hasło
            $passwordQuestion = new Question('Please enter the password:');
            $passwordQuestion->setValidator(function ($value) {
                if (trim($value) === '') {
                    throw new \RuntimeException('The password cannot be empty');
                }
                return $value;
            });
            $passwordQuestion->setHidden(true);
            $passwordQuestion->setHiddenFallback(false);
            $passwordQuestion->setMaxAttempts(3);

            $password = $helper->ask($input, $output, $passwordQuestion);

            // Zapytaj ponownie o hasło dla weryfikacji
            $confirmPasswordQuestion = new Question('Please re-enter the password for verification:');
            $confirmPasswordQuestion->setHidden(true);
            $confirmPasswordQuestion->setHiddenFallback(false);
            $confirmPasswordQuestion->setMaxAttempts(3);

            $passwordRepeat = $helper->ask($input, $output, $confirmPasswordQuestion);

            // Sprawdź, czy hasła są takie same
            if ($password === $passwordRepeat) {
                $passwordsAreEqual = true;
            } else {
                $output->writeln('The passwords do not match. Please try again.');
            }
        } while (!$passwordsAreEqual);


        // Utwórz użytkownika i hashowanie hasła
        $user = new User();
        $user->setUsername($username);
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        // Zapytaj o rolę
        $roleQuestion = new ChoiceQuestion(
            'Please select the role of the user (defaults to ROLE_USER)',
            [Role::ROLE_USER, Role::ROLE_STUDENT, Role::ROLE_TEACHER, Role::ROLE_ADMIN], // Opcje do wyboru
            0 // Domyślny indeks opcji
        );
        $roleQuestion->setErrorMessage('Role %s is invalid.');
        $role = $helper->ask($input, $output, $roleQuestion);
        $user->setRoles([$role]);

        // Zapisz użytkownika do bazy danych
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $output->writeln('User [' . $username . '] successfully created with the role [' . $role . ']');

        return Command::SUCCESS;
    }
}