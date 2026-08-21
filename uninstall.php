<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;

global $wpdb;
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}vehicle_manager" );
delete_option( 'vm_tag_data' );
delete_option( 'vm_db_version' );
