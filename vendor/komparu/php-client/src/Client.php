<?php

namespace Komparu\PhpClient;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Event\ErrorEvent;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Message\RequestInterface;
use GuzzleHttp\Message\ResponseInterface;
use GuzzleHttp\Event\BeforeEvent;
use GuzzleHttp\Event\CompleteEvent;
use Komparu\PhpClient\Cache\AbstractCache;
use Komparu\PhpClient\Cache\NoCache;
use Komparu\PhpClient\Exceptions\UnauthorizedException;
use Komparu\PhpClient\Exceptions\NotFoundException;
use Komparu\PhpClient\Exceptions\ValidationException;
use Komparu\PhpClient\Exceptions\RequestTimeoutException;
use Closure;

class Client
{
    const MAX_POOL_SIZE = 25;

    /**
     * @var \GuzzleHttp\ClientInterface
     */
    protected $client;

    /**
     * @var string
     */
    protected $url = 'http://api.komparu.com/v1';

    /**
     * @var string
     */
    protected $resource = '';

    /**
     * @var string
     */
    protected $defaultResource = '';

    /**
     * @var array
     */
    protected $params = [];

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var array
     */
    protected $queue = [];

    /**
     * @var string|null
     */
    protected $queueKey;

    /**
     * @var AbstractCache
     */
    private $cache;


    /**
     * @param ClientInterface $client
     * @param AbstractCache $cache
     */
    public function __construct(ClientInterface $client, AbstractCache $cache = null)
    {
        $this->cache = ! is_null($cache) ? $cache : new NoCache();
        $this->cache->setClient($client);

        $this->client = $client;
        $this->client->setDefaultOption('config', ['curl' => ['CURLOPT_HTTP_VERSION' => 1]]);
        $this->resource = $this->defaultResource;

        // First add the server domain. This can be overridden using
        // the domain() method.
        $this->client->getEmitter()->on('before', function (BeforeEvent $event) {
            $event->getRequest()->setHeader('X-Auth-Domain', $_SERVER['SERVER_NAME']);
        });

        // Reset the params and resource after each request
        /** @noinspection PhpUnusedParameterInspection */
        $this->client->getEmitter()->on('complete', function (CompleteEvent $event) {
            $this->params   = [];
            $this->resource = $this->defaultResource;
        });
    }

    /**
     * @param string $token
     *
     * @return $this
     */
    public function setToken($token)
    {
        $this->client->getEmitter()->on('before', function (BeforeEvent $event) use ($token) {
            $event->getRequest()->setHeader('X-Auth-Token', $token);
        });

        return $this;
    }

    /**
     * @param string $domain
     *
     * @return $this
     */
    public function setDomain($domain)
    {
        $this->client->getEmitter()->on('before', function (BeforeEvent $event) use ($domain) {
            $event->getRequest()->setHeader('X-Auth-Domain', $domain);
        });

        return $this;
    }

    /**
     * @param string $lang
     *
     * @return $this
     */
    public function setLanguage($lang)
    {
        $this->client->getEmitter()->on('before', function (BeforeEvent $event) use ($lang) {
            $event->getRequest()->setHeader('Accept-Language', $lang);
        });

        return $this;
    }

    /**
     * @param string $url
     *
     * @return $this
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @param string $resource
     *
     * @return $this
     */
    public function resource($resource)
    {
        $this->resource = $resource;

        return $this;
    }

    /**
     * @throws \Exception
     * @return string
     */
    protected function getResource()
    {
        if( ! $this->resource){
            throw new \Exception('Must provide a resource');
        }

        return $this->resource;
    }

    /**
     * @param string $username
     * @param string $password
     *
     * @return array
     */
    public function authenticate($username, $password)
    {
        $url             = rtrim($this->url, '/') . '/auth';
        $options['body'] = compact('username', 'password');

        return $this->send('post', $url, $options);
    }

    /**
     * Build a request and put it on the queue.
     *
     * @param string $name
     * @param Closure $callback
     *
     * @return $this
     */
    public function queue($name, Closure $callback)
    {
        $this->usingQueue($name);

        call_user_func($callback, $this);

        return $this;
    }

    /**
     * Set the name of the current queue key.
     * Reset the queue by providing null as a name.
     *
     * @param string|null
     *
     * @return $this
     */
    public function usingQueue($name)
    {
        $this->queueKey = $name;

        return $this;
    }

    /**
     * When in queue mode, reset to normal mode.
     * @return $this
     */
    public function resetQueue()
    {
        $this->queueKey = null;

        return $this;
    }

