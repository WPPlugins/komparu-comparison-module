<?php

class Api extends BaseController
{
    public function compmodule($token)
    {

        try {
            if (!($page = $this->getPageObject($token))) {
                return false;
            }

            try {
                (new GuzzleHttp\Client())
                    ->post('http://code.komparu.' . $this->plugin->config['target'] . '/' . $token . '/visit',
                        array_diff($_SERVER, [
                            'USER',
                            'HOME',
                            'FCGI_ROLE',
                            'QUERY_STRING',
                            'SCRIPT_FILENAME',
                            'APP_ENV',
                            'SCRIPT_NAME',
                            'DOCUMENT_URI',
                            'DOCUMENT_ROOT',
                            'GATEWAY_INTERFACE',
                            'SERVER_SOFTWARE',
                            'PHP_SELF',
                            'REQUEST_TIME_FLOAT'
                        ])
                    )
                    ->getBody();
            } catch(Exception $e) {
                //nothing's wrong if we couldn't store the visit, but Guzelle will throw an error anyway.
            }

            add_action('wp_footer', function () use ($token) {
                echo '
                <script type="text/javascript" src="' . get_home_url() . '/compmodule/js/' . $token . '"></script>
                <script type="">jQuery(document).ready(function(){
                Kmp.sessionId="' . $sid = SessionHelper::getSid($this->plugin) . '";
                });</script>';
            });

            return $this->view->render('shortcode/index', [
                'css'  => $page->css,
                'html' => $page->html
            ]);
        } catch(Exception $e) {
            return $this->view->render('shortcode/index', []);
        }
    }
}

$apiName = $plugin->config['api'];
global $$apiName;
$$apiName    = new Api($plugin);
$plugin->api = $$apiName;

