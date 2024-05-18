<?php

namespace App\Exception;

class TeacherNotFoundException extends \Exception
{
    protected $message = 'Nie znaleziono nauczyciela.';
}