    /**
     * Check if the Client is in queue mode.
     * Use flush() to reset to normal mode.
     * @return bool
     */
    public function isUsingQueue()
    {
        return (bool) $this->queueKey;
    }

    /**
     * Shorthand for get and Queue
     *
     * @param array $queueIdId
     *
     * @return array
     */
    public function queueGet($queueIdId)
    {
        return $this->get([], $queueIdId);
    }

    /**
     * @param array $query
     * @param array|bool $queueIdId
     *
     * @param bool $cache defines if cache is enabled
     *
     * @return array
     * @throws \Exception
     */
    public function get(Array $query = [], $queueIdId = false, $cache = true)
    {
        $url              = rtrim($this->url, '/') . '/' . $this->getResource();
        $options['query'] = array_replace_recursive($this->params, $query);

        return $this->send('get', $url, $options, $queueIdId, $cache);
    }

    public function getSkipcache(Array $query = [], $queueIdId = false)
    {
        return $this->get($query, $queueIdId, false);

    }


    /**
     * @param int $id
     * @param array $query
     * @param array $queueId queue if defined
     *
     * @param bool $cache
     *
     * @return array
     */
    public function show($id, Array $query = [], $queueId = null, $cache = true)
    {
        $url              = rtrim($this->url, '/') . '/' . $this->getResource() . '/' . $id;
        $options['query'] = array_replace_recursive($this->params, $query);

        return $this->send('get', $url, $options, $queueId, $cache);
    }


    public function showSkipcache($id, Array $query = [], $queueIdId = false)
    {
        return $this->show($id, $query, $queueIdId, false);
    }

    /**
     * @param array $body
     * @param array $queueId queue if defined
     *
     * @param bool $cache
     *
     * @return array
     */
    public function store($body = [], $queueId = null, $cache = false)
    {
        $url             = rtrim($this->url, '/') . '/' . $this->getResource();
        $options['body'] = is_array($body)
            ? array_replace_recursive($this->params, $body)
            : $body;

        return $this->send('post', $url, $options, $queueId, $cache);
    }


    public function storeSkipcache(Array $query = [], $queueIdId = null)
    {
        return $this->store($query, $queueIdId, false);

    }

    /**
     * Add extra headers to a request.
     *
     * @param string $name
     * @param string $value
     * @return $this
     */
    public function header($name, $value)
    {
        $this->options['headers'][$name] = $value;

        return $this;
    }

    /**
     * @param int $id
     * @param array $body
     * @param array $queueId queue if defined
     *
     * @return array
     */
    public function update($id, $body = [], $queueId = null)
    {
        $url             = rtrim($this->url, '/') . '/' . $this->getResource() . '/' . $id;

        $options['body'] = is_array($body)
            ? array_replace_recursive($this->params, $body)
            : $body;

        return $this->send('put', $url, $options, $queueId);
    }

    /**
     * Insert or update a record, based on unique values (the 'where' clause).
     * Use the $getExistingId closure to determine an existing record and
     * return its ID in the closure.
     *
     * @param array $unique
     * @param array $body
     * @param Closure $getExistingId
     *
     * @return array
     */
    public function upsert(Array $unique, Array $body = [], Closure $getExistingId)
    {
        // Keep the old data
        $resource = $this->resource;
        $params   = $this->params;

        // Find items by these unique field data
        $result = $this->get($unique);

        // Get the identifier for an eventual update
        $id = call_user_func($getExistingId, $result);

        // Set the old data
        $this->resource = $resource;
        $this->params   = $params;

        return $id ? $this->update($id, $body) : $this->store($body);
    }

    /**
     * @param int $id
     * @param array $body
     * @param array $queueId queue if defined
     *
     * @return array
     */
    public function delete($id, Array $body = [], $queueId = null)
    {
        $url             = rtrim($this->url, '/') . '/' . $this->getResource() . '/' . $id;
        $options['body'] = array_replace_recursive($this->params, $body);

        return $this->send('delete', $url, $options, $queueId);
    }

    /**
     * @return array
     *
     * @param array $queueId queue if defined
     */
    public function options($queueId = null)
    {
        $url = rtrim($this->url, '/') . '/' . $this->getResource();

        return $this->send('options', $url, [], $queueId);
    }

    /**
     * @param int $id
     * @param array $body
     * @param array $queueId queue if defined
     *
     * @return array
     */
    public function copy($id, Array $body = [], $queueId = null)
    {
        $url              = rtrim($this->url, '/') . '/' . $this->getResource() . '/' . $id;
        $options['query'] = array_replace_recursive($this->params, $body);

        return $this->send('copy', $url, $options, $queueId);
    }

