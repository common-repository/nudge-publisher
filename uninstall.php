<?php
/**
 * Remove user metadata and options on plugin delete.
 */
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}
/* @var $wpdb wpdb */
global $wpdb;


// Delete post meta
delete_post_meta_by_key( '_ndg_plublisher_code_id' );

$table_name = $wpdb->prefix . 'ndg_plublisher_codes';
$wpdb->query( "DROP TABLE IF EXISTS " . $table_name );
