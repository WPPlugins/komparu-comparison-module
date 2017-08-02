<?php

$plugin->panel->add( [
	'type'  => 'panel',
	'as'    => 'mainPanel',
	'title' => 'Komparu',
	'slug'  => 'komparu-index',
	'icon'  => '/gfx/icon.png'
], 'AdminController@index' );

$plugin->panel->add( [
	'type'   => 'subpanel',
	'as'     => 'configure',
	'parent' => 'mainPanel',
	'title'  => 'Configure',
	'slug'   => 'komparu-configure'
], 'AdminController@settings' );