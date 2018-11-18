<?php

namespace JP\UM\Admin;

use JP\UM\User\Codes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class JP\UM\Admin\Menu_Editor
 */
class Menu_Editor {

	/**
	 * Init
	 */
	public static function init() {
		add_filter( 'wp_edit_nav_menu_walker', array( __CLASS__, 'nav_menu_walker' ), 999999999 );
		add_action( 'admin_head-nav-menus.php', array( __CLASS__, 'register_metaboxes' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
	}

	/**
	 * Override the Admin Menu Walker
	 */
	public static function nav_menu_walker( $walker ) {
		global $wp_version;

		if ( doing_filter( 'plugins_loaded' ) ) {
			return $walker;
		}

		if ( $walker == 'Walker_Nav_Menu_Edit_Custom_Fields' ) {
			return $walker;
		}

		if ( ! class_exists( 'Walker_Nav_Menu_Edit_Custom_Fields' ) ) {
			if ( version_compare( $wp_version, '3.6', '>=' ) ) {
				require_once \JP_User_Menus::$DIR . 'includes/classes/walker/nav-menu-edit-custom-fields.php';
			} else {
				require_once \JP_User_Menus::$DIR . 'includes/classes/walker/nav-menu-edit-custom-fields-deprecated.php';
			}
		}

		return 'Walker_Nav_Menu_Edit_Custom_Fields';
	}


	/**
	 *
	 */
	public static function register_metaboxes() {
		add_meta_box( 'jp_user_menus', __( 'User Links', 'user-menus' ), array( __CLASS__, 'nav_menu_metabox', ), 'nav-menus', 'side', 'default' );
	}

	/**
	 * @param $object
	 */
	public static function nav_menu_metabox( $object ) {
		global $_nav_menu_placeholder, $nav_menu_selected_id;

		$link_types = array(
			array(
				'object' => 'login',
				'title'  => __( 'Login', 'user-menus' ),
			),
			array(
				'object' => 'logout',
				'title'  => __( 'Logout', 'user-menus' ),
			),
		);

		foreach ( $link_types as $key => $link ) {

			$i = isset( $i ) ? $i + 1 : 1;

			$link_types[ $key ] = (object) array_replace_recursive( array(
				'type'             => '',
				'object'           => '',
				'title'            => '',
				'ID'               => $i,
				'object_id'        => $i,
				'db_id'            => 0,
				'post_parent'      => 0,
				'menu_item_parent' => 0,
				'url'              => '',
				'target'           => '',
				'attr_title'       => '',
				'description'      => '',
				'classes'          => array(),
				'xfn'              => '',
			), $link );

		}

		$walker = new \Walker_Nav_Menu_Checklist;

		$removed_args = array(
			'action',
			'customlink-tab',
			'edit-menu-item',
			'menu-item',
			'page-tab',
			'_wpnonce',
		);

		?>

		<div id="user-menus-div" class="user-menus">
			<div id="tabs-panel-user-menus-all" class="tabs-panel tabs-panel-active">
				<ul id="user-menus-checklist-all" class="categorychecklist form-no-clear">
					<?php echo walk_nav_menu_tree( array_map( 'wp_setup_nav_menu_item', $link_types ), 0, (object) array( 'walker' => $walker ) ); ?>
				</ul>

				<p class="button-controls">
					<span class="list-controls">
						<a href="<?php
						echo esc_url( add_query_arg( array(
							'user-menus-all' => 'all',
							'selectall'      => 1,
						), remove_query_arg( $removed_args ) ) );
						?>#user-menus-div" class="select-all"><?php _e( 'Select All' ); ?></a>
					</span>

					<span class="add-to-menu">
						<input type="submit"<?php wp_nav_menu_disabled_check( $nav_menu_selected_id ); ?> class="button-secondary submit-add-to-menu right" value="<?php esc_attr_e( 'Add to Menu' ); ?>" name="add-user-menus-menu-item" id="submit-user-menus-div" />
						<span class="spinner"></span>
					</span>
				</p>
			</div>
		</div>

		<?php

	}

	/**
	 * @param $hook
	 */
	public static function enqueue_scripts( $hook ) {
		if ( $hook != 'nav-menus.php' ) {
			return;
		}

		add_action( 'admin_footer', array( __CLASS__, 'media_templates' ) );

		// Use minified libraries if SCRIPT_DEBUG is turned off
		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_enqueue_script( 'jpum-scripts', \JP_User_Menus::$URL . 'assets/scripts/admin' . $suffix . '.js', array( 'jquery', 'underscore' ), \JP_User_Menus::$VER, true );
		wp_enqueue_style( 'jpum-styles', \JP_User_Menus::$URL . 'assets/styles/admin' . $suffix . '.css', array( 'dashicons' ), \JP_User_Menus::$VER );
	}

	/**
	 *
	 */
	public static function media_templates() { ?>
		<script type="text/html" id="tmpl-jpum-user-codes">
			<div class="jpum-user-codes">
				<button type="button" title="<?php _e( 'Insert User Menu Codes', 'user-menus' ); ?>">
					<i class="dashicons dashicons-arrow-left"></i>
				</button>
				<ul>
					<?php foreach ( Codes::valid_codes() as $code => $label ) : ?>
						<li>
							<a title="<?php echo $label; ?>" href="#" data-code="<?php echo $code; ?>">
								<?php echo $label; ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</script>
		<?php
	}
}

Menu_Editor::init();
