<?php namespace Komparu\PhpClient\Exceptions;

class RequestTimeoutException extends BaseException {

    protected $message = 'The request timed out';
}