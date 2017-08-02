<?php
$sections = [
    [
        'id'    => 'komparu',
        'title' => 'General Komparu API settings'
    ]
];

$fields = [
    'komparu' => [
        [
            'name'  => 'username',
            'label' => 'Username',
            'desc'  => 'A username used to login into partner backend',
            'type'  => 'text'
        ],
        [
            'name'  => 'password',
            'label' => 'Password',
            'desc'  => 'A password corresponding to the username',
            'type'  => 'password'
        ],
        [
            'name'    => 'X-Auth-Domain',
            'label'   => 'X-Auth-Domain',
            'desc'    => 'X-Auth-Domain',
            'type'    => 'text',
            'default' => 'partner.komparu.com'
        ],
        [
            'name'    => 'check_up',
            'label'   => 'API call frequency',
            'desc'    => 'How often should plugin check for Komparu widget updates (in minutes)',
            'type'    => 'number',
            'default' => $plugin->config['check_up']
        ]
    ]
];

$plugin->settings->set_sections($sections);
$plugin->settings->set_fields($fields);
add_action('admin_init', function () use ($plugin) {
    $plugin->settings->admin_init();
});