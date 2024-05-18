<?php

namespace App\Exception;

class RoleNotFoundException extends \Exception
{
    protected $message = 'Nie znana rola użytkownika.';
}
