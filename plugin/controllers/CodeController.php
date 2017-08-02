<?php

class CodeController extends BaseController
{
    public function post($route)
    {
        $code = (new \GuzzleHttp\Client())
            ->post($this->getUrl($route), ['headers' => ['Content-Type' => 'application/json'], 'body' => file_get_contents("php://input")])
            ->getBody()
            ->getContents();

        $this->process($code);
    }

    public function get($route)
    {
        $code = file_get_contents($this->getUrl($route));

        $this->process($code);
    }

    /**
     * @param $route
     *
     * @return string
     */
    private function getUrl($route)
    {
        return sprintf(
            'http://code.komparu.%s/%s/?%s',
            $this->plugin->config['target'],
            $route,
            $_SERVER['QUERY_STRING']
        );
    }

    /**
     * @param $code
     */
    private function process($code)
    {
        $code = preg_replace_callback('/(Kmp\.json_[a-z0-9]*?)\((.*)\)\;/msi', function ($json) {
            $data = json_decode($json[2]);
            if (property_exists($data, 'documents')) {
                array_walk($data->documents, function ($document) {
                    $document->{'url'}           = HTMLProcessor::hashed($document->{'url'}, $this->plugin);
                    $document->{'company.image'} = HTMLProcessor::hashed(
                        MediaController::checkForSlashes($document->{'company.image'}),
                        $this->plugin
                    );
                });
            }
            if (property_exists($data, 'html')) {
                $html       = new HTMLProcessor($this->plugin, $data->html, '');
                $data->html = $html->process()->getText();
            }
            if (property_exists($data, 'url')) {
                $data->url = preg_replace(
                    '/http(s?)\:\/\/code\.komparu\.[a-z]{2,4}\//',
                    get_home_url() . $this->plugin->config['rewrite'] . 'code/',
                    $data->url
                );
            }

            return $json[1] . '(' . json_encode($data, JSON_UNESCAPED_UNICODE) . ');';
        }, $code);

        header('Content-Type: text/javascript; charset=UTF-8');
        header('Content-length: ' . strlen($code));
        echo $code;
        exit();
    }
}