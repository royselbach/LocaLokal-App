<?php
/**
 * Elementor Meta import handler.
 *
 * This is needed because by default, the importer breaks our JSON meta.
 *
 * @package    themeisle-onboarding
 * @soundtrack All Apologies (Live) - Nirvana
 */

/**
 * Class Themeisle_OB_Elementor_Meta_Handler
 *
 * @package ThemeIsle
 */
class Themeisle_OB_Elementor_Meta_Handler {
	/**
	 * Elementor meta key.
	 *
	 * @var string
	 */
	private $meta_key = '_elementor_data';

	/**
	 * Meta value.
	 *
	 * @var null
	 */
	private $value = null;

	/**
	 * Themeisle_OB_Elementor_Meta_Handler constructor.
	 *
	 * @param string $unfiltered_value the unfiltered meta value.
	 */
	public function __construct( $unfiltered_value ) {
		$this->value = $unfiltered_value;
	}

	/**
	 * Filter the meta to allow escaped JSON values.
	 */
	public function filter_meta() {
		add_filter( 'sanitize_post_meta_' . $this->meta_key, array( $this, 'allow_escaped_json_meta' ), 10, 3 );
	}

	/**
	 * Allow JSON escaping.
	 *
	 * @param string $val  meta value.
	 * @param string $key  meta key.
	 * @param string $type meta type.
	 *
	 * @return array|string
	 */
	public function allow_escaped_json_meta( $val, $key, $type ) {
		if ( empty( $this->value ) ) {
			return $val;
		}

		$this->replace_urls();

		return $this->value;
	}

	/**
	 * Replace demo urls in meta with site urls.
	 */
	private function replace_urls() {
		$string = str_replace( '\\', '', $this->value );
		$urls   = wp_extract_urls( $string );

		array_walk( $urls, function ( $item ) {
			$old_url = $item;
			$item    = parse_url( $item );
			if ( ! isset( $item['host'] ) ) {
				return;
			}
			if ( $item['host'] !== 'demo.themeisle.com' ) {
				return;
			}
			$uploads_dir  = wp_get_upload_dir();
			$uploads_url  = $uploads_dir['baseurl'];
			$item['path'] = preg_split( '/\//', $item['path'] );
			$item['path'] = array_slice( $item['path'], - 3 );

			$item = array(
				'old_url' => str_replace( '/', '\\/', $old_url ),
				'new_url' => str_replace( '/', '\\/', esc_url( $uploads_url . '/' . join( '/', $item['path'] ) ) ),
			);

			$this->value = str_replace( $item['old_url'], $item['new_url'], $this->value );
		} );
	}
}
