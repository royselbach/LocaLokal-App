<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

class DN_Email {

	static $instance = null;

	// set email from
	public function set_email_from( $email ) {
		if ( $donate_email = DN_Settings::instance()->email->get( 'admin_email' ) ) {
			return $donate_email;
		}

		return $email;
	}

	// set email name header
	public function set_email_name( $name ) {
		if ( $donate_name = DN_Settings::instance()->email->get( 'from_name' ) ) {
			return sanitize_title( $donate_name );
		}

		return $name;
	}

	// filter content type
	public function email_content_type( $type ) {
		return 'text/html';
	}

	// filter charset
	public function email_charset( $chartset ) {
		return 'UTF-8';
	}

	// send email donate completed
	public function send_email_donate_completed( $donor = null ) {
		if ( $this->is_enable() !== true ) {
			return;
		}

		// email template
		$email_template = DN_Settings::instance()->email->get( 'email_template' ) ? DN_Settings::instance()->email->get( 'email_template' ) : '';
		$email          = $donor->get_meta( 'email' );
		if ( $email ) {
			$subject = __( 'Donate completed', 'fundpress' );

			$replace = apply_filters( 'donate_completed_mail_replace', array(
				'/\[(.*?)donor_first_name(.*?)\]/i',
				'/\[(.*?)donor_last_name(.*?)\]/i',
				'/\[(.*?)donor_phone(.*?)\]/i',
				'/\[(.*?)donor_email(.*?)\]/i',
				'/\[(.*?)donor_address(.*?)\]/i'
			) );

			$replace_with = apply_filters( 'donate_completed_mail_replace_with', array(
					$donor->get_meta( 'first_name' ),
					$donor->get_meta( 'last_name' ),
					$donor->get_meta( 'phone' ),
					$donor->get_meta( 'email' ),
					$donor->get_meta( 'address' )
				)
			);

			ob_start();
			echo preg_replace( $replace, $replace_with, $email_template );
			$body = ob_get_clean();

			// filter email setting
			add_filter( 'wp_mail_from', array( $this, 'set_email_from' ) );
			// filter email from name
			add_filter( 'wp_mail_from_name', array( $this, 'set_email_name' ) );
			// filter content type
			add_filter( 'wp_mail_content_type', array( $this, 'email_content_type' ) );
			// filter charset
			add_filter( 'wp_mail_charset', array( $this, 'email_charset' ) );

			wp_mail( $email, $subject, $body );

			// filter email setting
			remove_filter( 'wp_mail_from', array( $this, 'set_email_from' ) );
			// filter email from name
			remove_filter( 'wp_mail_from_name', array( $this, 'set_email_name' ) );
			// filter content type
			remove_filter( 'wp_mail_content_type', array( $this, 'email_content_type' ) );
			// filter charset
			remove_filter( 'wp_mail_charset', array( $this, 'email_charset' ) );
		}
	}

	public function is_enable() {
		if ( DN_Settings::instance()->email->get( 'enable', 'yes' ) === 'yes' ) {
			return true;
		}
	}

	// instance
	public static function instance() {
		if ( ! self::$instance ) {
			return self::$instance = new self();
		}

		return self::$instance;
	}

}

DN_Email::instance();