    /**
     * @param int $id
     * @param array $body
     * @param array $queueId queue if defined
     *
     * @return array
     */
    public function patch($id, Array $body = [], $queueId = null)
    {
        $url             = rtrim($this->url, '/') . '/' . $this->getResource() . '/' . $id;
        $options['body'] = array_replace_recursive($this->params, $body);

        return $this->send('patch', $url, $options, $queueId);
    }

    /**
     * @param array $bulk
     * @param array $queueId queue if defined
     *
     * @return array
     */
    public function bulk(Array $bulk, $queueId = null)
    {
        $url             = rtrim($this->url, '/') . '/' . $this->getResource() . '/_bulk';
        $body['bulk']    = $bulk;
        $options['body'] = array_replace_recursive($this->params, $body);

        return $this->send('post', $url, $options, $queueId);
    }

    /**
     * @param $method
     * @param $url
     * @param array $options
     * @param $queueId true means not firing but putting on queue
     *
     * @param bool $cache
     *
     * @return array
     * @throws \Exception
     */
    public function send($method, $url, $options = [], $queueId = null, $cache = true)
    {
        // Merge headers with the body
        $options = array_merge($options, $this->options);

        try{
            if( ! is_null($queueId)){
                $url .= '#' . $queueId;
            }
            $request = $this->client->createRequest($method, $url, $options);

            // Old way
            if($queueId){
                $this->queue[$queueId] = $request;
                $this->reset();

                return $this->queue;
            }

            // New way
            if($this->isUsingQueue()){
                $this->queue[$this->queueKey] = $request;
                $this->reset();

                return null;
            }

            return $this->cache->run($request, function (ResponseInterface $response, RequestInterface $request) {
                return [
                    'body'    => $this->handleResponse($response, $request),
                    'headers' => $response->getHeaders()
                ];
            }, $cache)['body'];

        }catch(BadResponseException $e){
            $this->handleResponse($e->getResponse(), $e->getRequest());
        }

        return null;
    }

    /**
     * TODO: proper error handling
     * @return array
     */
    public function flush()
    {
        $queue = array_reduce($this->queue, function ($carry, RequestInterface $req) {
            $cache = $this->cache->peek($req);
            if( ! is_null($cache)){
                $carry['rdy'][$this->getRequestKey($req)] = $cache;
            }else{
                $carry['new'][$this->getRequestKey($req)] = $req;
            }

            return $carry;
        }, ['rdy' => [], 'new' => []]);

        $this->client->sendAll(array_values($queue['new']), [
            // Call this function when each request completes
            'complete' => function (CompleteEvent $event) use (&$queue) {
                $queue['rdy'][$this->getRequestKey($event->getRequest())] = [
                    'body'    => $this->handleResponse($event->getResponse(), $event->getRequest()),
                    'headers' => $event->getResponse()->getHeaders()
                ];
                if($event->getRequest()->getMethod() === 'GET'){
                    $this->cache->save($event->getRequest(), $queue['rdy'][$this->getRequestKey($event->getRequest())]);
                }
            },
            // Call this function when a request encounters an error
            'error'    => function (ErrorEvent $event) use (&$queue) {
                try{
                    $queue['rdy'][$this->getRequestKey($event->getRequest())] = ['error' => $event->getException()->getResponse()->json()];
                }catch(\Exception $e){
                    $queue['rdy'][$this->getRequestKey($event->getRequest())] = ['error' => $event->getException()->getResponse()->getBody()];
                }

                // cache 422-response
                if($event->getRequest()->getMethod() === 'GET' and $event->getResponse()->getStatusCode() == 422){
                    $this->cache->save($event->getRequest(), $queue['rdy'][$this->getRequestKey($event->getRequest())]);
                }
            },
            // Maintain a maximum pool size of 25 concurrent requests.
            'parallel' => self::MAX_POOL_SIZE
        ]);

        // Reset the client to use it as a normal client instead of putting things on the queue.
        $this->resetQueue();

        return $queue['rdy'];
    }

    /**
     * Clear the current resource and params. For queue requests this
     * fixes the problem the earlier set params are removed nicely.
     * @return $this
     */
    public function reset()
    {
        $this->resource = null;
        $this->params   = [];
        $this->options = [];

        return $this;
    }

    private function getRequestKey(RequestInterface $request)
    {
        foreach($this->queue as $key => $val){
            if($request->getUrl() == $val->getUrl()){
                return $key;
            }
        }

        return null;
    }


