<?php

namespace JP\UM\User;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class JP\UM\User\Codes
 */
class Codes {

	/**
	 * @return array
	 */
	public static function valid_codes() {
		return array(
			'avatar'       => __( 'Avatar', 'user-menus' ),
			'first_name'   => __( 'First Name', 'user-menus' ),
			'last_name'    => __( 'Last Name', 'user-menus' ),
			'username'     => __( 'Username', 'user-menus' ),
			'display_name' => __( 'Display Name', 'user-menus' ),
			'nickname'     => __( 'Nickname', 'user-menus' ),
			'email'        => __( 'Email', 'user-menus' ),
		);
	}

}
