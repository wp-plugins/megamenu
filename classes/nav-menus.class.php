<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // disable direct access
}

if ( ! class_exists( 'Mega_Menu_Nav_Menus' ) ) :
/**
 * Handles all admin related functionality.
 */
class Mega_Menu_Nav_Menus {

    /**
     * Return the default settings for each menu item
     *
     * @since 1.5
     */
    public static function get_menu_item_defaults() {

        $defaults = array(
            'type' => 'flyout',
            'align' => 'bottom-left',
            'icon' => 'disabled',
            'hide_text' => 'false',
            'disable_link' => 'false',
            'hide_arrow' => 'false',
            'item_align' => 'left',
            'panel_columns' => 6, // total number of columns displayed in the panel
            'mega_menu_columns' => 1 // for sub menu items, how many columns to span in the panel
        );

        return apply_filters( "megamenu_menu_item_defaults", $defaults );

    }

    /**
     * Constructor
     *
     * @since 1.0
     */
    public function __construct() {

        add_action( 'admin_init', array( $this, 'register_nav_meta_box' ), 11 );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_menu_page_scripts' ) );
        add_action( 'megamenu_save_settings', array($this, 'save') );

        add_filter( 'hidden_meta_boxes', array( $this, 'show_mega_menu_metabox' ) );

    }

    /**
     * By default the mega menu meta box is hidden - show it.
     *
     * @since 1.0
     * @param array $hidden
     * @return array
     */
    public function show_mega_menu_metabox( $hidden ) {

        if ( is_array( $hidden ) && count( $hidden ) > 0 ) {
            foreach ( $hidden as $key => $value ) {
                if ( $value == 'mega_menu_meta_box' ) {
                    unset( $hidden[$key] );
                }
            }            
        }

        return $hidden;
    }


    /**
     * Adds the meta box container
     *
     * @since 1.0
     */
    public function register_nav_meta_box() {
        global $pagenow;

        if ( 'nav-menus.php' == $pagenow ) {

            add_meta_box(
                'mega_menu_meta_box',
                __("Mega Menu Settings", "megamenu"),
                array( $this, 'metabox_contents' ),
                'nav-menus',
                'side',
                'high'
            );

        }

    }


    /**
     * Enqueue required CSS and JS for Mega Menu
     *
     * @since 1.0
     */
    public function enqueue_menu_page_scripts($hook) {

        if( 'nav-menus.php' != $hook )
            return;
        
        // http://wordpress.org/plugins/image-widget/
        if ( class_exists( 'Tribe_Image_Widget' ) ) {
            $image_widget = new Tribe_Image_Widget;
            $image_widget->admin_setup();
        }

        wp_enqueue_style( 'colorbox', MEGAMENU_BASE_URL . 'js/colorbox/colorbox.css', false, MEGAMENU_VERSION );
        wp_enqueue_style( 'mega-menu', MEGAMENU_BASE_URL . 'css/admin-menus.css', false, MEGAMENU_VERSION );

        wp_enqueue_script( 'mega-menu', MEGAMENU_BASE_URL . 'js/admin.js', array(
            'jquery',
            'jquery-ui-core',
            'jquery-ui-sortable',
            'jquery-ui-accordion'),
        MEGAMENU_VERSION );

        wp_enqueue_script( 'colorbox', MEGAMENU_BASE_URL . 'js/colorbox/jquery.colorbox-min.js', array( 'jquery' ), MEGAMENU_VERSION );

        wp_localize_script( 'mega-menu', 'megamenu',
            array(
                'debug_launched' => __("Launched for Menu ID", "megamenu"),
                'launch_lightbox' => __("Mega Menu", "megamenu"),
                'saving' => __("Saving", "megamenu"),
                'nonce' => wp_create_nonce('megamenu_edit'),
                'nonce_check_failed' => __("Oops. Something went wrong. Please reload the page.", "megamenu")
            )
        );

        do_action("megamenu_enqueue_admin_scripts");

    }

