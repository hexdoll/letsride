<?php
/*
Plugin Name: Let's Ride
Description: Demo Project for Big Wave Media.
Author: Gemma Peter
Version: 0.1
Author URI: http://gemmapeter.co.uk/
*/

class LetsRide
{
	const DB_VERSION = '0.1';
	const NAME = 'letsride';
	const TABLE = 'letsride';
	const PREFIX = 'letsride_'; //for preventing namespace collisions

	public static function register() {
		register_activation_hook(__FILE__, [__CLASS__, 'activate']);
		register_deactivation_hook(__FILE__, [__CLASS__, 'deactivate']);
		register_uninstall_hook(__FILE__, [__CLASS__, 'uninstall']);
		add_action('admin_menu', [__CLASS__, 'admin_menu']);
	}

	/*
	 * Install the plugin
	 */
	public static function activate() {
		self::db_init();
		self::options_init();
		self::cron_start();
	}

	/*
	 * Deactivate the plugin
	 */
	public static function deactivate() {
		self::cron_stop();
	}

	/*
	 * Uninstall the plugin
	 */
	public static function uninstall() {
		self::db_delete();
		self::options_delete();
	}

	/*
	 * Sets up the menus for the plugin in the admin area
	 */
	public static function admin_menu() {
		add_menu_page(
			get_plugin_data(__FILE__)['Name'], //page title
			get_plugin_data(__FILE__)['Name'], //menu title
			'manage_options', //see https://codex.wordpress.org/Roles_and_Capabilities
			self::NAME,
			[__CLASS__, 'admin_settings_page']
		);
	}

	public static function admin_settings_page() {
		if (!current_user_can('manage_options')) {
			wp_die('You do not have sufficient permissions to access this page.');
		}

		include(__DIR__.'/admin/settings.php');
	}

	/*
	 * Initialise the wp options for plugin
	 */
	public static function options_init()
	{
		// see https://codex.wordpress.org/Function_Reference/add_option
		add_option(self::PREFIX.'last_updated', array(), '', false);
		add_option(self::PREFIX.'maps_api', '', '', true);
	}

	/*
	 * Remove plugin's wp options entries
	 */
	public static function options_delete() {
		// see https://codex.wordpress.org/Function_Reference/delete_option
		delete_option(self::PREFIX.'last_updated');
		delete_option(self::PREFIX.'maps_api');
	}

	/*
	 * Initialise the db table for storage of feed data
	 */
	public static function db_init() {
		// https://codex.wordpress.org/Creating_Tables_with_Plugins
		global $wpdb;
		$table = $wpdb->prefix.self::TABLE;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table (
			id INTEGER NOT NULL AUTO_INCREMENT,
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

		add_option(self::PREFIX.'db_version', self::DB_VERSION);
	}

	/*
	 * Cleanly removes table from database
	 */
	public static function db_delete() {
		global $wpdb;
		$table = $wp_db->prefix.self::TABLE;
		$wpdb->query("DROP TABLE IF EXISTS $table;");
		delete_option(self::PREFIX.'db_version');
	}

	/*
	 * Starts the cron to bring in the data
	 */
	public static function cron_start() {
		//TODO: write me
	}

	/*
	 * Stops the cron from bringing in the data
	 */
	public static function cron_stop() {
		//TODO: write me
	}

	/*
	 * Empties the database table
	 */
	public static function clear_cache() {
		global $wpdb;
		$table = $wpdb->prefix.self::TABLE;
		$wpdb->query("DELETE FROM $table;");
		update_option(self::PREFIX.'last_updated', array());
	}

	/*
	 * Returns the places to load data from every time update feeds is run
	 * @return array of feeds
	 */
	public static function active_feed_urls() {
		return array(
			"http://api.letsride.co.uk/public/v1/rides",
		);
	}

	/*
	 * This function is triggered by wp cron to update the data feeds
	 */
	public static function update_feeds() {
		foreach (self::active_feed_urls() as $feed) {
			self::update_feed($feed);
		}
	}

	/*
	 * Separated in case the plugin needs the functionality to update a single feed
	 * @return true on success or an array of errors
	 */
	public static function update_feed($url) {
		$errors = array(); //TODO: log these somewhere

		// https://codex.wordpress.org/Function_Reference/wp_remote_get
		$response = wp_remote_get(esc_url_raw($url));
		if ( $code == 200 ) {
			//TODO: Deal with it
		} else {
			$errors[] = 'Could not fetch feed ';
		}
		//did we get something or an error
		//did we get something that makes sense
		return true;
	}
}

LetsRide::register();