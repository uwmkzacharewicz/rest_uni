<?php

namespace App\Exception;

class CourseNotFoundException extends \Exception
{
    protected $message = 'Nie znaleziono kursu';
}
