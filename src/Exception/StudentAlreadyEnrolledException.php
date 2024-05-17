<?php

namespace App\Exception;

class StudentAlreadyEnrolledException extends \Exception
{
    protected $message = 'Student jest już zapisany na ten kurs';
}
