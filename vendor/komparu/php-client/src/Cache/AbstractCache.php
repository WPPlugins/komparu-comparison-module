<?php namespace Komparu\PhpClient\Cache;


use GuzzleHttp\ClientInterface as Client;
use GuzzleHttp\Message\RequestInterface;

abstract class AbstractCache
{
    /**
     * @param Client $client
     */
    private $client;

    /**
     * @param RequestInterface $request
     * @param \Closure $fallback
     *
     * @param bool $cache
     *
     * @return mixed
     */
    final public function run(RequestInterface $request, \Closure $fallback, $cache = true)
    {
        if (!$cache) {
            return $fallback($this->getClient()->send($request), $request);
        }

        $key = $this->getRequestHashKey($request);
        if (!($data = $this->get($key))) {
            $data = $this->remember($key, $fallback($this->getClient()->send($request), $request), $this->getTags($request));
        }

        return $data;
    }

    /**
     * @param RequestInterface $request
     * @param null $default
     *
     * @return mixed
     */
    final public function peek(RequestInterface $request, $default = null)
    {
        return $this->get($this->getRequestHashKey($request), $default);
    }

    /**
     * @param RequestInterface $request
     * @param $value
     *
     * @return mixed
     */
    final public function save(RequestInterface $request, $value)
    {
        return $this->remember($this->getRequestHashKey($request), $value, $this->getTags($request));
    }

    /**
     * @param string $key
     * @param mixed $response
     * @param array $tags
     *
     * @return mixed
     */
    abstract public function remember($key, $response, $tags = []);

    /**
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    abstract public function get($key, $default = null);

    /**
     * @return Client
     */
    private function getClient()
    {
        return $this->client;
    }

    /**
     * @param Client $client
     *
     * @return $this
     */
    final public function setClient(Client $client)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * @param RequestInterface $request
     *
     * @return string
     */
    final private function getRequestHashKey(RequestInterface $request)
    {
        return sha1(''
                    . $request->getMethod()
                    . '~'
                    . $request->getScheme()
                    . '~'
                    . $request->getHost()
                    . '~'
                    . $request->getPath()
                    . '~'
                    . $request->getQuery()
        );
    }

    abstract public function getTags(RequestInterface $request);
}