<?php
/**
 * Plugin Name: WP Client IP Manager
 * Description: .
 * Plugin URI: https://github.com/AntonRzevskiy/ar-client-ip-manager
 * Author: Anton Rzhevskiy
 * Author URI: https://github.com/AntonRzevskiy
 * Version: 0.0.1
 * License: MIT License
 *
 * @package AR_Client_IP_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'AR_CLIENT_IP_MANAGER_VERSION', '1.0.0' );
define( 'AR_CLIENT_IP_MANAGER_FILE', __FILE__ );
define( 'AR_CLIENT_IP_MANAGER_PATH', plugin_dir_path( AR_CLIENT_IP_MANAGER_FILE ) );
define( 'AR_CLIENT_IP_MANAGER_URL', plugin_dir_url( AR_CLIENT_IP_MANAGER_FILE ) );

register_activation_hook( AR_CLIENT_IP_MANAGER_FILE, array( 'AR_Client_IP_Manager', 'activate' ) );
register_deactivation_hook( AR_CLIENT_IP_MANAGER_FILE, array( 'AR_Client_IP_Manager', 'deactivate' ) );

/**
 * Class AR_Client_IP_Manager
 */
final class AR_Client_IP_Manager {

	/**
	 * Plugin instance.
	 *
	 * @since    0.0.1
	 *
	 * @access   private
	 *
	 * @var      object    $instance    Instance of the class AR_Client_IP_Manager.
	 */
	private static $instance = null;

	/**
	 * Current user data.
	 *
	 * @since    0.0.1
	 *
	 * @access   private
	 *
	 * @var      array     $user_data   Cached user data for one instance.
	 */
	private static $user_data = null;

	/**
	 * Current user IP.
	 *
	 * @since    0.0.1
	 *
	 * @access   private
	 *
	 * @var      string    $user_ip     Cached user IP for one instance.
	 */
	private static $user_ip = null;

	/**
	 * Name of table in data base WP.
	 *
	 * @since    0.0.1
	 *
	 * @access   private
	 *
	 * @var      string    $table_name  Cached table name for one instance.
	 */
	private static $table_name = null;

	/**
	 * Settings.
	 *
	 * @since    0.0.1
	 *
	 * @access   private
	 *
	 * @var      array     $_settings   Plugin settings.
	 */
	private static $_settings = array();

	/**
	 * Set table name with WP prefix.
	 *
	 * @since    0.0.1
	 *
	 * @access   private
	 */
	private static function set_table_name() {

		global $wpdb;

		self::$table_name = $wpdb->get_blog_prefix() . 'ar_clients_ip';

	}

	/**
	 * Set current user IP.
	 *
	 * @since    0.0.1
	 *
	 * @access   private
	 */
	private static function set_user_ip() {

		self::$user_ip = '1.1.1.1';

	}

	/**
	 * Clear the cache in the database.
	 *
	 * @since    0.0.1
	 *
	 * @access   private
	 */
	private static function db_clear_old_users() {

		global $wpdb;

		$table_name = self::$table_name;

		$wpdb->query( $wpdb->prepare( "DELETE FROM `$table_name` WHERE user_lifetime < %d", time() ) );

	}

	/**
	 * Get the cache from the database.
	 *
	 * @since    0.0.1
	 *
	 * @access   private
	 *
	 * @return   array|null             Array with cached user data or null if fail.
	 */
	private static function db_get_user() {

		global $wpdb;

		$table_name = self::$table_name;

		$user_data = $wpdb->get_var( $wpdb->prepare( "SELECT `user_data` FROM `$table_name` WHERE `user_ip` = %s AND `user_lifetime` > %d", self::$user_ip, time() ) );

		if ( $user_data ) $user_data = unserialize( $user_data );

		return $user_data;
	}

	/**
	 * Set new user in database.
	 *
	 * @since    0.0.1
	 *
	 * @access   private
	 *
	 * @param    array   $args          {
	 *                                  Optional. A named array.
	 *
	 *   @type   string  $user_ip       IP of current user.
	 *   @type   array   $user_data     User Data.
	 *   @type   int     $user_lifetime Seconds since the Unix Epoch.
	 *
	 * }
	 */
	private static function db_set_user( $args ) {

		global $wpdb;

		$args = wp_parse_args( $args, array(

			'user_ip' => self::$user_ip,
			'user_data' => array(),
			'user_lifetime' => ( time() + 3600 ),

		) );

		$args = apply_filters( 'ar_client_ip_manager_before_cash', $args );

		$args[ 'user_data' ] = serialize( $args[ 'user_data' ] );

		$wpdb->insert( self::$table_name, $args, array( '%s', '%s', '%d' ) );

	}

