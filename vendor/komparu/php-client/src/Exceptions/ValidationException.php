<?php

namespace Komparu\PhpClient\Exceptions;

use Exception;

class ValidationException extends Exception
{
    protected $errors = [];

    protected $message = 'The request parameters are invalid';

    /**
     * @param array $errors
     */
    public function setErrors(Array $errors)
    {
        $this->errors = $errors;
    }

    /**
     * @return mixed
     */
    public function getErrors()
    {
        return $this->errors;
    }
}