<?php namespace Komparu\PhpClient\Exceptions;

class NotFoundException extends BaseException {

    protected $message = 'The requested resource is not found';
}