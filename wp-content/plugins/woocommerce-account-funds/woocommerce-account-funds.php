<?php
/*
 * Plugin Name: WooCommerce Account Funds
 * Plugin URI: https://woocommerce.com/products/account-funds/
 * Description: Allow customers to deposit funds into their accounts and pay with account funds during checkout.
 * Version: 2.1.10
 * Author: WooCommerce
 * Author URI: https://woocommerce.com/
 * Requires at least: 4.0
 * WC tested up to: 3.3
 * WC requires at least: 2.6
 *
 * Copyright: 2009-2017 WooCommerce.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Woo: 18728:a6fcf35d3297c328078dfe822e00bd06
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Required functions
 */
if ( ! function_exists( 'woothemes_queue_update' ) ) {
	require_once( 'woo-includes/woo-functions.php' );
}

/**
 * Plugin updates
 */
woothemes_queue_update( plugin_basename( __FILE__ ), 'a6fcf35d3297c328078dfe822e00bd06', '18728' );

if ( is_woocommerce_active() ) {

	define( 'WC_ACCOUNT_FUNDS_VERSION', '2.1.10' );

	/**
	 * WC_Account_Funds
	 */
	class WC_Account_Funds {

		/**
		 * Plugin's version.
		 *
		 * @var string
		 */
		public $version;

		/**
		 * Constructor
		 */
		public function __construct() {
			$this->version = WC_ACCOUNT_FUNDS_VERSION;

			add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );
			add_action( 'plugins_loaded', array( $this, 'init_early' ) );
			add_action( 'init', array( $this, 'init' ) );
			add_action( 'plugins_loaded', array( $this, 'version_check' ), 0 );
			add_action( 'plugins_loaded', array( $this, 'gateway_init' ), 0 );
			add_action( 'widgets_init', array( $this, 'widgets_init' ) );
			add_action( 'init', array( $this, 'admin_init' ) );
			add_filter( 'woocommerce_email_classes', array( $this, 'add_email_classes' ), 99 );
			add_filter( 'woocommerce_payment_gateways', array( $this, 'register_gateway' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'checkout_scripts' ) );
			add_filter( 'woocommerce_data_stores', array( $this, 'add_data_stores' ) );

			define( 'WC_ACCOUNT_FUNDS_FILE', __FILE__ );
			register_activation_hook( __FILE__, array( $this, 'activate' ) );
		}

		/**
		 * Load plugin textdomain
		 */
		public function load_plugin_textdomain() {
			load_plugin_textdomain( 'woocommerce-account-funds', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		}

		/**
		 * Classes that need to be loaded early.
		 *
		 * @since 2.0.12
		 */
		public function init_early() {
			include_once( 'includes/class-wc-account-funds-my-account.php' );
		}

		/**
		 * Load classes
		 */
		public function init() {
			include_once( 'includes/class-wc-product-deposit.php' );
			include_once( 'includes/class-wc-product-topup.php' );
			include_once( 'includes/class-wc-account-funds-cart-manager.php' );
			include_once( 'includes/class-wc-account-funds-deposits-manager.php' );
			include_once( 'includes/class-wc-account-funds-order-manager.php' );
			include_once( 'includes/class-wc-account-funds-integration.php' );
			include_once( 'includes/class-wc-account-funds-shortcodes.php' );
		}

		/**
		 * Perform version check. Update routine will be performed if current
		 * plugin's version doesn't match with installed version.
		 */
		public function version_check() {
			if ( ! class_exists( 'WC_Account_Funds_Installer' ) ) {
				include_once( 'includes/class-wc-account-funds-installer.php' );
			}
			WC_Account_Funds_Installer::update_check( $this->version );
		}

		/**
		 * Init Gateway
		 */
		public function gateway_init() {
			if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
				return;
			}
			include_once( 'includes/class-wc-gateway-account-funds.php' );
		}

		/**
		 * Load Widget
		 */
		public function widgets_init() {
			include_once( 'includes/class-wc-account-funds-widget.php' );
		}

		/**
		 * Load admin
		 */
		public function admin_init() {
			if ( is_admin() ) {
				include_once( 'includes/class-wc-account-funds-admin.php' );
				include_once( 'includes/class-wc-account-funds-admin-product.php' );
				include_once( 'includes/class-wc-account-funds-reports.php' );
			}
		}

		/**
		 *  Add email to the list of emails WooCommerce should load.
		 */
		public function add_email_classes( $email_classes ) {
			include_once( 'includes/class-wc-account-funds-email-account-funds-increase.php' );
			$email_classes['WC_Account_Funds_Email_Account_Funds_Increase'] = new WC_Account_Funds_Email_Account_Funds_Increase();
			return $email_classes;
		}

		/**
		 * Activation
		 */
		public function activate() {
			if ( ! class_exists( 'WC_Account_Funds_Installer' ) ) {
				include_once( 'includes/class-wc-account-funds-installer.php' );
			}
			WC_Account_Funds_Installer::install( $this->version );
			WC_Account_Funds_Installer::flush_rewrite_rules();
		}

		/**
		 * Add custom action links on the plugin screen.
		 *
		 * @param	mixed $actions Plugin Actions Links
		 * @return	array
		 */
		public function plugin_action_links( $actions ) {
			return array_merge( array(
				'docs'      => sprintf( '<a href="%s">%s</a>', 'https://docs.woocommerce.com/document/account-funds/', __( 'Docs', 'woocommerce-account-funds' ) ),
				'support'   => sprintf( '<a href="%s">%s</a>', 'https://support.woocommerce.com/', __( 'Support', 'woocommerce-account-funds' ) ),
				'changelog' => sprintf( '<a href="%s" target="_blank">%s</a>', 'https://woocommerce.com/changelogs/woocommerce-account-funds/changelog.txt', __( 'Changelog', 'woocommerce-account-funds' ) )
			), $actions );
		}

		/**
		 * Get a users funds amount
		 * @param  int  $user_id
		 * @param  boolean $formatted
		 * @return string
		 */
		public static function get_account_funds( $user_id = null, $formatted = true, $exclude_order_id = 0 ) {
			$user_id = $user_id ? $user_id : get_current_user_id();

			if ( $user_id ) {
				$funds = max( 0, get_user_meta( $user_id, 'account_funds', true ) );

				// Account for pending orders
				$orders_with_pending_funds = get_posts( array(
					'numberposts' => -1,
					'post_type'   => 'shop_order',
					'post_status' => array_keys( wc_get_order_statuses() ),
					'fields'      => 'ids',
					'meta_query'  => array(
						array(
							'key'   => '_customer_user',
							'value' => $user_id
						),
						array(
							'key'   => '_funds_removed',
							'value' => '0',
						),
						array(
							'key'     => '_funds_used',
							'value'   => '0',
							'compare' => '>'
						)
					)
				) );

				foreach ( $orders_with_pending_funds as $order_id ) {
					if ( null !== WC()->session && ! empty( WC()->session->order_awaiting_payment ) && $order_id == WC()->session->order_awaiting_payment ) {
						continue;
					}
					if ( $exclude_order_id === $order_id ) {
						continue;
					}
					$funds = $funds - floatval( get_post_meta( $order_id, '_funds_used', true ) );
				}
			} else {
				$funds = 0;
			}

			return $formatted ? wc_price( $funds ) : $funds;
		}

		/**
		 * Add funds to user account
		 * @param int $customer_id
		 * @param float $amount
		 */
		public static function add_funds( $customer_id, $amount ) {
			$funds = get_user_meta( $customer_id, 'account_funds', true );
			$funds = $funds ? $funds : 0;
			$funds += floatval( $amount );
			update_user_meta( $customer_id, 'account_funds', $funds );
		}

		/**
		 * Remove funds from user account
		 * @param int $customer_id
		 * @param float $amount
		 */
		public static function remove_funds( $customer_id, $amount ) {
			$funds = get_user_meta( $customer_id, 'account_funds', true );
			$funds = $funds ? $funds : 0;
			$funds = $funds - floatval( $amount );
			update_user_meta( $customer_id, 'account_funds', max( 0, $funds ) );
		}

		/**
		 * Register the gateway for use
		 */
		public function register_gateway( $methods ) {
			$methods[] = 'WC_Gateway_Account_Funds';
			return $methods;
		}

		/**
		 * Add scripts to checkout process
		 */
		public function checkout_scripts() {
			wp_enqueue_script( 'account_funds', plugins_url( 'assets/js/account-funds.js', WC_ACCOUNT_FUNDS_FILE ), array( 'jquery' ), true );
		}

		/**
		 * Add AF-related data stores.
		 *
		 * @since 2.1.3
		 *
		 * @version 2.1.3
		 *
		 * @param array $data_stores Data stores.
		 *
		 * @return array Data stores.
		 */
		public function add_data_stores( $data_stores ) {
			if ( ! class_exists( 'WC_Product_Topup_Data_Store' ) ) {
				require_once( 'includes/class-wc-product-topup-data-store.php' );
			}

			$data_stores['product-topup'] = 'WC_Product_Topup_Data_Store';

			return $data_stores;
		}
	}

	new WC_Account_Funds();
}
