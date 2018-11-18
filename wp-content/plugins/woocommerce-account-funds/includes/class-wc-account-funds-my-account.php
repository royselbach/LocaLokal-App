<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Account_Funds_My_Account
 */
class WC_Account_Funds_My_Account extends WC_Query {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'add_endpoints' ) );
		add_filter( 'the_title', array( $this, 'change_endpoint_title' ), 11, 1 );

		if ( ! is_admin() ) {
			add_filter( 'query_vars', array( $this, 'add_query_vars' ), 0 );
			add_filter( 'woocommerce_get_breadcrumb', array( $this, 'add_breadcrumb' ), 10 );
			add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ), 11 );

			// Inserting your new tab/page into the My Account page.
			add_filter( 'woocommerce_account_menu_items', array( $this, 'add_menu_items' ) );
			add_action( 'woocommerce_account_account-funds_endpoint', array( $this, 'endpoint_content' ) );

			add_action( 'wp', array( $this, 'topup_handler' ) );

			if ( function_exists( 'WC' ) && version_compare( WC()->version, '2.6', '<' ) ) {
				add_action( 'woocommerce_before_my_account', array( $this, 'my_account' ) );
			}
		}

		$this->init_query_vars();
	}

	/**
	 * Init query vars by loading options.
	 *
	 * @since 2.0.12
	 */
	public function init_query_vars() {
		$this->query_vars = array(
			'account-funds' => get_option( 'woocommerce_myaccount_account_funds_endpoint', 'account-funds' ),
		);
	}

	/**
	 * Adds endpoint breadcrumb when viewing account funds.
	 *
	 * @since 2.0.12
	 *
	 * @param  array $crumbs already assembled breadcrumb data
	 * @return array $crumbs if we're on a account funds page, then augmented breadcrumb data
	 */
	public function add_breadcrumb( $crumbs ) {
		foreach ( $this->query_vars as $key => $query_var ) {
			if ( $this->is_query( $query_var ) ) {
				$crumbs[] = array( $this->get_endpoint_title( $key ) );
			}
		}

		return $crumbs;
	}

	/**
	 * Check if the current query is for a type we want to override.
	 *
	 * @since 2.0.12
	 *
	 * @param  string $query_var the string for a query to check for
	 * @return bool
	 */
	protected function is_query( $query_var ) {
		global $wp;

		$is_af_query = false;
		if ( is_main_query() && is_page() && isset( $wp->query_vars[ $query_var ] ) ) {
			$is_af_query = true;
		}

		return $is_af_query;
	}

	/**
	 * Get endpoint title.
	 *
	 * @since 2.0.12
	 *
	 * @param  string $endpoint Endpoint name
	 * @return string           Endpoint title
	 */
	public function get_endpoint_title( $endpoint ) {
		$title = '';
		if ( 'account-funds' === $endpoint ) {
			$title = __( 'Account Funds', 'woocommerce-account-funds' );
		}

		return $title;
	}

	/**
	 * Changes page title on account funds page.
	 *
	 * @since 2.0.12
	 *
	 * @param  string $title original title
	 * @return string        changed title
	 */
	public function change_endpoint_title( $title ) {
		if ( in_the_loop() ) {
			foreach ( $this->query_vars as $key => $query_var ) {
				if ( $this->is_query( $query_var ) ) {
					$title = $this->get_endpoint_title( $key );
				}
			}
		}
		return $title;
	}


	/**
	 * Insert the new endpoint into the My Account menu.
	 *
	 * @since 2.0.12
	 *
	 * @param array $items
	 * @return array
	 */
	public function add_menu_items( $menu_items ) {
		// Try insert after orders.
		if ( isset( $menu_items['orders'] ) ) {
			$new_menu_items = array();
			foreach ( $menu_items as $key => $menu ) {
				$new_menu_items[ $key ] = $menu;
				if ( 'orders' === $key ) {
					$new_menu_items['account-funds'] = __( 'Account Funds', 'woocommerce-account-funds' );
				}
			}
			$menu_items = $new_menu_items;
		} else {
			$menu_items['account-funds'] = __( 'Account Funds', 'woocommerce-account-funds' );
		}

		return $menu_items;
	}

	/**
	 * Endpoint HTML content.
	 *
	 * @since 2.0.12
	 */
	public function endpoint_content() {
		$topup    = '';
		$products = '';
		if ( 'yes' === get_option( 'account_funds_enable_topup' ) ) {
			$topup = $this->get_my_account_topup();
		} else {
			$products = $this->get_my_account_products();
		}

		$recent_deposits = $this->get_my_account_orders();

		$vars = array(
			'funds'           => WC_Account_Funds::get_account_funds(),
			'topup'           => $topup,
			'products'        => $products,
			'recent_deposits' => $recent_deposits,
		);

		wc_get_template( 'myaccount/account-funds.php', $vars, '', plugin_dir_path( WC_ACCOUNT_FUNDS_FILE ) . 'templates/' );
	}

	/**
	 * Fix for endpoints on the homepage
	 *
	 * Based on WC_Query->pre_get_posts(), but only applies the fix for endpoints on the homepage from it
	 * instead of duplicating all the code to handle the main product query.
	 *
	 * @since 2.0.12
	 *
	 * @param mixed $q query object
	 */
	public function pre_get_posts( $q ) {
		// We only want to affect the main query
		if ( ! $q->is_main_query() ) {
			return;
		}

		if ( $q->is_home() && 'page' === get_option( 'show_on_front' ) && absint( get_option( 'page_on_front' ) ) !== absint( $q->get( 'page_id' ) ) ) {
			$_query = wp_parse_args( $q->query );
			if ( ! empty( $_query ) && array_intersect( array_keys( $_query ), array_keys( $this->query_vars ) ) ) {
				$q->is_page     = true;
				$q->is_home     = false;
				$q->is_singular = true;
				$q->set( 'page_id', (int) get_option( 'page_on_front' ) );
				add_filter( 'redirect_canonical', '__return_false' );
			}
		}
	}
	/**
	 * Handle top-ups
	 */
	public function topup_handler() {
		if ( isset( $_POST['wc_account_funds_topup'] ) && isset( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'account-funds-topup' ) ) {
			$min          = max( 0, get_option( 'account_funds_min_topup' ) );
			$max          = get_option( 'account_funds_max_topup' );
			$topup_amount = wc_clean( $_POST['topup_amount'] );

			if ( $topup_amount < $min ) {
				wc_add_notice( sprintf( __( 'The minimum amount that can be topped up is %s', 'woocommerce-account-funds' ), wc_price( $min ) ), 'error' );
				return;
			} elseif ( $max && $topup_amount > $max ) {
				wc_add_notice( sprintf( __( 'The maximum amount that can be topped up is %s', 'woocommerce-account-funds' ), wc_price( $max ) ), 'error' );
				return;
			}

			WC()->cart->add_to_cart( wc_get_page_id( 'myaccount' ), true, '', '', array( 'top_up_amount' => $topup_amount ) );
			
			if ( 'yes' === get_option( 'woocommerce_cart_redirect_after_add' ) ) {
				wp_redirect( get_permalink( wc_get_page_id( 'cart' ) ) );
			}
		}
	}

	/**
	 * Show funds on account page
	 */
	public function my_account() {
		$funds = WC_Account_Funds::get_account_funds();

		echo '<h2>'. __( 'Account Funds', 'woocommerce-account-funds' ) .'</h2>';
		echo '<p>'. sprintf( __( 'You currently have <strong>%s</strong> worth of funds in your account.', 'woocommerce-account-funds' ), $funds ) . '</p>';

		if ( 'yes' === get_option( 'account_funds_enable_topup' ) ) {
			$this->my_account_topup();
		} else {
			$this->my_account_products();
		}

		$this->my_account_orders();
	}

	/**
	 * Get HTML string for topup form in my account.
	 *
	 * @since 2.0.12
	 *
	 * @return string HTML string
	 */
	public function get_my_account_topup() {
		ob_start();
		$this->my_account_topup();
		return ob_get_clean();
	}

	/**
	 * Show top up form
	 */
	public function my_account_topup() {
		$min_topup     = get_option( 'account_funds_min_topup' );
		$max_topup     = get_option( 'account_funds_max_topup' );
		$items_in_cart = $this->_get_topup_items_in_cart();
		$topup_in_cart = array_shift( $items_in_cart );
		if ( ! empty( $max_topup ) && ! empty( $topup_in_cart ) ) {
			printf(
				'<p class="woocommerce-info"><a href="%s" class="button wc-forward">%s</a> %s</p>',
				wc_get_page_permalink( 'cart' ),
				__( 'View Cart', 'woocommerce-account-funds' ),
				sprintf( __( 'You have "%s" in your cart.', 'woocommerce-account-funds' ), $topup_in_cart['data']->get_title() )
			);
			return;
		}

		$vars = array(
			'min_topup' => $min_topup,
			'max_topup' => $max_topup,
		);

		wc_get_template( 'myaccount/topup-form.php', $vars, '', plugin_dir_path( WC_ACCOUNT_FUNDS_FILE ) . 'templates/' );
	}

	/**
	 * Get topup items in cart.
	 *
	 * @since 2.0.6
	 *
	 * @return bool
	 */
	private function _get_topup_items_in_cart() {
		$topup_items = array();
		if ( ! WC()->cart->is_empty() ) {
			$topup_items = array_filter( WC()->cart->get_cart(), array( $this, 'filter_topup_items' ) );
		}

		return $topup_items;
	}

	/**
	 * Cart items filter callback to filter topup product.
	 *
	 * @since 2.0.6
	 *
	 * @return bool Returns true if item is topup product
	 */
	public function filter_topup_items( $item ) {
		if ( isset( $item['data'] ) && is_callable( array( $item['data'], 'get_type' ) ) ) {
			return ( 'topup' === $item['data']->get_type() );
		}

		return false;
	}

	/**
	 * Show top up products
	 */
	private function my_account_products() {
		$product_ids = get_posts( array(
			'post_type' => 'product',
			'tax_query' => array(
				array(
					'taxonomy' => 'product_type',
					'field'    => 'slug',
					'terms'    => 'deposit',
				)
			),
			'fields' => 'ids'
		) );
		if ( $product_ids ) {
			echo do_shortcode( '[products ids="' . implode( ',', $product_ids ) . '"]' );
		}
	}

	/**
	 * Get HTML string of deposit products in my account page.
	 *
	 * @since 2.0.12
	 *
	 * @return string HTML string
	 */
	private function get_my_account_products() {
		ob_start();
		$this->my_account_products();
		return ob_get_clean();
	}

	/**
	 * Show deposits
	 */
	private function my_account_orders() {
		$deposits = get_posts( array(
			'numberposts' => 10,
			'meta_key'    => '_customer_user',
			'meta_value'  => get_current_user_id(),
			'post_type'   => 'shop_order',
			'post_status' => array( 'wc-completed', 'wc-processing', 'wc-on-hold' ),
			'meta_query'  => array(
				array(
					'key'   => '_funds_deposited',
					'value' => '1',
				)
			)
		) );

		if ( $deposits ) {
			$vars = array(
				'deposits' => $deposits,
			);
			wc_get_template( 'myaccount/recent-deposits.php', $vars, '', plugin_dir_path( WC_ACCOUNT_FUNDS_FILE ) . 'templates/' );
		}
	}

	/**
	 * Get HTML string of recent deposits.
	 *
	 * @since 2.0.12
	 *
	 * @return string HTML string
	 */
	private function get_my_account_orders() {
		ob_start();
		$this->my_account_orders();
		return ob_get_clean();
	}
}

new WC_Account_Funds_My_Account();
