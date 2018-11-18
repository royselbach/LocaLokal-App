<?php
/**
 * ThemeIsle - About page class
 *
 * @package ti-about-page
 */

/**
 * Class Ti_About_Page_Main
 *
 * @package Themeisle
 */
class Ti_About_Page {

	/**
	 * Current theme args
	 */
	private $theme_args = array();

	/**
	 * About page content that should be rendered
	 */
	private $config = array();

	/**
	 * About Page instance
	 */
	private static $instance;

	/**
	 * The Main Themeisle_About_Page instance.
	 *
	 * We make sure that only one instance of Themeisle_About_Page exists in the memory at one time.
	 *
	 * @param array $config The configuration array.
	 */
	public static function init( $config ) {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Ti_About_Page ) ) {
			self::$instance = new Ti_About_Page();
			if ( ! empty( $config ) && is_array( $config ) ) {
				self::$instance->config = apply_filters( 'ti_about_config_filter', $config );
				self::$instance->setup_config();
				self::$instance->setup_actions();
				self::$instance->set_recommended_plugins_visibility();
			}
		}
	}

	/**
	 * Setup the class props based on current theme
	 */
	private function setup_config() {

		$theme = wp_get_theme();

		$this->theme_args['name']        = $theme->__get( 'Name' );
		$this->theme_args['version']     = $theme->__get( 'Version' );
		$this->theme_args['description'] = $theme->__get( 'Description' );
		$this->theme_args['slug']        = $theme->__get( 'stylesheet' );

		$default = array(
			'type' => 'default',
			'render_callback' => array( $this, 'render_notice' ),
			'dismiss_option' => 'ti_about_welcome_notice',
			'notice_class' => '',
		);

		if ( isset( $this->config['welcome_notice'] ) ) {
			$this->config['welcome_notice'] = wp_parse_args( $this->config['welcome_notice'], $default );
		}
	}

	/**
	 * Setup the actions used for this page.
	 */
	public function setup_actions() {

		add_action( 'admin_menu', array( $this, 'register' ) );
		add_action( 'admin_notices', array( $this, 'welcome_notice' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_notice_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action(
			'wp_ajax_update_recommended_plugins_visibility', array(
				$this,
				'update_recommended_plugins_visibility',
			)
		);
		add_action( 'wp_ajax_dismiss_welcome_notice', array( $this, 'dismiss_welcome_notice' ) );
	}

	/**
	 * Register the menu page under Appearance menu.
	 */
	public function register() {
		$theme = $this->theme_args;

		if ( empty( $theme['name'] ) || empty( $theme['slug'] ) ) {
			return;
		}

		$page_title = __( 'About', 'hestia' ) . ' ' . $theme['name'] . ' ';

		$menu_name        = __( 'About', 'hestia' ) . ' ' . $theme['name'] . ' ';
		$required_actions = $this->get_recommended_actions_left();
		if ( $required_actions > 0 ) {
			$menu_name .= '<span class="badge-action-count update-plugins">' . esc_html( $required_actions ) . '</span>';
		}

		add_theme_page(
			$page_title,
			$menu_name,
			'activate_plugins',
			$theme['slug'] . '-welcome',
			array(
				$this,
				'render',
			)
		);
	}

	/**
	 * Instantiate the render class which will render all the tabs based on config
	 */
	public function render() {
		require_once 'includes/class-ti-about-render.php';
		new TI_About_Render( $this->theme_args, $this->config, $this );
	}

	/**
	 * Load css and scripts for the about page
	 */
	public function enqueue() {
		$screen = get_current_screen();

		if ( ! isset( $screen->id ) ) {
			return;
		}

		if ( $screen->id !== 'appearance_page_' . $this->theme_args['slug'] . '-welcome' ) {
			return;
		}

		wp_enqueue_style( 'ti-about-style', TI_ABOUT_PAGE_URL . '/css/style.css', array(), TI_ABOUT_PAGE_VERSION );

		wp_register_script(
			'ti-about-scripts', TI_ABOUT_PAGE_URL . '/js/ti_about_page_scripts.js', array(
			'jquery',
			'jquery-ui-tabs',
		), TI_ABOUT_PAGE_VERSION, true
		);

		wp_localize_script(
			'ti-about-scripts',
			'tiAboutPageObject',
			array(
				'nr_actions_required' => $this->get_recommended_actions_left(),
				'ajaxurl'             => admin_url( 'admin-ajax.php' ),
				'template_directory'  => get_template_directory_uri(),
				'activating_string'   => esc_html__( 'Activating', 'hestia' ),
			)
		);

		wp_enqueue_script( 'ti-about-scripts' );
		Ti_About_Plugin_Helper::instance()->enqueue_scripts();
	}

	/**
	 * Utility function for checking the number of recommended actions uncompleted
	 *
	 * @return int $actions_left - the number of uncompleted recommended actions.
	 */
	public function get_recommended_actions_left() {

		$nb_of_actions       = 0;
		$actions_left        = 0;
		$recommended_plugins = get_option( 'ti_about_recommended_plugins' );

		if ( ! empty( $recommended_plugins ) ) {
			foreach ( $recommended_plugins as $slug => $visibility ) {
				if ( $recommended_plugins[ $slug ] === 'visible' ) {
					$nb_of_actions += 1;

					if ( Ti_About_Plugin_Helper::instance()->check_plugin_state( $slug ) !== 'deactivate' ) {
						$actions_left += 1;
					}
				}
			}
		}

		return $actions_left;
	}

	/**
	 * Get the list of recommended plugins
	 *
	 * @return array - either recommended plugins or empty array.
	 */
	public function get_recommended_plugins() {
		foreach ( $this->config as $index => $content ) {
			if ( isset( $content['type'] ) && $content['type'] === 'recommended_actions' ) {
				$plugins = $content['plugins'];

				return $plugins;
				break;
			}
		}

		return array();
	}

	/**
	 * Set an option with recommended plugins slugs and visibility
	 * Based on visibility flag the plugin should be shown/hidden in recommended_plugins tab
	 */
	public function set_recommended_plugins_visibility() {

		$recommended_plugins_option = get_option( 'ti_about_recommended_plugins' );
		if ( ! empty( $recommended_plugins_option ) ) {
			return;
		}

		$required_plugins           = $this->get_recommended_plugins();
		$required_plugins_visbility = array();
		foreach ( $required_plugins as $slug => $req_plugin ) {
			$required_plugins_visbility[ $slug ] = 'visible';
		}

		update_option( 'ti_about_recommended_plugins', $required_plugins_visbility );
	}

	/**
	 * Update recommended plugins visibility flag if the user dismiss one of them
	 */
	public function update_recommended_plugins_visibility() {

		$recommended_plugins = get_option( 'ti_about_recommended_plugins' );

		$plugin_to_update                         = $_POST['slug'];
		$recommended_plugins[ $plugin_to_update ] = 'hidden';

		update_option( 'ti_about_recommended_plugins', $recommended_plugins );

		$required_actions_left = array( 'required_actions' => $this->get_recommended_actions_left() );
		wp_send_json( $required_actions_left );
	}

	/**
	 * Display default or custom welcome notice, based on config and current user
	 */
	public function welcome_notice() {

		/**
		 * Handle edge case for Zerif
		 */
		if ( defined( 'ZERIF_VERSION' ) || defined( 'ZERIF_LITE_VERSION' ) ) {
			if ( get_option( 'zelle_notice_dismissed' ) === 'yes' ) {
				return;
			}
		}

		if ( ! isset( $this->config['welcome_notice'] ) ) {
			return;
		}

		global $current_user;
		$user_id = $current_user->ID;
		$dismissed_notice = get_user_meta( $user_id, $this->config['welcome_notice']['dismiss_option'], true );
		if ( $dismissed_notice === 'dismissed' ) {
			return;
		}

		echo '<div class="' . esc_attr( $this->config['welcome_notice']['notice_class'] ) . ' notice is-dismissible ti-about-notice">';
		call_user_func( $this->config['welcome_notice']['render_callback'] );
		echo '</div>';
	}

	/**
	 * Render the default welcome notice
	 */
	public function render_notice() {
		$url = admin_url( 'themes.php?page=' . $this->theme_args['slug'] . '-welcome' );
		$notice = apply_filters( 'ti_about_welcome_notice_filter', ( '<p>' . sprintf( 'Welcome! Thank you for choosing %1$s! To fully take advantage of the best our theme can offer please make sure you visit our %2$swelcome page%3$s.', $this->theme_args['name'], '<a href="' . esc_url( admin_url( 'themes.php?page=' . $this->theme_args['slug'] . '-welcome' ) ) . '">', '</a>' ) . '</p><p><a href="' . esc_url( $url ) . '" class="button" style="text-decoration: none;">' . sprintf( 'Get started with %s', $this->theme_args['name'] ) . '</a></p>' ) );

		echo wp_kses_post( $notice );
	}

	/**
	 * Dismiss welcome notice
	 */
	public function dismiss_welcome_notice() {

		$params = $_REQUEST;
		global $current_user;
		$user_id = $current_user->ID;

		if ( ! isset( $params['nonce'] ) || ! wp_verify_nonce( $params['nonce'], 'dismiss_ti_about_notice' ) ) {
			wp_send_json_error( 'Wrong nonce' );
		}
		add_user_meta( $user_id, $this->config['welcome_notice']['dismiss_option'], 'dismissed', true );
		wp_send_json_success( 'Dismiss notice' );
	}

	/**
	 * Welcome notice scripts
	 */
	public function enqueue_notice_scripts() {
		wp_enqueue_script(
			'ti-about-notice-scripts',
			TI_ABOUT_PAGE_URL . '/js/ti_about_notice_scripts.js',
			array(),
			TI_ABOUT_PAGE_VERSION,
			true
		);
		wp_localize_script(
			'ti-about-notice-scripts',
			'tiAboutNotice',
			array(
				'ajaxurl'      => admin_url( 'admin-ajax.php' ),
				'dismissNonce' => wp_create_nonce( 'dismiss_ti_about_notice' ),
			)
		);
	}
}