    /**
     * Show the Meta Menu settings
     *
     * @since 1.0
     */
    public function metabox_contents() {

        $menu_id = $this->get_selected_menu_id();

        do_action("megamenu_save_settings");

        $this->print_enable_megamenu_options( $menu_id );

    }


    /**
     * Save the mega menu settings (submitted from Menus Page Meta Box)
     *
     * @since 1.0
     */
    public function save() {

        if ( isset( $_POST['menu'] ) && $_POST['menu'] > 0 && is_nav_menu( $_POST['menu'] ) && isset( $_POST['megamenu_meta'] ) ) {

            $submitted_settings = $_POST['megamenu_meta'];

            if ( ! get_site_option( 'megamenu_settings' ) ) {

                add_site_option( 'megamenu_settings', $submitted_settings );

            } else {

                $existing_settings = get_site_option( 'megamenu_settings' );

                $new_settings = array_merge( $existing_settings, $submitted_settings );

                update_site_option( 'megamenu_settings', $new_settings );

            }

            do_action( "megamenu_after_save_settings" );

        }

    }


    /**
     * Print the custom Meta Box settings
     *
     * @param int $menu_id
     * @since 1.0
     */
    public function print_enable_megamenu_options( $menu_id ) {

        $tagged_menu_locations = $this->get_tagged_theme_locations_for_menu_id( $menu_id );
        $theme_locations = get_registered_nav_menus();

        $saved_settings = get_site_option( 'megamenu_settings' );

        if ( ! count( $theme_locations ) ) {

            echo "<p>" . __("This theme does not have any menu locations.", "megamenu") . "</p>";

        } else if ( ! count ( $tagged_menu_locations ) ) {

            echo "<p>" . __("This menu is not tagged to a location. Please tag a location to enable the Mega Menu settings.", "megamenu") . "</p>";

        } else { ?>

            <?php if ( count( $tagged_menu_locations ) == 1 ) : ?>
            
                <?php 

                $locations = array_keys( $tagged_menu_locations );
                $location = $locations[0];

                if (isset( $tagged_menu_locations[ $location ] ) ) {
                    $this->settings_table( $location, $saved_settings ); 
                }
                
                ?>

            <?php else: ?>

                <div id='megamenu_accordion'>

                    <?php foreach ( $theme_locations as $location => $name ) : ?>
                    
                        <?php if ( isset( $tagged_menu_locations[ $location ] ) ): ?>

                            <h3 class='theme_settings'><?php echo esc_html( $name ); ?></h3>

                            <div class='accordion_content' style='display: none;'>
                                <?php $this->settings_table( $location, $saved_settings ); ?>
                            </div>
                            
                        <?php endif; ?>
                    
                    <?php endforeach;?>
                </div>

            <?php endif; ?>

            <?php 

            submit_button( __( 'Save' ), 'button-primary alignright');

        }

    }