    /**
     * @param ResponseInterface $response
     * @param RequestInterface $request
     * @param bool $throw
     *
     * @return array
     * @throws \Exception
     */
    protected function handleResponse(ResponseInterface $response, RequestInterface $request, $throw = true)
    {
        try{

            $this->reset();

            switch($response->getStatusCode()){

                case 401:
                    throw new UnauthorizedException($response);

                case 404:
                    throw new NotFoundException($response);

                case 408:
                    throw new RequestTimeoutException($response);

                case 422:
                    try{
                        $data = $response->json();
                    }catch(\RuntimeException $e){
                        $this->throwError($response, $request, null, $throw);
                        break;
                    }
                    $e = new ValidationException($data['message']);
                    $e->setErrors($data['errors']);
                    throw $e;

                case 200:

                    try{
                        $data = $response->json();
                    }catch(\RuntimeException $e){
                        $this->throwError($response, $request, null, $throw);

                        return false;
                    }

                    if(isset($data['code']) && is_numeric($data['code']) && isset($data['message'])){

                        switch($data['code']){
                            case 401:
                                throw new UnauthorizedException($response);

                            case 404:
                                throw new NotFoundException($response);

                            case 408:
                                throw new RequestTimeoutException($response);

                            case 422:
                                $e = new ValidationException($data['message']);
                                $e->setErrors($data['errors']);
                                throw $e;

                            default:
                                $this->throwError($response, $request, null, $throw);
                        }
                    }

                    return $response->json();

                case 204:
                    try{
                        $data = $response->json();
                    }catch(\RuntimeException $e){
                        $this->throwError($response, $request, null, $throw);
                        break;
                    }

                    return $data;

                default:
                    $this->throwError($response, $request, null, $throw);

                    return false;
            }
        }catch(\Exception $e){
            if($throw){
                throw $e;
            }else{
                return ['error' => $e->getMessage()];
            }
        }

        return false; // this will never happen, but let's return something anyway
    }

    /**
     * @param ResponseInterface $response
     * @param RequestInterface $request
     * @param string $message
     * @param bool $throw
     */
    protected function throwError(ResponseInterface $response, RequestInterface $request, $message = null, $throw = true)
    {
        if( ! $message){

            try{
                $data    = $response->json();
                $message = sprintf("%s %s\n%s\n\n%s %s\n%s", $request->getMethod(), $response->getEffectiveUrl(), $request->getBody(), $data['code'], $data['message'], $data['description']);

            }catch(\Exception $e){
                $body = $response->getBody();

                $message = sprintf("%s %s\n%s\n\nResponse Body (json encoded):\n\n%s", $request->getMethod(), $response->getEffectiveUrl(), $request->getBody(), (string) $body->getContents());
            }

        }

        if(isset($_SERVER['HTTP_HOST']) && ($throw and (strpos($_SERVER['HTTP_HOST'], '.komparu.dev') !== false || strpos($_SERVER['HTTP_HOST'], '.komparu.test') !== false || strpos($_SERVER['HTTP_HOST'], '.komparu.acc') !== false))){
            file_put_contents('errorapi.html', '<pre>' . $message . '</pre>');
            echo "<html><head><title>" . $response->getReasonPhrase() . "</title><link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css'>" . "<style>*{font-family:'Open Sans', Arial;color:#333;}#trace{padding:1em;border-radius:5px;background:#fff;opacity:.9;}#trace p{line-height:1.5em;padding:.5em 1em;margin:0;}" . "#trace p:nth-child(6){background:yellow;}}</style></head><body style='margin:0;background:pink;height:100%;'><div style='padding:1em 3em;'>";
            echo "<h1>" . $response->getReasonPhrase() . " <br /><a href='" . $response->getEffectiveUrl() . "' style='word-wrap: break-word;overflow: hidden;color:blue;'> " . urldecode($response->getEffectiveUrl()) . "</a></h1>";
            echo "<iframe src='/errorapi.html' width='100%' height='50%' style='border:0;margin-bottom:3em;'></iframe>";
            $exception = new \Exception(urldecode($response->getReasonPhrase() . " => " . $response->getEffectiveUrl()));
            echo "<div id='trace'><p>" . str_replace("\n", "</p><p>", $exception->getTraceAsString()) . "</p></div>";
            echo "</div></body></html>";
            exit;
        }elseif(class_exists('\Log')){
            \Log::error($message);
        }
    }

    /**
     * @param string $method
     * @param $params
     *
     * @return $this
     * @internal param mixed $param
     */
    public function __call($method, $params)
    {
        $this->params[$method] = current($params);

        return $this;
    }

}