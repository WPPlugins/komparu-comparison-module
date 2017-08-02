<?php

use \Herbert\Framework\Plugin;

/**
 * Class Client
 */
class KomparuClient extends \Komparu\PhpClient\Client
{

    /**
     * @var KomparuClient
     */
    protected static $instance;

    /**
     * @var Plugin
     */
    protected $plugin;

    /**
     * @param Plugin $plugin
     */
    public function __construct(Plugin $plugin)
    {

        parent::__construct(new \GuzzleHttp\Client());

        $domain   = $plugin->settings->get_option('X-Auth-Domain', 'komparu', false);
        $username = $plugin->settings->get_option('username', 'komparu', false);
        $password = $plugin->settings->get_option('password', 'komparu', false);

        if (!($domain and $username and $password)) {
            $plugin->message->error('' .
                                    '<h3>' . $plugin->name . '</h3>'
                                    . 'You have not provided Komparu credentials.'
                                    . '<p>Please <a href="admin.php?page=komparu-configure">reconfigure</a> Komparu plugin settings.</p>'
            );

            return;
        }

        $this->setDomain($domain);
        $this->setUrl('http://api.komparu.' . $plugin->config['target'] . '/v1');

        $hash  = md5(implode('~', compact('domain', 'username', 'password')));
        $token = get_transient('cmpmd_tk_' . $hash);
        if (!preg_match('/^[a-zA-Z0-9]{4,}$/', $token)) {
            try {
                /** @var $token */
                /** @var $seconds_to_live */
                extract($this->authenticate($username, $password));
                set_transient('cmpmd_tk_' . $hash, $token, $seconds_to_live);
            } catch(Komparu\PhpClient\Exceptions\UnauthorizedException $e) {
                $plugin->message->error(''
                                        . '<h3>' . $plugin->name . '</h3>'
                                        . '<p>You have provided wrong Komparu credentials.</p>'
                                        . '<p>Please <a href="admin.php?page=komparu-configure">reconfigure</a> Komparu plugin settings.</p>'
                );

                return;
            }
        }
        $this->setToken($token);
        $plugin->client = $this;
    }

    /**
     * @param $plugin
     * @return KomparuClient
     */
    public static function getInstance($plugin)
    {
        if (is_null(self::$instance)) {
            self::$instance = new KomparuClient($plugin);
        }

        return self::$instance;
    }

    /**
     * @param array $query
     * @return array
     */
    public function get(array $query = [])
    {
        try {
            return parent::get($query);
        } catch(Exception $e) {
            return [];
        }
    }
}