<?php

namespace App\Exception;

class CourseFullException extends \Exception
{
    protected $message = 'Brak miejsc na kursie';
}
