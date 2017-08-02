<?php

if (!defined('HERBERT_CONFIG')) {
    die();
}

return [
    'framework' => 'framework',
    'plugin'    => 'plugin',
    'views'     => 'views',
    'assets'    => 'assets',
    'core'      => 'plugin.php',
    'api'       => 'komparuApi',
    'name'      => 'Komparu',
    'target'    => 'com',
    'rewrite'   => '/compmodule/',
    'eloquent'  => false,
    'check_up'  => 60,
    'nginx'     => false
                   or stristr($_SERVER['SERVER_SOFTWARE'], 'nginx')
                   or !in_array($_SERVER['SERVER_PORT'], [80, 443])
];
