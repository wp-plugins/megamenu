<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // disable direct access
}

if ( ! class_exists( 'Mega_Menu_Style_Manager' ) ) :

/**
 * 
 */
final class Mega_Menu_Style_Manager {

	/**
	 * Constructor
     *
     * @since 1.0
	 */
	public function __construct() {

	}


	/**
	 * Setup actions
	 *
	 * @since 1.0
	 */
	public function setup_actions() {

		add_action( 'wp_ajax_megamenu_css', array( $this, 'get_css') );
		add_action( 'wp_ajax_nopriv_megamenu_css', array( $this, 'get_css') );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

	}

    /**
     *
     *
     * @since 1.0
     */
    public function default_themes() {

        $themes['default'] = array(
            'title'                                     => __("Default", "megamenu"),
            'container_background_from'                 => '#222',
            'container_background_to'                   => '#222',
            'container_padding_left'                    => '0px',
            'container_padding_right'                   => '0px',
            'container_padding_top'                     => '0px',
            'container_padding_bottom'                  => '0px',
            'container_border_radius_top_left'          => '0px',
            'container_border_radius_top_right'         => '0px',
            'container_border_radius_bottom_left'       => '0px',
            'container_border_radius_bottom_right'      => '0px',
            'arrow_up'                                  => 'dash-f142',
            'arrow_down'                                => 'dash-f140',
            'arrow_left'                                => 'dash-f141',
            'arrow_right'                               => 'dash-f139',
            'menu_item_background_from'                 => 'transparent',
            'menu_item_background_to'                   => 'transparent',
            'menu_item_background_hover_from'           => '#333',
            'menu_item_background_hover_to'             => '#333',
            'menu_item_spacing'                         => '0px',
            'menu_item_link_font'                       => 'inherit',
            'menu_item_link_font_size'                  => '14px',
            'menu_item_link_height'                     => '40px',
            'menu_item_link_color'                      => '#ffffff',
            'menu_item_link_weight'                     => 'normal',
            'menu_item_link_text_transform'             => 'normal',
            'menu_item_link_color_hover'                => '#ffffff',
            'menu_item_link_weight_hover'               => 'normal',
            'menu_item_link_padding_left'               => '10px',
            'menu_item_link_padding_right'              => '10px',
            'menu_item_link_padding_top'                => '0px',
            'menu_item_link_padding_bottom'             => '0px',
            'menu_item_link_border_radius_top_left'     => '0px',
            'menu_item_link_border_radius_top_right'    => '0px',
            'menu_item_link_border_radius_bottom_left'  => '0px',
            'menu_item_link_border_radius_bottom_right' => '0px',
            'panel_background_from'                     => '#f1f1f1',
            'panel_background_to'                       => '#f1f1f1',
            'panel_width'                               => '100%',
            'panel_header_color'                        => '#555',
            'panel_header_text_transform'               => 'uppercase',
            'panel_header_font'                         => 'inherit',
            'panel_header_font_size'                    => '16px',
            'panel_header_font_weight'                  => 'bold',
            'panel_header_padding_top'                  => '0px',
            'panel_header_padding_right'                => '0px',
            'panel_header_padding_bottom'               => '5px',
            'panel_header_padding_left'                 => '0px',
            'panel_padding_left'                        => '0px',
            'panel_padding_right'                       => '0px',
            'panel_padding_top'                         => '0px',
            'panel_padding_bottom'                      => '0px',
            'panel_widget_padding_left'                 => '15px',
            'panel_widget_padding_right'                => '15px',
            'panel_widget_padding_top'                  => '15px',
            'panel_widget_padding_bottom'               => '15px',
            'flyout_width'                              => '150px',
            'flyout_link_padding_left'                  => '10px',
            'flyout_link_padding_right'                 => '10px',
            'flyout_link_padding_top'                   => '0px',
            'flyout_link_padding_bottom'                => '0px',
            'flyout_link_weight'                        => 'normal',
            'flyout_link_weight_hover'                  => 'normal',
            'flyout_link_height'                        => '35px',
            'flyout_background_from'                    => '#f1f1f1',
            'flyout_background_to'                      => '#f1f1f1',
            'flyout_background_hover_from'              => '#dddddd',
            'flyout_background_hover_to'                => '#dddddd',
            'font_size'                                 => '14px',
            'font_color'                                => '#666',
            'font_family'                               => 'inherit',
            'responsive_breakpoint'                     => '600px',
            'line_height'                               => '1.7',
            'z_index'                                   => '999',
            'custom_css'                                => '
#{$wrap} #{$menu} {
    /** Custom styles should be added below this line **/
}
#{$wrap} { 
    clear: both;
}'
        );

        return apply_filters( "megamenu_themes", $themes);
    }


    /**
     * Return a filtered list of themes
     *
     * @since 1.0
     * @return array
     */
    public function get_themes() {

    	$default_themes = $this->default_themes();

    	if ( $saved_themes = get_site_option( "megamenu_themes" ) ) {

    		foreach ( $default_themes as $key => $settings ) {

    			// Merge in any custom modifications to default themes
    			if ( isset( $saved_themes[ $key ] ) ) {

    				$default_themes[ $key ] = array_merge( $default_themes[ $key ], $saved_themes[ $key ] );
    				unset( $saved_themes[ $key ] );

    			}

    		}

    		foreach ( $saved_themes as $key => $settings ) {

    			// Add in saved themes, ensuring they always have a placeholder for any new settings
    			// which have since been added to the default theme.
    			$default_themes[ $key ] = array_merge ( $default_themes['default'], $settings );

    		}

    	}

		uasort( $default_themes, array( $this, 'sort_by_title' ) );

    	return $default_themes;
    	
    }


    /**
     * Sorts a 2d array by the 'title' key
     *
     * @since 1.0
     * @param array $a
     * @param array $b
     */
    function sort_by_title( $a, $b ) {
	    return strcmp( $a['title'], $b['title'] );
	}


	/**
	 * Return the menu CSS. Use the cache if possible.
     *
     * @since 1.0
	 */
	public function get_css() {

		header("Content-type: text/css; charset: UTF-8");

		$debug_mode = ( defined( 'MEGAMENU_DEBUG' ) && MEGAMENU_DEBUG === true ) || isset( $_GET['nocache'] );

		if ( $debug_mode ) {

			echo $this->generate_css( true );

		} else if ( $css = get_site_transient('megamenu_css') ) {

			echo $css;
			echo "\n/** CSS served from cache **/";

		} else {

			echo $this->generate_css();

		}

	  	wp_die();
	}


	/**
	 * Generate and cache the CSS for our menus.
	 * The CSS is compiled by lessphp using the file located in /css/megamenu.less
     *
     * @since 1.0
	 * @return string
	 * @param boolean $debug_mode (prints error messages to the CSS when enabled)
	 */
	public function generate_css( $debug_mode = false ) {

		$start_time = microtime( true );

	  	$settings = get_site_option( "megamenu_settings" );

	  	if (! $settings ) {
	  		return "/** CSS Generation Failed. No menu settings found **/";
	  	}

  		$locations = get_nav_menu_locations();

  		$css = "";

  		$exception = false;

	  	foreach ( $settings as $location => $settings ) {

	  		if ( ! isset( $locations[ $location ] ) ) continue;

	  		$menu_id = $locations[ $location ];

	  		if ( ! $menu_id ) continue;

	  		$selected_theme = $settings['theme'];

			$compiled_css = $this->generate_css_for_theme( $selected_theme, $location, $menu_id );

			if ( ! is_wp_error( $compiled_css ) ) {

				$css .= $compiled_css;

			} else {

				if ( $debug_mode ) {

					$css .= $compiled_css->get_error_message();

				}

				$exception = true;
			}

	  	}

		$load_time = number_format( microtime(true) - $start_time, 4 );

        $css .= "\n/** Dynamic CSS generated in " . $load_time . " seconds **/";
	  	$css .= "\n/** Cached CSS generated by mega-menu on " . date('l jS \of F Y h:i:s A') . " **/";

	  	if ( ! $exception ) {
	  		set_site_transient( 'megamenu_css', $css, 12 * HOUR_IN_SECONDS );
	  	}

	  	return $css;
	}


	/**
	 *
	 */
	public function generate_css_for_theme( $theme, $location = '', $menu_id = '' ) {

		$scssc = new scssc();
		$scssc->setFormatter("scss_formatter");

		$scss_path = $this->load_scss_file(); 
		$raw_scss = file_get_contents( $scss_path['path'] );

		$all_themes = $this->get_themes();

		$theme_settings = isset( $all_themes[ $theme ] ) ? $all_themes[ $theme ] : $all_themes[ 'default' ];

		$vars = "\$wrap: \"#mega-menu-wrap-{$location}-{$menu_id}\";
			     \$menu: \"#mega-menu-{$location}-{$menu_id}\";
				 \$number_of_columns: 6;";

		foreach( $theme_settings as $name => $value ) {

			if ( in_array( $name, array( 'arrow_up', 'arrow_down', 'arrow_left', 'arrow_right' ) ) ) {

				$parts = explode( '-', $value );

				$code = end( $parts );

				$arrow_icon = $code == 'disabled' ? "''" : "'\\" . $code . "'";

				$vars .= "$" . $name . ": " . $arrow_icon . ";\n";

				continue;
			}

			if ( $name != 'custom_css' ) {
				$vars .= "$" . $name . ": " . $value . ";\n";
			}

		}

		$custom_css = html_entity_decode( $theme_settings['custom_css'] );

		try {
		    return $scssc->compile( $vars . $raw_scss . $custom_css );
		}
		catch ( Exception $e ) {
			$message = __("Warning: CSS compilation failed. Please check your changes or revert the theme.", "megamenu");

			return new WP_Error( 'scss_compile_fail', $message . "<br /><br />" . $e->getMessage() );
		}

	}


	/**
	 * Return the path to the megamenu.scss file, look for custom files before
	 * loading the core version.
     *
     * @since 1.0
	 * @return string Full path to scss file
	 */
	private function load_scss_file() {

		$located = array(
			'type' => 'core',
			'path' => MEGAMENU_PATH . trailingslashit('css') . 'megamenu.scss'
		);
 
		$locations = array(
			'child' => trailingslashit( get_stylesheet_directory() ) . trailingslashit("megamenu"), // child theme
			'parent' => trailingslashit( get_template_directory() ) . trailingslashit("megamenu"), // parent theme
		);
 
 		$locations = apply_filters( "megamenu_scss_locations", $locations );

 		foreach ( $locations as $type => $location ) {

			if ( file_exists( $location . 'megamenu.scss' ) ) {

				$located = array(
					'type' => $type, 
					'path' => $location . 'megamenu.scss'
				);

				return $located;

			}

 		}

		return $located;
	}


	/**
	 * Enqueue public CSS and JS files required by Mega Menu
     *
     * @since 1.0
	 */
	public function enqueue_scripts() {

		wp_enqueue_script( 'megamenu', MEGAMENU_BASE_URL . "js/public.js", array('jquery'), MEGAMENU_VERSION );

		$params = apply_filters("megamenu_javascript_localisation", 
			array( 
				'fade_speed' => 'fast',
				'slide_speed' => 'fast'
			)
		);

		wp_localize_script( 'megamenu', 'megamenu', $params );

		wp_enqueue_script( 'hoverIntent' );
		
		wp_enqueue_style( 'megamenu', admin_url('admin-ajax.php') . '?action=megamenu_css', false, MEGAMENU_VERSION );
		wp_enqueue_style( 'dashicons' );

	}

}

endif;