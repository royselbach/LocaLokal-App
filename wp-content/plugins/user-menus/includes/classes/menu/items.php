<?php

namespace JP\UM\Menu;

use JP\UM\User\Codes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class JP\UM\Menu\Items
 */
class Items {

	private static $current_item;

	/**
	 * Init
	 */
	public static function init() {
		add_filter( 'wp_setup_nav_menu_item', array( __CLASS__, 'merge_item_data' ) );
	}

	/**
	 * Merge Item data into the $item object.
	 *
	 * @param $item
	 *
	 * @return mixed
	 */
	public static function merge_item_data( $item ) {

		self::$current_item = $item;

		// Merge Rules.
		foreach ( Item::get_options( $item->ID ) as $key => $value ) {
			$item->$key = $value;
		}

		if ( in_array( $item->object, array( 'login', 'logout' ) ) ) {

			$item->type_label = __( 'User Link', 'user-menus' );

			switch ( $item->redirect_type ) {
				case 'current':
					$redirect = static::current_url();
					break;

				case 'home':
					$redirect = home_url();
					break;

				case 'custom':
					$redirect = $item->redirect_url;
					break;

				default:
					$redirect = '';
					break;
			}

			$item->url = $item->object == 'logout' ? wp_logout_url( $redirect ) : wp_login_url( $redirect );

		}

		// User text replacement.
		if ( ! is_admin() ) {
			$item->title = static::user_titles( $item->title );
		}


		return $item;
	}

	/**
	 * @return string
	 */
	public static function current_url() {
		$protocol = ( ! empty( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] !== 'off' ) || $_SERVER['SERVER_PORT'] == 443 ? 'https://' : 'http://';

		return $protocol . $_SERVER['HTTP_HOST'] . $_SERVER["REQUEST_URI"];
	}

	/**
	 * @param string $title
	 *
	 * @return mixed|string
	 */
	public static function user_titles( $title = '' ) {

		preg_match_all( '/{(.*?)}/', $title, $found );

		if ( count( $found[1] ) ) {

			foreach ( $found[1] as $key => $match ) {

				$title = static::text_replace( $title, $match );

			}
		}

		return $title;

	}

	/**
	 * @param string $title
	 * @param string $match
	 *
	 * @return mixed|string
	 */
	public static function text_replace( $title = '', $match = '' ) {

		if ( empty( $match ) ) {
			return $title;
		}

		if ( strpos( $match, '||' ) !== false ) {
			$matches = explode( '||', $match );
		} else {
			$matches = array( $match );
		}

		$current_user = wp_get_current_user();

		$replace = '';

		foreach ( $matches as $string ) {

			if ( $current_user->ID == 0 || ! array_key_exists( $string, Codes::valid_codes() ) ) {

				$replace = '';

			} else {

				switch ( $string ) {

					case 'avatar':
						$replace = get_avatar( $current_user, self::$current_item->avatar_size );
						break;

					case 'first_name':
						$replace = $current_user->user_firstname;
						break;

					case 'last_name':
						$replace = $current_user->user_lastname;
						break;

					case 'username':
						$replace = $current_user->user_login;
						break;

					case 'display_name':
						$replace = $current_user->display_name;
						break;

					case 'nickname':
						$replace = $current_user->nickname;
						break;

					case 'email':
						$replace = $current_user->user_email;
						break;

					default:
						$replace = $string;
						break;

				}

			}

			// If we found a replacement stop the loop.
			if ( ! empty( $replace ) ) {
				break;
			}

		}

		return str_replace( '{' . $match . '}', $replace, $title );
	}

}

Items::init();
