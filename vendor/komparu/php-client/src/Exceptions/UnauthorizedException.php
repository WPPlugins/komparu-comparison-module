<?php namespace Komparu\PhpClient\Exceptions;

class UnauthorizedException extends BaseException {

    protected $message = 'Not authorized to perform this request';

}