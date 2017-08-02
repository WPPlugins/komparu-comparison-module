<?php

/**
 * Class CSSProcessor
 */
class CSSProcessor extends BaseProcessor
{
    /**
     * holds the stylesheet's initial URL for relative paths
     * @var array
     */
    protected $url;

    /**
     * @param \Herbert\Framework\Plugin $plugin
     * @param $text
     * @param $url
     */
    public function __construct($plugin, $text, $token, $url)
    {
        $this->url = parse_url($url);

        return parent::__construct($plugin, $text, $token);
    }

    /**
     * @return $this
     */
    public function process()
    {
        return $this->url();
    }

    /**
     * @return $this
     */
    protected function url()
    {
        $this->text = preg_replace_callback('/url\s?\([\"]?(.*?)[\"]?\)/msi', function ($url) {
            $parsed = parse_url($url[1]);

            if (!isset($parsed['host'])) {
                // url had relative path
                $path    = preg_replace(
                    '#\.\.\/|([^\/]+?\/)\.\.\/#', '',
                    dirname($this->url['path']) . '/' . $parsed['path']
                );
                $newpath = $this->path($path);

                MediaController::saveTransient(
                    trim($newpath, '/'),
                    $this->url['scheme'] . '://' . $this->url['host'] . $path
                );

                $url = wp_upload_dir()['baseurl'] . '/compmodule' . $newpath;
            } elseif (stristr($parsed['host'], 'komparu')) {
                // url had absolute komparu path
                $path    = trim($parsed['path'], '/');
                $newpath = $this->path($path);

                MediaController::saveTransient(
                    $newpath,
                    (preg_match('/^\/\//', $url[1]) ? ($this->url['scheme'] . ':') : '') . $url[1]
                );
                $url = wp_upload_dir()['baseurl'] . '/compmodule/' . $newpath;
            } else {
                // url is outside of komparu domain
                $url = $url[1];
            }
            $url = preg_replace('/http[s]?\:\/\//i', '//', $url);

            return 'url("' . $url . '")';
        }, $this->text);

        $this->text = preg_replace_callback('/\s*}.*?\s*{/', function ($m) {
            return str_replace('komparu', 'compmodule', $m[0]);
        }, $this->text);

        return $this;
    }

    private function path($path)
    {
        return preg_replace_callback('/([^\/]+?)\.([^.]*$|$)/', function ($m) {
            return str_replace('komparu', 'compmodule', substr($m[1], 0, 16))
                   . ($this->plugin->config['nginx'] ? '_' : '.')
                   . $m[2];
        }, $path);
    }
}