<?php
// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit();
}

global $wpdb;

// Name of the table, same as in your plugin
$table_name = $wpdb->prefix . 'rtp_votes';

// Delete the table from the database
$wpdb->query("DROP TABLE IF EXISTS $table_name");
