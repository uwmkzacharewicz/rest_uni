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

    public static function courseAlreadyExists(string $name): self
    {
        $e = new self("Kurs o nazwie $name już istnieje.");
        $e->statusCode = 409;
        return $e;
    }

    public static function databaseError(string $message): self
    {
        $e = new self("Błąd bazy danych: $message");
        $e->statusCode = JsonResponse::HTTP_INTERNAL_SERVER_ERROR;
        return $e;
    }

    public static function applicationError(string $message): self
    {
        $e = new self("Błąd aplikacji: $message");
        $e->statusCode = JsonResponse::HTTP_INTERNAL_SERVER_ERROR;
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