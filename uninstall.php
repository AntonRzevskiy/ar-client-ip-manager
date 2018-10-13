<?php
/**
 * Fired when the plugin is uninstalled.
 */

// If uninstall not called from WordPress, then exit.
if ( false === defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Current must have rules to activate plugins, then exit.
if ( false === current_user_can( 'activate_plugins' ) ) {
	exit;
}


// DROP TABLE from database.
global $wpdb;

$table_name = $wpdb->get_blog_prefix() . 'ar_clients_ip';

$wpdb->query("DROP TABLE IF EXISTS $table_name");
