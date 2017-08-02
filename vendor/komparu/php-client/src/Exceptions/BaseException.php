<?php namespace Komparu\PhpClient\Exceptions;

use Exception;
use GuzzleHttp\Message\ResponseInterface;

class BaseException extends Exception
{
    /**
     * @var ResponseInterface
     */
    protected $response;

    /**
     * @param ResponseInterface $response
     */
    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
    }

    /**
     * @return ResponseInterface
     */
    public function getResponse()
    {
        return $this->response;
    }
}