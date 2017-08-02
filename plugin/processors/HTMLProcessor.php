<?php

/**
 * Class HTMLProcessor
 */
class HTMLProcessor extends BaseProcessor
{
    /**
     * @var DOMDocument
     */
    protected $dom;

    /**
     * @return $this
     */
    public function process()
    {
        $this->dom                     = new DOMDocument();
        $this->dom->preserveWhiteSpace = false;
        @$this->dom->loadHTML('<?xml encoding="UTF-8">' . $this->text);

        return $this->images()->urls()->finish();
    }

    /**
     * Replaces the images with local urls
     * @return $this
     */
    public function images()
    {
        $x = new DOMXPath($this->dom);

        foreach ($x->query('//img[not(ancestor::template)]') as $img) {
            /** @var DOMNode $img */
            if (($src = $img->attributes->getNamedItem('src')) and ($src->value !== '')) {
                /** @var DOMAttr $src */
                $src->value = self::hashed($src->value, $this->plugin);
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function urls()
    {
        $x = new DOMXPath($this->dom);

        foreach ($x->query('//a[not(ancestor::template)]') as $a) {
            /** @var DOMNode $img */
            if ($src = $a->attributes->getNamedItem('href')) {
                /** @var DOMAttr $src */
                $src->value = self::hashed($src->value, $this->plugin);
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function finish()
    {
        $body = $this->dom->getElementsByTagName('body');
        $body = $body->item(0);
        $text = '';

        foreach ($body->childNodes as $childNode) {
            $text .= @$body->ownerDocument->saveHTML($childNode);
        }

        $this->text = str_replace(['%7B%7B', '%7D%7D'], ['{{', '}}'], $text);

        return $this;
    }

    public static function hashed($url, $plugin)
    {
        if ($url == '#') {
            return $url;
        }
        $hashed = preg_replace('/^(.)(.)(.{10}).*$/', '\1/\2/\3', md5($url)) .
                  (preg_match('/\.([^\/\.]{1,5})$/', $url, $m) ? (($plugin->config['nginx'] ? '_' : '.') . $m[1]) : '');

        if (!get_transient('cmpmd_' . $hashed)) {
            MediaController::saveTransient($hashed, $url);
        }

        return wp_upload_dir()['baseurl'] . '/compmodule/' . $hashed;
    }
}