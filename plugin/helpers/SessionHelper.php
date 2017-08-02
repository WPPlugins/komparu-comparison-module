<?php

class SessionHelper
{
    public static function getSid($plugin, $force = false)
    {
        if (!session_id()) {
            session_start();
        }
        if (!isset($_SESSION['kmpsid']) or $force) {
            $json               = (new GuzzleHttp\Client())->post('http://code.komparu.'.$plugin->config['target'].'/kmpsid')->json();
            $_SESSION['kmpsid'] = $json['session_id'];
        }

        return $_SESSION['kmpsid'];
    }

    public static function setSid($sid)
    {
        $_SESSION['kmpsid'] = $sid;
    }

}