    /**
     * Print the list of Mega Menu settings
     *
     * @since 1.0
     */
    public function settings_table( $location, $settings ) {
        ?>
        <table>
            <tr>
                <td><?php _e("Enable", "megamenu") ?></td>
                <td>
                    <input type='checkbox' name='megamenu_meta[<?php echo $location ?>][enabled]' value='1' <?php checked( isset( $settings[$location]['enabled'] ) ); ?> />
                </td>
            </tr>
            <tr>
                <td><?php _e("Event", "megamenu") ?></td>
                <td>
                    <select name='megamenu_meta[<?php echo $location ?>][event]'>
                        <option value='hover' <?php selected( isset( $settings[$location]['event'] ) && $settings[$location]['event'] == 'hover'); ?>><?php _e("Hover", "megamenu"); ?></option>
                        <option value='click' <?php selected( isset( $settings[$location]['event'] ) && $settings[$location]['event'] == 'click'); ?>><?php _e("Click", "megamenu"); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <td><?php _e("Effect", "megamenu") ?></td>
                <td>
                    <select name='megamenu_meta[<?php echo $location ?>][effect]'>
                        <option value='disabled' <?php selected( isset( $settings[$location]['effect'] ) && $settings[$location]['effect'] == 'disabled'); ?>><?php _e("None", "megamenu"); ?></option>
                        <option value='fade' <?php selected( isset( $settings[$location]['effect'] ) && $settings[$location]['effect'] == 'fade'); ?>><?php _e("Fade", "megamenu"); ?></option>
                        <option value='slide' <?php selected( isset( $settings[$location]['effect'] ) && $settings[$location]['effect'] == 'slide'); ?>><?php _e("Slide", "megamenu"); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <td><?php _e("Theme", "megamenu"); ?></td>
                <td>

                    <select name='megamenu_meta[<?php echo $location ?>][theme]'>
                        <?php 
                            $style_manager = new Mega_Menu_Style_Manager();
                            $themes = $style_manager->get_themes();

                            foreach ( $themes as $key => $theme ) {
                                echo "<option value='{$key}' " . selected( $settings[$location]['theme'], $key ) . ">{$theme['title']}</option>";
                            }
                        ?>
                    </select>
                </td>
            </tr>

            <?php do_action('megamenu_settings_table', $location, $settings); ?>
        </table>
        <?php
    }


    /**
     * Return the locations that a specific menu ID has been tagged to.
     *
     * @param $menu_id int
     * @return array
     */
    public function get_tagged_theme_locations_for_menu_id( $menu_id ) {

        $locations = array();

        $nav_menu_locations = get_nav_menu_locations();

        foreach ( get_registered_nav_menus() as $id => $name ) {

            if ( isset( $nav_menu_locations[ $id ] ) && $nav_menu_locations[$id] == $menu_id )
                $locations[$id] = $name;

        }

        return $locations;
    }

    /**
     * Get the current menu ID.
     *
     * Most of this taken from wp-admin/nav-menus.php (no built in functions to do this)
     *
     * @since 1.0
     * @return int
     */
    public function get_selected_menu_id() {

        $nav_menus = wp_get_nav_menus( array('orderby' => 'name') );

        $menu_count = count( $nav_menus );

        $nav_menu_selected_id = isset( $_REQUEST['menu'] ) ? (int) $_REQUEST['menu'] : 0;

        $add_new_screen = ( isset( $_GET['menu'] ) && 0 == $_GET['menu'] ) ? true : false;

        // If we have one theme location, and zero menus, we take them right into editing their first menu
        $page_count = wp_count_posts( 'page' );
        $one_theme_location_no_menus = ( 1 == count( get_registered_nav_menus() ) && ! $add_new_screen && empty( $nav_menus ) && ! empty( $page_count->publish ) ) ? true : false;

        // Get recently edited nav menu
        $recently_edited = absint( get_user_option( 'nav_menu_recently_edited' ) );
        if ( empty( $recently_edited ) && is_nav_menu( $nav_menu_selected_id ) )
            $recently_edited = $nav_menu_selected_id;

        // Use $recently_edited if none are selected
        if ( empty( $nav_menu_selected_id ) && ! isset( $_GET['menu'] ) && is_nav_menu( $recently_edited ) )
            $nav_menu_selected_id = $recently_edited;

        // On deletion of menu, if another menu exists, show it
        if ( ! $add_new_screen && 0 < $menu_count && isset( $_GET['action'] ) && 'delete' == $_GET['action'] )
            $nav_menu_selected_id = $nav_menus[0]->term_id;

        // Set $nav_menu_selected_id to 0 if no menus
        if ( $one_theme_location_no_menus ) {
            $nav_menu_selected_id = 0;
        } elseif ( empty( $nav_menu_selected_id ) && ! empty( $nav_menus ) && ! $add_new_screen ) {
            // if we have no selection yet, and we have menus, set to the first one in the list
            $nav_menu_selected_id = $nav_menus[0]->term_id;
        }

        return $nav_menu_selected_id;

    }

}

endif;