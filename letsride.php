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
	const DB_VERSION = '0.2';
	const NAME = 'letsride';
	const TABLE = 'letsride';
	const PREFIX = 'letsride_'; //for preventing namespace collisions

	public static function register() {
		register_activation_hook(__FILE__, [__CLASS__, 'activate']);
		register_deactivation_hook(__FILE__, [__CLASS__, 'deactivate']);
		register_uninstall_hook(__FILE__, [__CLASS__, 'uninstall']);
		add_action('admin_menu', [__CLASS__, 'admin_menu']);
		add_action('admin_post_'.self::NAME, [__CLASS__,'admin_settings_post']);
	}

	/*
	 * Install the plugin
	 */
	public static function activate() {
		self::db_init();
		self::options_init();
		//self::cron_start(); //TODO: remove temp comment
	}

	/*
	 * Deactivate the plugin
	 */
	public static function deactivate() {
		//self::cron_stop();  //TODO: remove temp comment
		self::clear_cache();
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
	 * Handles update of information from the settings page
	 */
	public static function admin_settings_post() {
		// see https://wordpress.stackexchange.com/questions/79898/trigger-custom-action-when-setting-button-pressed
		if (!wp_verify_nonce( $_POST[ self::PREFIX.'nonce' ], self::NAME )) {
			wp_die('Invalid nonce');
		}
		$from = urldecode( $_POST['_wp_http_referer'] );

		if (isset($_POST['update-feeds'])) {
			self::update_feeds();
			echo 'Feeds updated'; //TODO: replace with admin notice
		}
		if (isset($_POST['clear-cache'])) {
			self::clear_cache();
			echo 'Cache cleared'; //TODO: replace with admin notice
		}
		//wp_safe_redirect( $from ); //TODO: comment me in when finished debugging
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
			identifier INTEGER NOT NULL,
			title VARCHAR(255),
			description TEXT,
			date DATETIME NOT NULL,
			location TEXT,
			thumbnail VARCHAR(255),
			url VARCHAR(255),
			PRIMARY KEY (id),
			UNIQUE KEY (feed_url, identifier)
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
		if (wp_next_scheduled(self::PREFIX.'update_feeds') === false) {
			wp_schedule_event(time(), 'hourly', self::PREFIX.'update_feeds' );
		}
	}

	/*
	 * Stops the cron from bringing in the data
	 */
	public static function cron_stop() {
		wp_clear_scheduled_hook(self::PREFIX.'update_feeds');
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
	public static function update_feed($feed) {
		//$url = add_query_arg( 'afterTimestamp', $timestamp, $feed );

		// https://codex.wordpress.org/Function_Reference/wp_remote_get
		$response = wp_remote_get(esc_url_raw($feed));
		if ( $response instanceof WP_Error ) {
			print_r( $response );
			//TODO: log errors
			return false;
		} else {
			$body = wp_remote_retrieve_body( $response );
			if (!$body) {
				echo 'No data returned';
				return false;
			}
			$data = json_decode( $body, true );
			if ( $data == null ) {
				echo 'Could not understand feed response';
				return false;
			}
			//errors variable may be set in the JSON
			if (isset( $data['errors'] )) {
				print_r( $data['errors'] );
				return false;
			}

			foreach ( $data['items'] as $item ) {
				self::feed_item( $feed, $item );
			}
		}
		return true;
	}

	/*
	 * Update an individual feed item
	 */
	public static function feed_item($feed, $item) {
		global $wpdb;
		$table = $wpdb->prefix.self::TABLE;
		$data = array(
			'feed_url' => $feed,
			'identifier' => intval($item['data']['identifier']),
			'title' => $item['data']['name'],
			'description' => (isset($item['data']['description']) ? $item['data']['description'] : ''),
			'date' => $item['data']['startDate'],
			'location' => serialize($item['data']['location']),
			'thumbnail' => (isset($item['programme']['logo']['url']) ? $item['programme']['logo']['url'] : ''),
			'url' => $item['data']['url'],
		);
		$wpdb->replace( $table, $data );
	}
}

LetsRide::register();