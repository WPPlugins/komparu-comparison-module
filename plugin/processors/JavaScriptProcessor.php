<?php

/**
 * Class JavaScriptProcessor
 */
class JavaScriptProcessor extends BaseProcessor
{
    /**
     * @return $this
     */
    public function process()
    {
        return $this->code();
    }

    protected function code()
    {
        $this->text = preg_replace(
            '/(http\:\/\/|https\:\/\/|\/\/)code\.komparu\.' . $this->plugin->config['target'] . '/i',
            get_home_url() . '/compmodule/code',
            $this->text
        );

        return $this;
    }
}