<?php

namespace JP\UM\Site;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class JP\UM\Site\Menus
 */
class Menus {

	/**
	 * Init
	 */
	public static function init() {
		add_filter( 'wp_get_nav_menu_items', array( __CLASS__, 'exclude_menu_items' ) );
	}

	/**
	 * Exclude menu items via wp_get_nav_menu_items filter.
	 *
	 * Guarantees compatibility with nearly any theme.
	 */
	public static function exclude_menu_items( $items = array() ) {

		if ( empty( $items ) ) {
			return $items;
		}

		$logged_in = is_user_logged_in();

		$excluded = array();

		foreach ( $items as $key => $item ) {

			$exclude = in_array( $item->menu_item_parent, $excluded );

			if ( $item->object == 'logout' ) {
				$exclude = ! $logged_in;
			} elseif ( $item->object == 'login' ) {
				$exclude = $logged_in;
			} else {

				switch ( $item->which_users ) {

					case 'logged_in':
						if ( ! $logged_in ) {
							$exclude = true;
						} elseif ( ! empty( $item->roles ) ) {

							// Checks all roles, should not exclude if any are active.
							$valid_role = false;

							foreach ( $item->roles as $role ) {
								if ( current_user_can( $role ) ) {
									$valid_role = true;
									break;
								}
							}

							if ( ! $valid_role ) {
								$exclude = true;
							}
						}
						break;

					case 'logged_out':
						$exclude = $logged_in;
						break;

				}

			}

			$exclude = apply_filters( 'jpum_should_exclude_item', $exclude, $item );

			// unset non-visible item
			if ( $exclude ) {
				$excluded[] = $item->ID; // store ID of item
				unset( $items[ $key ] );
			}

		}

		return $items;
	}

}

Menus::init();
