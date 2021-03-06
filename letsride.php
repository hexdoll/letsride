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
		add_action('wp_ajax_'.self::PREFIX.'frontend_ajax', [__CLASS__, 'frontend_ajax']);
		add_action('wp_ajax_nopriv_'.self::PREFIX.'frontend_ajax', [__CLASS__, 'frontend_ajax']);
		add_action( 'init', [__CLASS__, 'check_url']);
	}

	/*
	 * Install the plugin
	 */
	public static function activate() {
		self::db_init();
		self::options_init();
		self::cron_start(); // TODO: Fix cron hook
	}

	/*
	 * Deactivate the plugin
	 */
	public static function deactivate() {
		self::cron_stop();
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
		if (isset($_POST['update-maps-api-key'])) {
			update_option(self::PREFIX.'maps_api_key', $_POST['maps-api-key']);
			wp_safe_redirect( $from );
		}
		//wp_safe_redirect( $from ); // TODO: comment me in when finished adding admin notices
	}

	/*
	 * Initialise the wp options for plugin
	 */
	public static function options_init()
	{
		// note that options are prefixed with the module name to prevent namespace collisions
		// feeds - an array indexed by feed url, each item contains an array of data about that feed
		add_option(self::PREFIX.'feeds', array(), '', false);
		// maps_api_key - stores the Google Maps API key
		add_option(self::PREFIX.'maps_api_key', '', '', true);
	}

	/*
	 * Remove plugin's wp options entries
	 */
	public static function options_delete() {
		delete_option(self::PREFIX.'feeds');
		delete_option(self::PREFIX.'maps_api_key');
	}

	/*
	 * Helper to get the Google Maps API key
	 */
	public static function maps_api_key() {
		return get_option(self::PREFIX.'maps_api_key');
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
		//remove all the update times for the feeds
		$updated = get_option(self::PREFIX.'feeds');
		$updated = array_map(function($a) {unset($a['updated']); return $a;}, $updated);
		update_option(self::PREFIX.'feeds', $updated);
	}

	/*
	 * Returns the places to load data from every time update feeds is run
	 * @return array of feeds
	 */
	public static function active_feed_urls() {
		// TODO: de-hardcode me
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
	 * @return true on success or false on failure
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
			// record that the feed was updated
			$updated = get_option( self::PREFIX.'feeds' );
			$updated[$feed]['updated'] = time();
			update_option(self::PREFIX.'feeds', $updated);
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
			'thumbnail' => (isset($item['data']['programme']['logo']['url']) ? $item['data']['programme']['logo']['url'] : ''),
			'url' => $item['data']['url'],
		);
		$wpdb->replace( $table, $data );
	}

	/*
	 * Generates AJAX for our frontend to use
	 */
	public static function frontend_ajax() {
		global $wpdb;
		$table = $wpdb->prefix.self::TABLE;
		$query = $wpdb->get_results("SELECT * FROM $table LIMIT 10;", ARRAY_A);
		//process db query output to be suitable for feed
		$data = array_map(function($a){
			unset($a['identifier']);
			unset($a['feed_url']);
			$location = unserialize($a['location']);
			$a['location'] = array(
				'lat' => $location['geo']['latitude'],
				'lng' => $location['geo']['longitude'],
			);
			$a['address'] = $location['address'];
			$a['place'] = $location['description'];
			return $a;
		}, $query);
		wp_send_json($data);
		wp_die();
	}

	/*
	 * Helper for plugin scripts that aren't in the root of the plugin dir
	 */
	public static function plugins_url($path='') {
		return plugins_url($path, __FILE__);
	}

	// see https://wordpress.stackexchange.com/questions/242814/how-to-use-a-frontend-url-with-a-plugin
	public static function check_url() {
		$here = $_SERVER['REQUEST_URI'];
		// TODO: make this URL a plugin setting
		// TODO: make this work for wp installs not in the root directory
		$page = '/'.self::NAME;
		if ( false !== strpos($here, $page) ) {
			add_filter('the_posts', [__CLASS__, 'frontend_page']);
			add_filter('the_content', [__CLASS__, 'frontend_page_content']);
			wp_enqueue_style(self::PREFIX.'frontend_stylesheet', self::plugins_url('public/css/page.css'));
			add_filter('script_loader_tag', [__CLASS__, 'maps_js_tag'], 10, 3);
			wp_enqueue_script('google-maps', 'https://maps.googleapis.com/maps/api/js?callback=initMap&key='.self::maps_api_key(), [], '', true);
			wp_enqueue_script(self::PREFIX.'frontend_js', self::plugins_url('public/js/page.js'), ['jquery','google-maps'], '', true);
			//allows sending data to the javascript
			$data = array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'action' => self::PREFIX.'frontend_ajax',
			);
			wp_localize_script( self::PREFIX.'frontend_js', self::PREFIX.'data', $data);
		}
	}

	// see https://wordpress.stackexchange.com/questions/236811/how-to-make-google-jquery-library-async-or-defer
	public static function maps_js_tag($tag, $handle, $src) {
		if ( 'google-maps' === $handle ) {
			return str_replace( ' src=', ' async defer src=', $tag );
		}
		// Allow all other tags to pass
		return $tag;
	}

	/*
	 * Replace all the posts but one, on a page specified in check_url
	 */
	public static function frontend_page($posts) {
		$posts = null;
		$post = new stdClass();
		$post->post_content = "Doesn't matter this will be overridden";
		$post->post_title = "Let's Ride page";
		$post->post_type = "page";
		$post->comment_status = "closed";
		$posts[] = $post;
		return $posts;
	}

	/*
	 * Replace the post content with custom content from the plugin
	 */
	// see https://wordpress.stackexchange.com/questions/166449/proper-way-to-replace-the-content-only-for-pages-created-by-custom-plugin
	public static function frontend_page_content ($content) {
		ob_start();
		include(__DIR__.'/public/page.php');
		$content = ob_get_clean();
		return $content;
	}
}

LetsRide::register();