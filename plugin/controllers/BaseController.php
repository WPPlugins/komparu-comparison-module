<?php

use Herbert\Framework\Plugin;
use Herbert\Framework\Traits\PluginAccessorTrait;

class BaseController
{

    use PluginAccessorTrait;

    /**
     * @var \Herbert\Framework\Plugin
     */
    protected $plugin;

    /**
     * @param \Herbert\Framework\Plugin $plugin
     */
    public function __construct(Plugin $plugin)
    {
        $this->plugin = $plugin;
    }

    public function getPageObject($token)
    {
        if (!($page = get_transient('cmpmd_pg_' . $token)) or !$this->isWebsiteUpdated($token) or isset($_GET['f'])) {
            $url = sprintf(
                       'http://code.komparu.%s/%s/page/?format=plugin',
                       $this->plugin->config['target'],
                       $token
                   ) . (isset($_GET['f']) ? ('&' . http_build_query(['f' => $_GET['f']])) : '');
            if (!($page = json_decode(@file_get_contents($url)))) {
                return false;
            }

            if (isset($page->sid) and trim($page->sid) != '') {
                SessionHelper::setSid($page->sid);
            }

            $page->css = preg_replace('/^\/\//', 'http://', $page->css);
            $page->js  = preg_replace('/^\/\//', 'http://', $page->js);

            $html = new HTMLProcessor($this->plugin, $page->html, $token);
            $css  = new CSSProcessor($this->plugin, file_get_contents($page->css), $token, $page->css);
            $js   = new JavaScriptProcessor($this->plugin, file_get_contents($page->js), $token);

            $page->html = $html->process()->getText();
            $page->css  = $css->process()->getText();
            $page->js   = $js->process()->getText();

            if (!isset($_GET['f'])
                and (trim($page->html) != '')
                    and (trim($page->css) != '')
                        and (trim($page->js) != '')
            ) {
                // pg — page
                set_transient('cmpmd_pg_' . $token, $page, 5 * MINUTE_IN_SECONDS);
                // lu — last update
                set_transient('cmpmd_lu_' . $token, time(), YEAR_IN_SECONDS);
                // cr - checked recently
                set_transient('cmpmd_cr_' . $token, true,
                    MINUTE_IN_SECONDS * $this->plugin->settings->get_option(
                        'check_up', 'komparu',
                        $this->plugin->config['check_up']
                    )
                );
            }
        }

        return $page;
    }

    private function isWebsiteUpdated($token)
    {
        $checked_recently = get_transient('cmpmd_cr_' . $token);
        if (!$checked_recently) {
            $last_updated = get_transient('cmpmd_lu_' . $token);
            try {
                $updated_at = strtotime(
                    KomparuClient::getInstance($this->plugin)
                                 ->resource('website/token/' . $token)
                                 ->get(['visible' => 'updated_at'])
                    ['updated_at']
                );
            } catch(Exception $e) {
                return false;
            }

            set_transient('cmpmd_cr_' . $token, true,
                MINUTE_IN_SECONDS * $this->plugin->settings->get_option(
                    'check_up', 'komparu',
                    $this->plugin->config['check_up']
                )
            );

            return !$last_updated or ($last_updated > $updated_at);
        }

        return true;
    }

}
