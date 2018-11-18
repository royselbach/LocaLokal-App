<?php
/**
 * Plugin Name: User Menus
 * Plugin URI: https://wordpress.org/plugins/user-menus/
 * Description: Quickly customize & extend your menus with user based functionality.
 * Version: 1.1.2
 * Author: Jungle Plugins
 * Author URI: https://jungleplugins.com/
 * Text Domain: user-menus
 *
 * Minimum PHP: 5.3
 * Minimum WP: 3.6
 *
 * @author      Daniel Iser
 * @copyright   Copyright (c) 2016, Jungle Plugins
 * @since       1.0.0
 *
 * Prior Work Credits. Big thanks to the following:
 * - No Conflict Nav Menu Walker (Modified) - Nav Menu Roles @helgatheviking
 * - Menu Importer (Modified) - Kathy Darling
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class JP_User_Menus
 */
class JP_User_Menus {

	/**
	 * @var string
	 */
	public static $NAME = 'User Menus';

	/**
	 * @var string
	 */
	public static $VER = '1.1.2';

	/**
	 * @var string
	 */
	public static $MIN_PHP_VER = '5.3';

	/**
	 * @var string
	 */
	public static $MIN_WP_VER = '3.6';

	/**
	 * @var string
	 */
	public static $URL = '';
	/**
	 * @var string
	 */
	public static $DIR = '';
	/**
	 * @var string
	 */
	public static $FILE = '';

	/**
	 * @var string
	 */
	public static $TEMPLATE_PATH = 'jp/user-menus/';

	/**
	 * @var string
	 */
	public static $TD = 'user-menus';
	/**
	 * @var         JP_User_Menus $instance The one true JP_User_Menus
	 * @since       1.0.0
	 */
	private static $instance;

	/**
	 * Get active instance
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      object self::$instance The one true JP_User_Menus
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new static;
			self::$instance->setup_constants();

			add_action( 'plugins_loaded', array( self::$instance, 'load_textdomain' ) );

			self::$instance->includes();
		}

		return self::$instance;
	}

	/**
	 * Setup plugin constants
	 *
	 * @access      private
	 * @since       1.0.0
	 * @return      void
	 */
	private function setup_constants() {
		self::$DIR  = self::$instance->plugin_path();
		self::$URL  = self::$instance->plugin_url();
		self::$FILE = __FILE__;
	}

	/**
	 * Include necessary files
	 *
	 * @access      private
	 * @since       1.0.0
	 * @return      void
	 */
	private function includes() {
		// Menu Items
		require_once self::$DIR . 'includes/classes/menu/item.php';
		require_once self::$DIR . 'includes/classes/menu/items.php';
		require_once self::$DIR . 'includes/classes/user/codes.php';
		if ( is_admin() ) {
			// Admin Menu Editor
			require_once self::$DIR . 'includes/classes/admin/menu-editor.php';
			require_once self::$DIR . 'includes/classes/admin/menu-settings.php';
			require_once self::$DIR . 'includes/classes/admin/menu-importer.php';
			require_once self::$DIR . 'includes/classes/admin/reviews.php';
		} else {
			// Site Menu Filter
			require_once self::$DIR . 'includes/classes/site/menus.php';
		}
	}

	/**
	 * Get the plugin path.
	 * @return string
	 */
	public function plugin_path() {
		return plugin_dir_path( __FILE__ );
	}

	/**
	 * Get the plugin url.
	 * @return string
	 */
	public function plugin_url() {
		return plugins_url( '/', __FILE__ );
	}

	/**
	 * Plugin Activation hook function to check for Minimum PHP and WordPress versions
	 */
	public static function activation_check() {
		global $wp_version;

		if ( version_compare( PHP_VERSION, self::$MIN_PHP_VER, '<' ) ) {
			$flag = 'PHP';
		} elseif ( version_compare( $wp_version, self::$MIN_WP_VER, '<' ) ) {
			$flag = 'WordPress';
		} else {
			return;
		}

		$version = 'PHP' == $flag ? self::$MIN_PHP_VER : self::$MIN_WP_VER;

		// Deactivate automatically due to insufficient PHP or WP Version.
		deactivate_plugins( basename( __FILE__ ) );

		$notice = sprintf( __( 'The %4$s %1$s %5$s plugin requires %2$s version %3$s or greater.', 'user-menus' ), self::$NAME, $flag, $version, "<strong>", "</strong>" );

		wp_die( "<p>$notice</p>", __( 'Plugin Activation Error', 'user-menus' ), array( 'response'  => 200, 'back_link' => true, ) );
	}

	/**
	 * Internationalization
	 *
	 * @access      public
	 * @since       1.0.0
	 * @return      void
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'user-menus' );
	}

	/**
	 * Get the template path.
	 * @return string
	 */
	public function template_path() {
		return apply_filters( 'jpum_template_path', self::$TEMPLATE_PATH );
	}

	/**
	 * Get Ajax URL.
	 * @return string
	 */
	public function ajax_url() {
		return admin_url( 'admin-ajax.php', 'relative' );
	}

}

/**
 * @return object
 */
function jp_user_menus() {
	return JP_User_Menus::instance();
}

jp_user_menus();

// Ensure plugin & environment compatibility.
register_activation_hook( __FILE__, array( 'JP_User_Menus', 'activation_check' ) );
