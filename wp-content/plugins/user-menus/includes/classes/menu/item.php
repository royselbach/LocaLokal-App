<?php

namespace JP\UM\Menu;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class JP\UM\Menu\Item
 */
class Item {

	/**
	 * @param int $item_id
	 *
	 * @return array
	 */
	public static function get_options( $item_id = 0 ) {

		// Fetch all rules for this menu item.
		$item_options = get_post_meta( $item_id, '_jp_nav_item_options', true );

		return static::parse_options( $item_options );
	}

	/**
	 * @param array $options
	 *
	 * @return array
	 */
	public static function parse_options( $options = array() ) {

		if ( ! is_array( $options ) ) {
			$options = array();
		}

		return wp_parse_args( $options, array(
			'avatar_size'   => 24,
			'redirect_type' => 'current',
			'redirect_url'  => '',
			'which_users'   => '',
			'roles'         => array(),
		) );
	}

}
