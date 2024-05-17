<?php

namespace App\Exception;

class StudentNotFoundException extends \Exception
{
    protected $message = 'Nie znaleziono studenta';
}
