<?php

namespace JP\UM\Admin;

use JP\UM\Menu\Item;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class JP\UM\Admin\Menu_Settings
 */
class Menu_Settings {

	/**
	 * Init
	 */
	public static function init() {
		add_action( 'wp_nav_menu_item_custom_fields', array( __CLASS__, 'fields' ), 10, 4 );
		add_action( 'wp_update_nav_menu_item', array( __CLASS__, 'save' ), 10, 2 );
	}

	/**
	 * @param $item_id
	 * @param $item
	 * @param $depth
	 * @param $args
	 */
	public static function fields( $item_id, $item, $depth, $args ) {

		$allowed_user_roles = static::allowed_user_roles();

		wp_nonce_field( 'jpum-menu-editor-nonce', 'jpum-menu-editor-nonce' ); ?>

		<p class="nav_item_options-avatar_size  description  description-wide">

			<label for="jp_nav_item_options-avatar_size-<?php echo $item->ID; ?>">

				<?php _e( 'Avatar Size', 'user-menus' ); ?><br />

				<input type="number" min="0" step="1" name="jp_nav_item_options[<?php echo $item->ID; ?>][avatar_size]" id="jp_nav_item_options-avatar_size-<?php echo $item->ID; ?>" value="<?php esc_attr_e( $item->avatar_size ); ?>" class="widefat  code" />

			</label>

		</p>


		<?php


		if ( in_array( $item->object, array( 'login', 'logout' ) ) ) :

			$redirect_types = array(
				'current' => __( 'Current Page', 'user-menus' ),
				'home'    => __( 'Home Page', 'user-menus' ),
				'custom'  => __( 'Custom URL', 'user-menus' ),
			); ?>

			<p class="nav_item_options-redirect_type  description  description-wide">

				<label for="jp_nav_item_options-redirect_type-<?php echo $item->ID; ?>">

					<?php _e( 'Where should users be taken afterwards?', 'user-menus' ); ?><br />

					<select name="jp_nav_item_options[<?php echo $item->ID; ?>][redirect_type]" id="jp_nav_item_options-redirect_type-<?php echo $item->ID; ?>" class="widefat">
						<?php foreach ( $redirect_types as $option => $label ) : ?>
							<option value="<?php echo $option; ?>" <?php selected( $option, $item->redirect_type ); ?>>
								<?php echo esc_html( $label ); ?>
							</option>
						<?php endforeach; ?>
					</select>

				</label>

			</p>

			<p class="nav_item_options-redirect_url  description  description-wide">

				<label for="jp_nav_item_options-redirect_url-<?php echo $item->ID; ?>">

					<?php _e( 'Enter a url user should be redirected to', 'user-menus' ); ?><br />

					<input type="text" name="jp_nav_item_options[<?php echo $item->ID; ?>][redirect_url]" id="jp_nav_item_options-redirect_url-<?php echo $item->ID; ?>" value="<?php esc_attr_e( $item->redirect_url ); ?>" class="widefat  code" />

				</label>

			</p>

		<?php else:

			$which_users_options = array(
				''           => __( 'Everyone', 'user-menus' ),
				'logged_out' => __( 'Logged Out Users', 'user-menus' ),
				'logged_in'  => __( 'Logged In Users', 'user-menus' ),
			); ?>

			<p class="nav_item_options-which_users  description  description-wide">

				<label for="jp_nav_item_options-which_users-<?php echo $item->ID; ?>">

					<?php _e( 'Who can see this link?', 'user-menus' ); ?><br />

					<select name="jp_nav_item_options[<?php echo $item->ID; ?>][which_users]" id="jp_nav_item_options-which_users-<?php echo $item->ID; ?>" class="widefat">
						<?php foreach ( $which_users_options as $option => $label ) : ?>
							<option value="<?php echo $option; ?>" <?php selected( $option, $item->which_users ); ?>>
								<?php echo esc_html( $label ); ?>
							</option>
						<?php endforeach; ?>
					</select>

				</label>

			</p>

			<p class="nav_item_options-roles  description  description-wide">

				<?php _e( 'Choose which roles can see this link', 'user-menus' ); ?><br />

				<?php foreach ( $allowed_user_roles as $option => $label ) : ?>
					<label>
						<input type="checkbox" name="jp_nav_item_options[<?php echo $item->ID; ?>][roles][]" value="<?php echo $option; ?>" <?php checked( in_array( $option, $item->roles ), true ); ?>/>
						<?php echo esc_html( $label ); ?>
					</label>
				<?php endforeach; ?>

			</p>

		<?php endif;
	}

	/**
	 * @return array|mixed|void
	 */
	public static function allowed_user_roles() {
		global $wp_roles;

		static $roles;

		if ( ! isset( $roles ) ) {
			$roles = apply_filters( 'jpum_user_roles', $wp_roles->role_names );

			if ( ! is_array( $roles ) || empty( $roles ) ) {
				$roles = array();
			}
		}

		return $roles;
	}

	/**
	 * @param $menu_id
	 * @param $item_id
	 */
	public static function save( $menu_id, $item_id ) {

		$allowed_roles = static::allowed_user_roles();

		if ( empty( $_POST['jp_nav_item_options'][ $item_id ] ) || ! isset( $_POST['jpum-menu-editor-nonce'] ) || ! wp_verify_nonce( $_POST['jpum-menu-editor-nonce'], 'jpum-menu-editor-nonce' ) ) {
			return;
		}

		$item_options = Item::parse_options( $_POST['jp_nav_item_options'][ $item_id ] );

		if ( $item_options['which_users'] == 'logged_in' ) {
			// Validate chosen roles and remove non-allowed roles.
			foreach ( (array) $item_options['roles'] as $key => $role ) {
				if ( ! array_key_exists( $role, $allowed_roles ) ) {
					unset( $item_options['roles'][ $key ] );
				}
			}
		} else {
			unset( $item_options['roles'] );
		}

		// Remove empty options to save space.
		$item_options = array_filter( $item_options );

		if ( ! empty( $item_options ) ) {
			update_post_meta( $item_id, '_jp_nav_item_options', $item_options );
		} else {
			delete_post_meta( $item_id, '_jp_nav_item_options' );
		}
	}

}

Menu_Settings::init();
