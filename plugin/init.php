<?php

/** @var \Herbert\Framework\Plugin $plugin */
SessionHelper::getSid($plugin);

if (is_admin()) {
    KomparuClient::getInstance($plugin);

    if (is_multisite()) {
        try {
            $contents = @file_get_contents(wp_upload_dir()['baseurl'] . '/compmodule/check.txt');
            if ($contents !== 'check passed') {
                throw new Exception();
            }
        } catch(Exception $e) {
            $plugin->message->error(''
                                    . '<h3>' . $plugin->name . '</h3>'
                                    . '<p>Your wordpress has multisite enabled. '
                                    . 'To make make media files work in this mode please add this line to your .htaccess file</p>'
                                    . '<pre><strong>RewriteRule ^' . str_replace(ABSPATH, '', wp_upload_dir()['basedir']) . '/compmodule' . '.* index.php [L]</strong></pre>'
                                    . '<p>Please place this rule above the line that contains these rules: <i>^(wp-(content|admin|includes).*)</i></p>'
                                    . '<p>Contact your administrator if you\'re not sure what you\'re doing.</p>'
            );
        }
    }

}

add_action('upgrader_process_complete', function ($upgrader_object, $options) use ($plugin) {
    if (!(true
          and $options['type'] == 'plugin'
              and isset($options['plugins'])
                  and in_array('compmodule/plugin.php', $options['plugins']))
    ) {
        return $upgrader_object;
    }
    $admin = new AdminController($plugin);
    $admin->clear();
    $admin->delete();

    return $upgrader_object;
}, 10, 2);