<?php

$plugin->route->get([
    'as'    => 'compmoduleCode',
    'route' => $plugin->config['rewrite'] . 'code/:route'
], 'CodeController@get');

$plugin->route->post([
    'as'    => 'compmoduleCode',
    'route' => $plugin->config['rewrite'] . 'code/:route'
], 'CodeController@post');

$plugin->route->get([
    'as'    => 'compmoduleJavascript',
    'route' => $plugin->config['rewrite'] . 'js/:token'
], 'JavaScriptController@get');

$plugin->route->get([
    'as'    => 'compmoduleMedia',
    'route' => str_replace(get_home_url(), '', wp_upload_dir()['baseurl']) . '/compmodule/:image'
], 'MediaController@get');

add_action('wp_ajax_compmodule_drop_cache', function() use ($plugin) {
    $plugin->controller->call('AdminController@clear', $_POST['args']);
});

add_action('wp_ajax_compmodule_delete_files', function() use ($plugin) {
    $plugin->controller->call('AdminController@delete', $_POST['args']);
});