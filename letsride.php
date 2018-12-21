<?php
/**
 * @package Lets_Ride
 * @version 0.1
 */
/*
Plugin Name: Let's Ride
Description: Demo Project for Big Wave Media.
Author: Gemma Peter
Version: 0.1
Author URI: http://gemmapeter.co.uk/
*/

$letsride_db_version = '0.1';
$letsride_db_table = 'letsride';

/*
 * Install the plugin
 */
register_activation_hook(__FILE__, 'letsride_options_init');
register_activation_hook(__FILE__, 'letsride_db_init');
register_activation_hook(__FILE__, 'letsride_cron_start');

/*
 * Initialise the wp options for plugin
 */
function letsride_options_init()
{
	//Google maps API credentials
	//plugin db version
}

/*
 * Initialise the db table for storage of feed data
 */
function letsride_db_init()
{
	// https://codex.wordpress.org/Creating_Tables_with_Plugins
	global $wpdb;
	global $letsride_db_table;
	$table = $wpdb->prefix . $letsride_db_table;
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table
		id INT NOT NULL AUTO_INCREMENT,
		feed_url VARCHAR(255) NOT NULL,
		title VARCHAR(255),
		description TEXT,
		date DATETIME NOT NULL,
		location TEXT,
		thumbnail VARCHAR(255),
		url VARCHAR(255),
		PRIMARY KEY (id)
	) $charset_collate;";

	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);

	add_option('letsride_db_version', $letsride_db_version);
}

/*
 * Starts the cron to bring in the data
 */
function letsride_cron_start()
{
	//TODO: write me
}

/*
 * Deactivate the plugin
 */
register_deactivation_hook(__FILE__, 'letsride_cron_stop');

/*
 * Starts the cron to bring in the data
 */
function letsride_cron_stop()
{
	//TODO: write me
}

/*
 * Runs the uninstall procedure for the plugin
 */
register_uninstall_hook(__FILE__, 'letsride_db_delete');
register_uninstall_hook(__FILE__, 'letsride_options_delete');

function letsride_db_delete()
{
	global $wpdb;
	//$wpdb->
	add_option('letsride_db_version', $letsride_db_version);
}

/*
 * Remove plugin's wp options entries
 */
function letsride_options_delete()
{
	//TODO: Write me
}

/*
 * Returns the places to load data from (currently hardcoded)
 * @return array of feeds
 */
function letsride_feed_urls()
{
	return array(
		"http://api.letsride.co.uk/public/v1/rides",
	);
}
