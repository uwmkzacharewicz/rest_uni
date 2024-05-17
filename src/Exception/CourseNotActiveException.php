<?php

namespace App\Exception;

class CourseNotActiveException extends \Exception
{
    protected $message = 'Kurs jest nieaktywny';
}