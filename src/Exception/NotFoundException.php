<?php

namespace App\Exception;

class NotFoundException extends \Exception
{
    protected $message = 'Nie znaleziono.';
}
