<?php
/**
 * Created by PhpStorm.
 * User: kostya
 * Date: 20/05/16
 * Time: 11:51
 */

namespace Komparu\PhpClient\Cache;

use GuzzleHttp\Message\RequestInterface;

class NoCache extends AbstractCache
{
    /**
     * @param string $key
     * @param mixed $response
     * @param array $tags
     *
     * @return mixed
     */
    public function remember($key, $response, $tags = [])
    {
        return $response;
    }

    /**
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return $default;
    }

    public function getTags(RequestInterface $request)
    {
        return [];
    }
}