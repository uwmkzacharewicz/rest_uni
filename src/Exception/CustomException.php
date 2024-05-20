<?php

namespace App\Exception;

use Exception;

class CustomException extends Exception
{
    protected int $statusCode;

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public static function invalidCredentials(): self
    {
        $e = new self("Nieprawidłowe dane logowania.");
        $e->statusCode = 400;
        return $e;
    }

    public static function accessDenied(): self
    {
        $e = new self("Nie masz uprawnień do dokonania zmian na tym zasobie.");
        $e->statusCode = 403;
        return $e;
    }

    public static function invalidRole(): self
    {
        $e = new self("Nieprawidłowa rola użytkownika.");
        $e->statusCode = 400;
        return $e;
    }

    public static function invalidPassword(): self
    {
        $e = new self("Nieprawidłowe hasło.");
        $e->statusCode = 400;
        return $e;
    }

    public static function invalidUsername(): self
    {
        $e = new self("Nieprawidłowa nazwa użytkownika.");
        $e->statusCode = 400;
        return $e;
    }

    public static function missingData(): self
    {
        $e = new self("Nie przekazano wymaganych danych.");
        $e->statusCode = 400;
        return $e;
    }

    public static function userNotFound(int $id): self
    {
        $e = new self("Użytkownik o id $id nie istnieje.");
        $e->statusCode = 404;
        return $e;
    }

    public static function roleNotFound(): self
    {
        $e = new self("Nieznana rola użytkownika.");
        $e->statusCode = 404;
        return $e;
    }

    public static function studentNotFound(int $id): self
    {
        $e = new self("Student o id $id nie istnieje.");
        $e->statusCode = 404;
        return $e;
    }

    public static function teacherNotFound(int $id): self
    {
        $e = new self("Nauczyciel o id $id nie istnieje.");
        $e->statusCode = 404;
        return $e;
    }

    public static function courseNotFound(int $id): self
    {
        $e = new self("Kurs o id $id nie istnieje.");
        $e->statusCode = 404;
        return $e;
    }

    public static function enrollmentNotFound(int $id): self
    {
        $e = new self("Zapis o id $id nie istnieje.");
        $e->statusCode = 404;
        return $e;
    }

    public static function enrollmentByStudentAndCourseNotFound(int $studentId, int $courseId): self
    {
        $e = new self("Zapis studenta $studentId na kurs $courseId nie istnieje.");
        $e->statusCode = 404;
        return $e;
    }

    public static function userAlreadyExists(string $username): self
    {
        $e = new self("Użytkownik o nazwie $username już istnieje.");
        $e->statusCode = 409;
        return $e;
    }

    public static function studentAlreadyExists(string $name): self
    {
        $e = new self("Student o nazwie $name już istnieje.");
        $e->statusCode = 409;
        return $e;
    }

    public static function teacherAlreadyExists(string $name): self
    {
        $e = new self("Nauczyciel o nazwie $name już istnieje.");
        $e->statusCode = 409;
        return $e;
    }

    public static function courseAlreadyExists(string $name): self
    {
        $e = new self("Kurs o nazwie $name już istnieje.");
        $e->statusCode = 409;
        return $e;
    }

    public static function studentAlreadyEnrolled(int $studentId, int $courseId): self
    {
        $e = new self("Student $studentId jest już zapisany na kurs $courseId.");
        $e->statusCode = 409;
        return $e;
    }

    public static function courseNotActive(int $courseId): self
    {
        $e = new self("Kurs {$courseId} jest nieaktywny.");
        $e->statusCode = 400;
        return $e;
    }

    public static function courseFull(int $courseId): self
    {
        $e = new self("Brak miejsc na kursie o id $courseId.");
        $e->statusCode = 400;
        return $e;
    }

    public static function databaseError(string $message): self
    {
        $e = new self("Błąd bazy danych: $message");
        $e->statusCode = 500;
        return $e;
    }

    public static function applicationError(string $message): self
    {
        $e = new self("Błąd aplikacji: $message");
        $e->statusCode = 500;
        return $e;
    }


    public static function invalidCourseCapacity(): self
    {
        $e = new self("Nieprawidłowa pojemność kursu.");
        $e->statusCode = 400;
        return $e;
    }
}

?>