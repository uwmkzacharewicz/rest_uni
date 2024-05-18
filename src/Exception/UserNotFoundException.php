<?php

namespace App\Exception;

class UserNotFoundException extends \Exception
{
    protected $message = 'Nie znaleziono użytkownika.';
}
