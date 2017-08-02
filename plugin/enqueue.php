<?php
/** @var Herbert\Framework\Enqueue $enqueue */

$plugin->enqueue->admin([
    'as'     => 'copy',
    'src'    => '/js/copy.js',
    'filter' => ['panel' => ['mainPanel', 'configure']]
]);

$plugin->enqueue->admin([
    'as'     => 'copy',
    'src'    => '/css/admin.css',
    'filter' => ['panel' => ['mainPanel', 'configure']]
]);