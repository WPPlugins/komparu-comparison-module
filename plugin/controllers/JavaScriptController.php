<?php

class JavaScriptController extends BaseController
{
    public function get($token)
    {
        $page = $this->getPageObject($token);

        header('Content-Type: text/javascript');
        header('Content-length: ' . strlen($page->js));

        echo $page->js;
    }
}