<?php
/**
 * Class used to import zerif frontpage sections to an Hestia template.
 *
 * @package hestia
 * @since 1.1.86
 */

/**
 * Class Hestia_Import_Zerif_Frontpage
 */
class Hestia_Import_Zerif_Frontpage extends Hestia_Import_Utilities {

	/**
	 * The page template name.
	 *
	 * @var string
	 */
	protected $name = 'Zerif Frontpage';

	/**
	 * The url from where we fetch the Elementor template.
	 *
	 * @var string
	 */
	protected $json_template_url = 'https://raw.githubusercontent.com/Codeinwp/obfx-templates/master/zerif-elementor/template.json';

	/**
	 * Preview theme content.
	 *
	 * @var array
	 */
	protected $previous_theme_content = array();

	/**
	 * Content.
	 *
	 * @var array
	 */
	protected $content = array();

	/**
	 * A property where we cache the content.
	 *
	 * @var array
	 */
	protected $default_content = array();

	/**
	 * Hestia_Import_Zerif_Frontpage constructor.
	 *
	 * @access public
	 * @since 1.1.86
	 */
	public function __construct() {
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		add_action( 'wp_ajax_import_zerif_frontpage', array( $this, 'wp_ajax_import_zerif_frontpage' ) );
		add_action( 'wp_ajax_dismiss_zerif_import', array( $this, 'wp_ajax_dismiss_zerif_import' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_notice_scripts' ) );
	}

	/**
	 * Adds a dashboard notification which allows the user to install Elementor in case it's missing and
	 * convert the old Zerif front-page sections into a template.
	 */
	public function admin_notices() {

		// This notice should appear only for admin users.
		if (
			! is_admin()
			&& current_user_can( 'install_plugins' )
			&& current_user_can( 'activate_plugins' )
		) {
			return;
		}

		$installer_helper = Hestia_Plugin_Install_Helper::instance();
		$installer_helper->enqueue_scripts();

		echo '<div class="notice notice-success is-dismissible hestia-import-zerif">';

		echo '<p>' . esc_html__( 'We detected that you previously had Zerif installed.', 'hestia' ) . '</p>';
		echo '<p>' . esc_html__( 'We can help you convert your Zerif front page into an Elementor template, so you can use it again.', 'hestia' ) . '</p>';

		if ( 'deactivate' !== $installer_helper->check_plugin_state( 'elementor' ) ) {
			echo '<p>' . esc_html__( 'But first, we need to install and activate Elementor.', 'hestia' ) . '</p>';
			echo '<p>' . $installer_helper->get_button_html( 'elementor' ) . '</p>';
		} else {
			echo '<p><a id="import-zerif-frontpage-button" class="button button-primary" href="#">';
			echo esc_html__( 'Import Zerif Front page as template ', 'hestia' );
			echo '</a></p>';
		}

		echo '</div>';
	}


	/**
	 * Enqueue import notice scripts.
	 */
	public function enqueue_notice_scripts() {

		if ( ! is_admin() ) {
			return;
		}

		wp_enqueue_script(
			'hestia-zerif-import-notice',
			get_template_directory_uri() . '/assets/js/admin/zerif-frontpage-import-notice.js',
			array( 'plugin-install' ),
			HESTIA_VERSION
		);

		wp_localize_script(
			'hestia-zerif-import-notice',
			'hestiaZerifImport',
			array(
				'ajaxurl'      => admin_url( 'admin-ajax.php' ),
				'dismissNonce' => wp_create_nonce( 'dismiss_zerif_import' ),
				'importNonce'  => wp_create_nonce( 'import_zerif_frontpage' ),
			)
		);
	}

	/**
	 * Dismiss import notice
	 */
	public function wp_ajax_dismiss_zerif_import() {
		$params = $_REQUEST;

		if ( ! isset( $params['nonce'] ) || ! wp_verify_nonce( $params['nonce'], 'dismiss_zerif_import' ) ) {
			wp_send_json_error( 'Wrong nonce' );
		}

		set_theme_mod( 'zerif_frontpage_was_imported', 'yes' );

		wp_send_json_success( 'Dismiss import' );

	}

	/**
	 * The callback of an ajax request when the user requests an import action.
	 */
	public function wp_ajax_import_zerif_frontpage() {
		$params = $_REQUEST;

		if ( ! isset( $params['nonce'] ) || ! wp_verify_nonce( $params['nonce'], 'import_zerif_frontpage' ) ) {
			wp_send_json_error( 'wrong nonce' );
		}

		$this->previous_theme_content = get_option( 'theme_mods_zerif-pro' );
		if ( empty( $this->previous_theme_content ) ) {
			$this->previous_theme_content = get_option( 'theme_mods_zerif-lite' );
		}

		require_once( ABSPATH . 'wp-admin' . '/includes/file.php' );
		require_once( ABSPATH . 'wp-admin' . '/includes/image.php' );
		global $wp_filesystem;

		WP_Filesystem();

		$template                   = download_url( esc_url( $this->json_template_url ) );
		$_FILES['file']['tmp_name'] = $template;

		$data                  = json_decode( $wp_filesystem->get_contents( $template ), true );
		$this->default_content = $data['content'];
		$this->content         = $this->default_content;

		// we don't need a footer for this page
		unset( $this->content[9] );

		if ( empty( $data ) ) {
			wp_send_json_error( 'Invalid File.' );
		}

		$this->map_bigtitle_section();
		$this->map_our_focus_section();
		$this->map_about_us_section();
		$this->map_our_team_section();
		$this->map_testimonials_section();
		$this->map_ribbon_section();
		$this->map_latest_news_section();
		$this->map_contact_us_section();

		$data['title']   = $this->name;
		$data['content'] = array_values( $this->content );

		$wp_filesystem->put_contents( $template, json_encode( $data ), 0644 );

		$elementor = new \Elementor\TemplateLibrary\Source_Local;

		$el_template_post = $elementor->import_template( $this->name, $template );

		if ( empty( $el_template_post ) ) {
			wp_send_json_error( 'cannot create template' );
		}

		$post_id = $this->insert_page( $el_template_post[0]['template_id'] );

		if ( $post_id ) {
			// * Mark the import as success and update the flag option to true.
			set_theme_mod( 'zerif_frontpage_was_imported', 'yes' );

			update_option( 'page_on_front', $post_id );
			update_option( 'show_on_front', 'page' );

			// on success we return the page url because we'll redirect to it.
			wp_send_json_success( esc_url( get_permalink( $post_id ) ) );
		}

		wp_send_json_error( 'something went wrong' );
	}

	/**
	 * Map the bigtitle options from Zerif theme_mod in the Elementor template.
	 */
	function map_bigtitle_section() {
		if ( isset( $this->previous_theme_content['zerif_bigtitle_show'] ) && $this->previous_theme_content['zerif_bigtitle_show'] ) {
			unset( $this->content[0] );

			return;
		}

		// background settings
		if ( ! empty( $this->previous_theme_content['background_image'] ) ) {
			$this->content[0]['settings']['background_image']['url'] = wp_kses_post( $this->previous_theme_content['background_image'] );
			$this->content[0]['settings']['background_image']['id']  = $this->get_image_id_by_url( $this->previous_theme_content['background_image'] );
		}

		if ( ! empty( $this->previous_theme_content['background_position_x'] ) && ! empty( $this->previous_theme_content['background_position_y'] ) ) {
			$this->content[0]['settings']['background_position'] = wp_kses_post( $this->previous_theme_content['background_position_x'] . ' ' . $this->previous_theme_content['background_position_y'] );
		}

		if ( ! empty( $this->previous_theme_content['background_attachment'] ) ) {
			$this->content[0]['settings']['background_attachment'] = wp_kses_post( $this->previous_theme_content['background_attachment'] );
		}

		// big title is always first.
		$data = $this->content[0]['elements'][0]['elements'];

		// heading settings
		$h_el = $data[0];
		if ( ! empty( $this->previous_theme_content['zerif_bigtitle_title'] ) ) {
			$h_el['settings']['title'] = wp_kses_post( $this->previous_theme_content['zerif_bigtitle_title'] );
		} elseif ( ! empty( $this->previous_theme_content['zerif_bigtitle_title_2'] ) ) {
			$h_el['settings']['title'] = wp_kses_post( $this->previous_theme_content['zerif_bigtitle_title_2'] );
		}
		if ( ! empty( $this->previous_theme_content['zerif_bigtitle_header_color'] ) ) {
			$h_el['settings']['title_color'] = wp_kses_post( $this->previous_theme_content['zerif_bigtitle_header_color'] );
		}
		$data[0] = $h_el;

		$buttons = $data[1]['elements'];

		if ( ! empty( $this->previous_theme_content['zerif_bigtitle_redbutton_label'] ) ) {
			$buttons[0]['elements'][0]['settings']['text'] = wp_kses_post( $this->previous_theme_content['zerif_bigtitle_redbutton_label'] );
		}
		if ( ! empty( $this->previous_theme_content['zerif_bigtitle_redbutton_label_2'] ) ) {
			$buttons[1]['elements'][0]['settings']['text'] = wp_kses_post( $this->previous_theme_content['zerif_bigtitle_redbutton_label_2'] );
		}

		if ( ! empty( $this->previous_theme_content['zerif_bigtitle_redbutton_url'] ) ) {
			$buttons[0]['elements'][0]['settings']['href'] = esc_url( $this->previous_theme_content['zerif_bigtitle_redbutton_url'] );
		}

		if ( ! empty( $this->previous_theme_content['zerif_bigtitle_greenbutton_label'] ) ) {
			$buttons[1]['elements'][0]['settings']['text'] = wp_kses_post( $this->previous_theme_content['zerif_bigtitle_greenbutton_label'] );
		}
		if ( ! empty( $this->previous_theme_content['zerif_bigtitle_greenbutton_url'] ) ) {
			$buttons[1]['elements'][0]['settings']['href'] = esc_url( $this->previous_theme_content['zerif_bigtitle_greenbutton_url'] );
		}

		// first button.
		if ( ! empty( $this->previous_theme_content['zerif_bigtitle_1button_color'] ) ) {
			$buttons[0]['elements'][0]['settings']['button_text_color'] = wp_kses_post( $this->previous_theme_content['zerif_bigtitle_1button_color'] );
		}
		if ( ! empty( $this->previous_theme_content['zerif_bigtitle_1button_color_hover'] ) ) {
			$buttons[0]['elements'][0]['settings']['hover_color'] = wp_kses_post( $this->previous_theme_content['zerif_bigtitle_1button_color_hover'] );
		}
		if ( ! empty( $this->previous_theme_content['zerif_bigtitle_1button_background_color'] ) ) {
			$buttons[0]['elements'][0]['settings']['background_color'] = wp_kses_post( $this->previous_theme_content['zerif_bigtitle_1button_background_color'] );
		}

		// second button.
		if ( ! empty( $this->previous_theme_content['zerif_bigtitle_2button_color'] ) ) {
			$buttons[1]['elements'][0]['settings']['button_text_color'] = wp_kses_post( $this->previous_theme_content['zerif_bigtitle_2button_color'] );
		}
		if ( ! empty( $this->previous_theme_content['zerif_bigtitle_2button_color_hover'] ) ) {
			$buttons[1]['elements'][0]['settings']['hover_color'] = wp_kses_post( $this->previous_theme_content['zerif_bigtitle_2button_color_hover'] );
		}
		if ( ! empty( $this->previous_theme_content['zerif_bigtitle_2button_background_color'] ) ) {
			$buttons[1]['elements'][0]['settings']['background_color'] = wp_kses_post( $this->previous_theme_content['zerif_bigtitle_2button_background_color'] );
		}
		if ( ! empty( $this->previous_theme_content['zerif_bigtitle_2button_background_color_hover'] ) ) {
			$buttons[1]['elements'][0]['settings']['button_background_hover_color'] = wp_kses_post( $this->previous_theme_content['zerif_bigtitle_2button_background_color_hover'] );
		}

		$data[1]['elements'] = $buttons;

		$this->content[0]['elements'][0]['elements'] = $data;
	}

	/**
	 * Map Our focus section.
	 */
	function map_our_focus_section() {
		if ( isset( $this->previous_theme_content['zerif_ourfocus_show'] ) && $this->previous_theme_content['zerif_ourfocus_show'] ) {
			unset( $this->content[1] );

			return;
		}

		if ( ! empty( $this->previous_theme_content['zerif_ourfocus_background'] ) ) {
			$this->content[1]['settings']['background_color'] = wp_kses_post( $this->previous_theme_content['zerif_ourfocus_background'] );
		}

		$data = $this->content[1]['elements'][0]['elements'];

		if ( ! empty( $this->previous_theme_content['zerif_ourfocus_title'] ) ) {
			$data[0]['settings']['title'] = wp_kses_post( $this->previous_theme_content['zerif_ourfocus_title'] );
		}

		if ( ! empty( $this->previous_theme_content['zerif_ourfocus_header'] ) ) {
			$data[0]['settings']['title_color'] = wp_kses_post( $this->previous_theme_content['zerif_ourfocus_header'] );
		}

		if ( ! empty( $this->previous_theme_content['zerif_ourfocus_subtitle'] ) ) {
			$data[1]['settings']['title'] = wp_kses_post( $this->previous_theme_content['zerif_ourfocus_subtitle'] );
		}

		$old_values = json_decode( get_theme_mod( 'hestia_features_content' ), true );

		if ( empty( $old_values ) ) {
			return;
		}

		$count_features = count( $old_values );

		$default_feature = $data[2]['elements'][0];

		$new_widgets = array();

		foreach ( $old_values as $index_key => $widget_data ) {
			$this_feature = $default_feature;

			// image
			if ( ! empty( $widget_data['image_url'] ) ) {
				$this_feature['elements'][0]['settings']['image']['url'] = $widget_data['image_url'];
				$this_feature['elements'][0]['settings']['image']['id']  = $this->get_image_id_by_url( $widget_data['image_url'] );
				$this_feature['elements'][0]['settings']['image_size']   = 'thumbnail';
				$this_feature['elements'][0]['settings']['link_to']      = $widget_data['link'];

				if ( ! empty( $widget_data['link'] ) ) {
					$this_feature['elements'][0]['settings']['link_to'] = 'custom';
					$this_feature['elements'][0]['settings']['url']     = $widget_data['link'];
				}

				// colors: this doesn't work with anything else than exactly 4 features; disabling for the moment.
				$this_feature['elements'][0]['settings']['_border_border']       = 'none';
				$this_feature['elements'][0]['settings']['_border_hover_border'] = 'none';
			}

			// title
			$this_feature['elements'][1]['settings']['title'] = $widget_data['title'];
			// description
			$this_feature['elements'][3]['settings']['editor'] = '<p style="text-align:center">' . $widget_data['text'] . '</p>';
			// recalculate box sizes
			$this_feature['settings']['_column_size'] = 25;
			$this_feature['settings']['_inline_size'] = round( 100 / $count_features );

			$this_feature['id'] = \Elementor\Utils::generate_random_string();

			$new_widgets[] = $this_feature;
		}

		$data[2]['elements'] = $new_widgets;

		$this->content[1]['elements'][0]['elements'] = $data;
	}

	/**
	 * Map About Us section.
	 */
	function map_about_us_section() {
		if ( isset( $this->previous_theme_content['zerif_aboutus_show'] ) && $this->previous_theme_content['zerif_aboutus_show'] ) {
			unset( $this->content[3] );

			return;
		}

		if ( ! empty( $this->previous_theme_content['zerif_aboutus_background'] ) ) {
			$this->content[3]['settings']['background_color'] = wp_kses_post( $this->previous_theme_content['zerif_aboutus_background'] );
		}

		$data = $this->content[3]['elements'][0]['elements'];

		$title = $data[0]['elements'][0]['elements'];

		if ( ! empty( $this->previous_theme_content['zerif_aboutus_title'] ) ) {
			$title[0]['settings']['title'] = wp_kses_post( $this->previous_theme_content['zerif_aboutus_title'] );
		}
		if ( ! empty( $this->previous_theme_content['zerif_aboutus_title_color'] ) ) {
			$title[0]['settings']['title_color'] = wp_kses_post( $this->previous_theme_content['zerif_aboutus_title_color'] );
		}

		if ( ! empty( $this->previous_theme_content['zerif_aboutus_subtitle'] ) ) {
			$title[1]['settings']['title'] = wp_kses_post( $this->previous_theme_content['zerif_aboutus_subtitle'] );
		}

		$data[0]['elements'][0]['elements'] = $title;

		$content = $data[1]['elements'];

		if ( ! empty( $this->previous_theme_content['zerif_aboutus_biglefttitle'] ) ) {
			$content[0]['elements'][0]['settings']['title'] = wp_kses_post( $this->previous_theme_content['zerif_aboutus_biglefttitle'] );
		}

		if ( ! empty( $this->previous_theme_content['zerif_aboutus_text'] ) ) {
			$content[1]['elements'][0]['settings']['editor'] = wp_kses_post( $this->previous_theme_content['zerif_aboutus_text'] );
		}

		$features = $content[2]['elements'];

		foreach ( $features as $key => $feature ) {

			if ( empty( $this->previous_theme_content[ 'zerif_aboutus_feature' . $key . '_text' ] ) ) {
				continue;
			}

			$features[ $key ]['settings']['title_text'] = $this->previous_theme_content[ 'zerif_aboutus_feature' . $key . '_title' ];
			// the `zerif_aboutus_feature1_nr` `zerif_aboutus_feature1_text` won't be imported
		}

		$content[2]['elements'] = $features;

		$data[1]['elements'] = $content;

		// we cannot import clients since they are custom widgets.
		unset( $data[2] );
		unset( $data[3] );

		$this->content[3]['elements'][0]['elements'] = $data;
	}

	/**
	 * Map Our Team section.
	 */
	function map_our_team_section() {
		if ( isset( $this->previous_theme_content['zerif_ourteam_show'] ) && $this->previous_theme_content['zerif_ourteam_show'] ) {
			unset( $this->content[4] );

			return;
		}

		$old_widget = json_decode( get_theme_mod( 'hestia_team_content' ), true );

		if ( ! empty( $this->previous_theme_content['zerif_ourteam_background'] ) ) {
			$this->content[4]['settings']['background_color'] = wp_kses_post( $this->previous_theme_content['zerif_ourteam_background'] );
		}

		$data = $this->content[4]['elements'][0]['elements'];

		// title $data[0]
		if ( ! empty( $this->previous_theme_content['zerif_ourteam_title'] ) ) {
			$data[0]['settings']['title'] = wp_kses_post( $this->previous_theme_content['zerif_ourteam_title'] );
			// now the color
			if ( ! empty( $this->previous_theme_content['zerif_ourteam_header'] ) ) {
				$data[0]['settings']['title_color'] = wp_kses_post( $this->previous_theme_content['zerif_ourteam_header'] );
			}
		}

		// subtitle $data[1]
		if ( ! empty( $this->previous_theme_content['zerif_ourteam_subtitle'] ) ) {
			$data[1]['settings']['title'] = wp_kses_post( $this->previous_theme_content['zerif_ourteam_subtitle'] );
			// now the color
			if ( ! empty( $this->previous_theme_content['zerif_ourteam_text'] ) ) {
				$data[1]['settings']['title_color'] = wp_kses_post( $this->previous_theme_content['zerif_ourteam_text'] );
			}
		}

		if ( ! empty( $old_widget ) ) {
			$widgets_nr   = count( $old_widget );
			$default_team = $data[2]['elements'][0];
			$new_widgets  = array();
			foreach ( $old_widget as $index => $widget ) {
				$new_team = $default_team;

				if ( ! empty( $widget['image_url'] ) ) {
					$new_team['elements'][0]['settings']          = $this->map_widget_image( $new_team['elements'][0]['settings'], $widget['image_url'] );
					$new_team['elements'][0]['settings']['align'] = 'center';
				}

				$color_key = 'zerif_ourteam_' . ( $index + 1 ) . 'box';
				if ( ! empty( $this->previous_theme_content[ $color_key ] ) ) {
					$new_team['elements'][0]['settings']['_border_color'] = wp_kses_post( $this->previous_theme_content[ $color_key ] );
					$new_team['elements'][2]['settings']['color']         = wp_kses_post( $this->previous_theme_content[ $color_key ] );
				}

				if ( ! empty( $widget['title'] ) ) {
					$new_team['elements'][1]['settings']['title'] = $widget['title'];
				}

				if ( ! empty( $widget['subtitle'] ) ) {
					$new_team['elements'][3]['settings']['editor'] = wp_kses_post( '<p style="text-align: center;">' . $widget['subtitle'] . '</p>' );
				}

				if ( ! empty( $widget['text'] ) ) {
					$new_team['elements'][3]['settings']['editor'] = wp_kses_post( '<p style="text-align: center;">' . $widget['subtitle'] . '</p>' );
				}

				if ( ! empty( $this->previous_theme_content['zerif_ourteam_socials'] ) ) {
					$new_team['elements'][4]['settings']['icon_secondary_color'] = wp_kses_post( $this->previous_theme_content['zerif_ourteam_socials'] );

					if ( ! empty( $this->previous_theme_content['zerif_ourteam_socials_hover'] ) ) {
						$new_team['elements'][4]['settings']['hover_secondary_color'] = wp_kses_post( $this->previous_theme_content['zerif_ourteam_socials_hover'] );
					}
				}

				if ( ! empty( $widget['social_repeater'] ) ) {
					$social_links = json_decode( $widget['social_repeater'], true );

					foreach ( $social_links as $social_index => $social_link ) {
						$new_link = array(
							'social' => 'fa ' . $social_link['icon'],
							'link'   => array(
								'is_external' => false,
								'nofollow'    => false,
								'url'         => $social_link['link'],
							),
							'_id'    => \Elementor\Utils::generate_random_string(),
						);

						$new_team['elements'][4]['settings']['social_icon_list'][ $social_index ] = $new_link;
					}
				}

				$new_team['id']                       = \Elementor\Utils::generate_random_string();
				$new_team['settings']['_column_size'] = round( 100 / $widgets_nr );
				$new_team['settings']['_inline_size'] = round( 100 / $widgets_nr );
				$new_widgets[]                        = $new_team;
			}

			$data[2]['elements'] = $new_widgets;
		}

		$this->content[4]['elements'][0]['elements'] = $data;
	}

	/**
	 * Map Testimonials section.
	 */
	function map_testimonials_section() {
		if ( isset( $this->previous_theme_content['zerif_testimonials_show'] ) && $this->previous_theme_content['zerif_testimonials_show'] ) {
			unset( $this->content[5] );

			return;
		}

		$data = $this->content[5]['elements'][0]['elements'];

		if ( ! empty( $this->previous_theme_content['zerif_testimonials_background'] ) ) {
			$this->content[5]['settings']['background_color'] = wp_kses_post( $this->previous_theme_content['zerif_testimonials_background'] );
		}

		if ( ! empty( $this->previous_theme_content['zerif_testimonials_title'] ) ) {
			$data[0]['settings']['title'] = wp_kses_post( $this->previous_theme_content['zerif_testimonials_title'] );

			if ( ! empty( $this->previous_theme_content['zerif_testimonials_header'] ) ) {
				$data[0]['settings']['title_color'] = wp_kses_post( $this->previous_theme_content['zerif_testimonials_header'] );
			}
		}

		if ( ! empty( $this->previous_theme_content['zerif_testimonials_subtitle'] ) ) {
			$data[1]['settings']['title'] = wp_kses_post( $this->previous_theme_content['zerif_testimonials_subtitle'] );
		}

		// widgets are hold in $data[2];
		$old_data = json_decode( get_theme_mod( 'hestia_testimonials_content' ), true );

		if ( ! empty( $old_data ) ) {
			$default_widget = $data[2]['elements'][0];
			$new_widgets    = array();

			$widgets_nr = count( $old_data );

			foreach ( $old_data as $testimonial_index => $testimonial ) {
				$this_widget = $default_widget;

				// testimonial content.
				if ( ! empty( $testimonial['text'] ) ) {
					$this_widget['elements'][0]['settings']['editor'] = wp_kses_post( $testimonial['text'] );
				}

				// name
				if ( ! empty( $testimonial['title'] ) ) {
					$this_widget['elements'][2]['settings']['editor'] = wp_kses_post( '<p>' . $testimonial['title'] . '</p>' );
				}

				if ( ! empty( $testimonial['image_url'] ) ) {
					$this_widget['elements'][3]['settings']                  = $this->map_widget_image( $this_widget['elements'][3]['settings'], $testimonial['image_url'] );
					$this_widget['elements'][3]['settings']['width']['size'] = 100;
				}

				// a job title?
				// $this_widget['elements'][4]['settings']['icon'];
				$this_widget['settings']['_column_size'] = round( 100 / $widgets_nr );

				$new_widgets[] = $this_widget;
			}

			$data[2]['elements'] = $new_widgets;
		}

		$this->content[5]['elements'][0]['elements'] = $data;
	}

	/**
	 * Map Ribbons section.
	 */
	function map_ribbon_section() {
		if ( ! empty( $this->previous_theme_content['zerif_ribbon_background'] ) ) {
			$this->content[2]['settings']['background_color'] = wp_kses_post( $this->previous_theme_content['zerif_ribbon_background'] );
		}

		if ( ! empty( $this->previous_theme_content['zerif_ribbonright_background'] ) ) {
			$this->content[6]['settings']['background_color'] = wp_kses_post( $this->previous_theme_content['zerif_ribbonright_background'] );
		}

		// plain ribbon
		$ribbon = $this->content[2]['elements'][0]['elements'];

		if ( ! empty( $this->previous_theme_content['zerif_bottomribbon_text'] ) ) {
			$ribbon[0]['settings']['title'] = wp_kses_post( $this->previous_theme_content['zerif_bottomribbon_text'] );
		}
		if ( ! empty( $this->previous_theme_content['zerif_ribbon_text_color'] ) ) {
			$ribbon[0]['settings']['title_color'] = wp_kses_post( $this->previous_theme_content['zerif_ribbon_text_color'] );
		}
		if ( ! empty( $this->previous_theme_content['zerif_ribbon_text_color'] ) ) {
			$ribbon[0]['settings']['title_color'] = wp_kses_post( $this->previous_theme_content['zerif_ribbon_text_color'] );
		}

		if ( ! empty( $this->previous_theme_content['zerif_bottomribbon_buttonlabel'] ) ) {
			$ribbon[1]['settings']['text'] = wp_kses_post( $this->previous_theme_content['zerif_bottomribbon_buttonlabel'] );
		}
		if ( ! empty( $this->previous_theme_content['zerif_bottomribbon_buttonlink'] ) ) {
			$ribbon[1]['settings']['url'] = wp_kses_post( $this->previous_theme_content['zerif_bottomribbon_buttonlink'] );
		}
		if ( ! empty( $this->previous_theme_content['zerif_ribbon_button_background'] ) ) {
			$ribbon[1]['settings']['background_color'] = wp_kses_post( $this->previous_theme_content['zerif_ribbon_button_background'] );
		}
		if ( ! empty( $this->previous_theme_content['zerif_ribbon_button_background_hover'] ) ) {
			$ribbon[1]['settings']['button_background_hover_color'] = wp_kses_post( $this->previous_theme_content['zerif_ribbon_button_background_hover'] );
		}
		if ( ! empty( $this->previous_theme_content['zerif_ribbon_button_button_color'] ) ) {
			$ribbon[1]['settings']['button_text_color'] = wp_kses_post( $this->previous_theme_content['zerif_ribbon_button_button_color'] );
		}

		$this->content[2]['elements'][0]['elements'] = $ribbon;

		// right ribbon.
		$right_ribbon = $this->content[6]['elements'];

		if ( ! empty( $this->previous_theme_content['zerif_ribbonright_text'] ) ) {
			$right_ribbon[0]['elements'][0]['settings']['title'] = wp_kses_post( $this->previous_theme_content['zerif_ribbonright_text'] );
		}
		if ( ! empty( $this->previous_theme_content['zerif_ribbonright_text_color'] ) ) {
			$right_ribbon[0]['elements'][0]['settings']['title_color'] = wp_kses_post( $this->previous_theme_content['zerif_ribbonright_text_color'] );
		}

		if ( ! empty( $this->previous_theme_content['zerif_ribbonright_buttonlabel'] ) ) {
			$right_ribbon[1]['elements'][0]['settings']['text'] = wp_kses_post( $this->previous_theme_content['zerif_ribbonright_buttonlabel'] );
		}
		if ( ! empty( $this->previous_theme_content['zerif_ribbonright_buttonlink'] ) ) {
			$right_ribbon[1]['elements'][0]['settings']['url'] = wp_kses_post( $this->previous_theme_content['zerif_ribbonright_buttonlink'] );
		}
		if ( ! empty( $this->previous_theme_content['zerif_ribbonright_button_background'] ) ) {
			$right_ribbon[1]['elements'][0]['settings']['background_color'] = wp_kses_post( $this->previous_theme_content['zerif_ribbonright_button_background'] );
		}
		if ( ! empty( $this->previous_theme_content['zerif_ribbonright_button_background_hover'] ) ) {
			$right_ribbon[1]['elements'][0]['settings']['button_background_hover_color'] = wp_kses_post( $this->previous_theme_content['zerif_ribbonright_button_background_hover'] );
		}
		if ( ! empty( $this->previous_theme_content['zerif_ribbonright_button_background_hover'] ) ) {
			$right_ribbon[1]['elements'][0]['settings']['button_background_hover_color'] = wp_kses_post( $this->previous_theme_content['zerif_ribbonright_button_background_hover'] );
		}
		if ( ! empty( $this->previous_theme_content['zerif_ribbonright_button_button_color'] ) ) {
			$right_ribbon[1]['elements'][0]['settings']['button_text_color'] = wp_kses_post( $this->previous_theme_content['zerif_ribbonright_button_button_color'] );
		}

		$this->content[6]['elements'] = $right_ribbon;
	}

	/**
	 * Map latest news.
	 */
	function map_latest_news_section() {
		if ( ! isset( $this->previous_theme_content['zerif_latest_news_show'] ) || ! $this->previous_theme_content['zerif_latest_news_show'] ) {
			unset( $this->content[7] );

			return;
		}

		if ( ! empty( $this->previous_theme_content['zerif_latestnews_background'] ) ) {
			$this->content[7]['settings']['background_color'] = wp_kses_post( $this->previous_theme_content['zerif_latestnews_background'] );
		}

		$data = $this->content[7]['elements'][0]['elements'];

		// title
		if ( ! empty( $this->previous_theme_content['zerif_latestnews_title'] ) ) {
			$data[0]['settings']['title'] = wp_kses_post( $this->previous_theme_content['zerif_latestnews_title'] );

			if ( ! empty( $this->previous_theme_content['zerif_latestnews_header_title_color'] ) ) {
				$data[0]['settings']['title_color'] = wp_kses_post( $this->previous_theme_content['zerif_latestnews_header_title_color'] );
			}
		}

		// subtitle
		if ( ! empty( $this->previous_theme_content['zerif_latestnews_subtitle'] ) ) {
			$data[1]['settings']['title'] = wp_kses_post( $this->previous_theme_content['zerif_latestnews_subtitle'] );

			if ( ! empty( $this->previous_theme_content['zerif_latestnews_header_subtitle_color'] ) ) {
				$data[1]['settings']['title_color'] = wp_kses_post( $this->previous_theme_content['zerif_latestnews_header_subtitle_color'] );
			}
		}

		// latest posts widget
		if ( ! empty( $this->previous_theme_content['zerif_latestnews_post_title_color'] ) ) {
			$data[2]['settings']['grid_title_style_color'] = wp_kses_post( $this->previous_theme_content['zerif_latestnews_post_title_color'] );
		}
		if ( ! empty( $this->previous_theme_content['zerif_latestnews_post_text_color'] ) ) {
			$data[2]['settings']['grid_content_style_color'] = wp_kses_post( $this->previous_theme_content['zerif_latestnews_post_text_color'] );
		}

		$this->content[7]['elements'][0]['elements'] = $data;
	}

	/**
	 * Map Contact us section.
	 */
	function map_contact_us_section() {
		if ( isset( $this->previous_theme_content['zerif_contactus_show'] ) && $this->previous_theme_content['zerif_contactus_show'] ) {
			unset( $this->content[0] );

			return;
		}

		$data = $this->content[8]['elements'][0]['elements'];

		if ( ! empty( $this->previous_theme_content['zerif_contactus_title'] ) ) {
			$data[0]['settings']['title'] = wp_kses_post( $this->previous_theme_content['zerif_contactus_title'] );
			if ( ! empty( $this->previous_theme_content['zerif_contacus_header'] ) ) {
				$data[0]['settings']['title_color'] = wp_kses_post( $this->previous_theme_content['zerif_contacus_header'] );
			}
		}

		if ( ! empty( $this->previous_theme_content['zerif_contactus_subtitle'] ) ) {
			$data[1]['settings']['title'] = wp_kses_post( $this->previous_theme_content['zerif_contactus_subtitle'] );
			if ( ! empty( $this->previous_theme_content['zerif_contacus_header'] ) ) {
				$data[0]['settings']['title_color'] = wp_kses_post( $this->previous_theme_content['zerif_contacus_header'] );
			}
		}

		if ( ! empty( $this->previous_theme_content['zerif_contactus_email'] ) ) {
			$data[2]['settings']['to_send_email'] = wp_kses_post( $this->previous_theme_content['zerif_contactus_email'] );
		}

		if ( ! empty( $this->previous_theme_content['zerif_contactus_button_label'] ) ) {
			$data[2]['settings']['submit_label'] = wp_kses_post( $this->previous_theme_content['zerif_contactus_button_label'] );
		}

		if ( ! empty( $this->previous_theme_content['zerif_contactus_name_placeholder'] ) ) {
			$data[2]['settings']['form_fields'][0]['placeholder'] = wp_kses_post( $this->previous_theme_content['zerif_contactus_name_placeholder'] );
		}
		if ( ! empty( $this->previous_theme_content['zerif_contactus_email_placeholder'] ) ) {
			$data[2]['settings']['form_fields'][1]['placeholder'] = wp_kses_post( $this->previous_theme_content['zerif_contactus_email_placeholder'] );
		}
		if ( ! empty( $this->previous_theme_content['zerif_contactus_subject_placeholder'] ) ) {
			$data[2]['settings']['form_fields'][2]['placeholder'] = wp_kses_post( $this->previous_theme_content['zerif_contactus_message_placeholder'] );
			$data[2]['settings']['form_fields'][2]['key']         = 'subject';
			$data[2]['settings']['form_fields'][2]['type']        = 'text';
		}
		if ( ! empty( $this->previous_theme_content['zerif_contactus_message_placeholder'] ) ) {
			$data[2]['settings']['form_fields'][3]['placeholder'] = wp_kses_post( $this->previous_theme_content['zerif_contactus_message_placeholder'] );
		}

		$this->content[8]['elements'][0]['elements'] = $data;
	}

	/**
	 * This function maps a image id and url to an Elementor widget array and it returns the mapped array.
	 *
	 * @param array  $widget The element array.
	 * @param string $url The image url to import.
	 *
	 * @return array
	 */
	function map_widget_image( $widget, $url ) {
		$widget['image']['url'] = $url;
		$widget['image']['id']  = $this->get_image_id_by_url( $url );
		$widget['image_size']   = 'thumbnail';

		return $widget;
	}

	/**
	 * This method seeks for the imported Elementor template and creates a page based on it's content.
	 *
	 * @param number $id The Elementor template id.
	 *
	 * @return bool|int|WP_Error
	 */
	private function insert_page( $id ) {
		$args = array(
			'post_type'        => 'elementor_library',
			'nopaging'         => true,
			'posts_per_page'   => '1',
			'suppress_filters' => true,
			'post__in'         => array( $id ),
		);

		$query = new \WP_Query( $args );

		$last_template_added = $query->posts[0];
		// get template id
		$template_id = $last_template_added->ID;

		wp_reset_query();
		wp_reset_postdata();

		// page content
		$page_content = $last_template_added->post_content;
		// meta fields
		$elementor_data_meta      = get_post_meta( $template_id, '_elementor_data' );
		$elementor_ver_meta       = get_post_meta( $template_id, '_elementor_version' );
		$elementor_edit_mode_meta = get_post_meta( $template_id, '_elementor_edit_mode' );
		$elementor_css_meta       = get_post_meta( $template_id, '_elementor_css' );
		$elementor_metas          = array(
			'_elementor_data'      => ! empty( $elementor_data_meta[0] ) ? wp_slash( $elementor_data_meta[0] ) : '',
			'_elementor_version'   => ! empty( $elementor_ver_meta[0] ) ? $elementor_ver_meta[0] : '',
			'_elementor_edit_mode' => ! empty( $elementor_edit_mode_meta[0] ) ? $elementor_edit_mode_meta[0] : '',
			'_elementor_css'       => $elementor_css_meta,
		);

		// Create post object
		$new_template_page = array(
			'post_type'     => 'page',
			'post_title'    => $this->name,
			'post_status'   => 'publish',
			'post_content'  => $page_content,
			'meta_input'    => $elementor_metas,
			'page_template' => apply_filters( 'template_directory_default_template', 'page-templates/template-pagebuilder-full-width.php' ),
		);

		// * Insert a new page.
		$post_id = wp_insert_post( $new_template_page );

		if ( is_wp_error( $post_id ) ) {
			return false;
		}

		return $post_id;
	}

	/**
	 * Returns the attachment id for an certain attachment url.
	 *
	 * @param string $attachment_url The attachment url for which we search the id.
	 *
	 * @return bool|null|string|void
	 */
	function get_image_id_by_url( $attachment_url = '' ) {
		global $wpdb;
		$attachment_id = false;

		// If there is no url, return.
		if ( '' == $attachment_url ) {
			return;
		}

		// Get the upload directory paths
		$upload_dir_paths = wp_upload_dir();

		// Make sure the upload path base directory exists in the attachment URL, to verify that we're working with a media library image
		if ( false !== strpos( $attachment_url, $upload_dir_paths['baseurl'] ) ) {

			// If this is the URL of an auto-generated thumbnail, get the URL of the original image
			$attachment_url = preg_replace( '/-\d+x\d+(?=\.(jpg|jpeg|png|gif)$)/i', '', $attachment_url );

			// Remove the upload path base directory from the attachment URL
			$attachment_url = str_replace( $upload_dir_paths['baseurl'] . '/', '', $attachment_url );

			// Finally, run a custom database query to get the attachment ID from the modified attachment URL
			$attachment_id = $wpdb->get_var( $wpdb->prepare( "SELECT wposts.ID FROM $wpdb->posts wposts, $wpdb->postmeta wpostmeta WHERE wposts.ID = wpostmeta.post_id AND wpostmeta.meta_key = '_wp_attached_file' AND wpostmeta.meta_value = %s AND wposts.post_type = 'attachment'", $attachment_url ) );

		}

		return $attachment_id;
	}
}