	/**
	 * Set user into instance object.
	 *
	 * @since    0.0.1
	 *
	 * @access   private
	 */
	private static function set_user() {

		$user_data = self::db_get_user();

		if ( $user_data ) {

			self::$user_data = $user_data;

			return;
		}

		if ( is_callable( self::$_settings[ 'set_user_func' ] ) && is_callable( self::$_settings[ 'is_bot' ] ) ) {

			if ( true === call_user_func( self::$_settings[ 'is_bot' ] ) ) return;

			$user_data = call_user_func( self::$_settings[ 'set_user_func' ], self::$user_ip );

			if ( $user_data ) {

				self::db_set_user( array(

					'user_data' => $user_data

				) );

				self::$user_data = $user_data;
			}
		}

	}

	/**
	 * Constructor.
	 *
	 * @since    0.0.1
	 *
	 * @access   private
	 */
	private function __construct() {

		add_action( 'plugins_loaded', array( $this, 'init' ) );

	}

	/**
	 * Get current user data.
	 *
	 * @since    0.0.1
	 *
	 * @static
	 *
	 * @return   object                 Object user data.
	 */
	public static function get_user() {

		if ( ! isset( self::$user_data ) ) self::set_user();

		return (object) self::$user_data;
	}

	/**
	 * Settings instance.
	 *
	 * @since    0.0.1
	 *
	 * @static
	 *
	 * @param    array   $args          {
	 *                                  Optional. A named array.
	 *
	 *   @type   funct   $set_user_func Function called to define user data array.
	 *   @type   funct   $is_bot        Function to define bots.
	 *
	 * }
	 *
	 * @return   object                 Instance of the class AR_Client_IP_Manager.
	 */
	public static function settings( $args ) {

		$args = wp_parse_args( $args, array(

			'set_user_func' => function( $ip ) {

				return array( 'ip' => $ip );
			},

			'is_bot' => function() {

				return (bool) preg_match( "~(Google|Yahoo|Rambler|Bot|Yandex|Spider|Snoopy|Crawler|Finder|Mail|curl)~i", $_SERVER[ 'HTTP_USER_AGENT' ] );
			},

		) );

		self::$_settings = $args;

		return self::$instance;
	}

	/**
	 * Get plugin instance or set it once.
	 *
	 * @since    0.0.1
	 *
	 * @static
	 *
	 * @return   object                 Instance of the class AR_Client_IP_Manager.
	 */
	public static function get_instance() {

		if ( ! isset( self::$instance ) ) {

			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Define vars.
	 *
	 * @since    0.0.1
	 */
	public function init() {

		self::set_table_name();

		self::db_clear_old_users();

		self::set_user_ip();

	}

	/**
	 * Run when activate plugin.
	 *
	 * This function create custom table in database.
	 *
	 * @since    0.0.1
	 */
	public static function activate() {

		global $wpdb;

		/**
		 * Include upgrade.php for use @dbDelta function
		 */
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$table_name = $wpdb->get_blog_prefix() . 'ar_clients_ip';
		$charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset} COLLATE {$wpdb->collate}";

		$sql = "CREATE TABLE {$table_name} (
		id  bigint(20) unsigned NOT NULL auto_increment,
		user_ip varchar(255) NOT NULL,
		user_data longtext NOT NULL,
		user_lifetime  bigint(20) NOT NULL default 0,
		PRIMARY KEY  (id),
		KEY user_ip (user_ip),
		KEY user_lifetime (user_lifetime)
		)
		{$charset_collate};";

		dbDelta( $sql );

	}

	/**
	 * Run when deactivate plugin.
	 *
	 * @since    0.0.1
	 */
	public static function deactivate() {
	}
}

function ar_client_ip_manager() {
	return AR_Client_IP_Manager::get_instance();
}

$GLOBALS['ar_client_ip_manager'] = ar_client_ip_manager();
