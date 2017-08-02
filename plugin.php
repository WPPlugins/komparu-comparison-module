<?php

/**
 * @wordpress-plugin
 * Plugin Name:       Komparu
 * Plugin URI:        http://komparu.com/
 * Description:       Enables easy embedding with komparu.com comparison widgets into your posts.
 * Version:           1.0.31
 * Author:            Komparu B.V.
 * Author URI:        http://komparu.com/wordpress-plugin
 * License:           MIT
 */

require_once __DIR__ . '/vendor/autoload.php';

// Initialise framework
$plugin = new Herbert\Framework\Plugin();

if ( ! get_option( 'permalink_structure' ) ) {
	$plugin->message->error( $plugin->name . ': Please ensure you have permalinks enabled.' );
}
