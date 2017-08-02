<?php

/**
 * Class BaseProcessor
 */
abstract class BaseProcessor extends BaseController
{
    /**
     * @var string
     */
    protected $text;

    /**
     * @var string
     */
    protected $token;

    /**
     * @param \Herbert\Framework\Plugin $plugin
     * @param $text
     */
    public function __construct($plugin, $text, $token)
    {
        $this->text  = $text;
        $this->token = $token;

        return parent::__construct($plugin);
    }

    /**
     * @return $this
     */
    public abstract function process();

    /**
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }
}