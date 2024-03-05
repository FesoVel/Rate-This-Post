<?php
/**
 * Plugin Name:       Rate This Post
 * Plugin URI:        https://agorawebdesigns.com
 * Description:       Adds a simple voting system to your WordPress posts, allowing users to vote "Yes" or "No" on each post.
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Viktor Veljanovski
 * Author URI:        https://agorawebdesigns.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       rate-this-post
 */


if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access
}

// Define plugin variables
define( 'RTP_PLUGIN_FILE', __FILE__ );
define( 'RTP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

// Include the main plugin class
require_once RTP_PLUGIN_DIR . 'class-rate-this-post.php';

// Init the plugin
add_action( 'plugins_loaded', array( 'RTP_RateThisPost', 'get_instance' ) );
