<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // disable direct access
}

if ( ! class_exists( 'Mega_Menu_theme_Editor' ) ) :

/**
 * Handles all admin related functionality.
 */
class Mega_Menu_theme_Editor {


    /**
     * All themes (default and custom)
     */
    var $themes = array();


    /**
     * Active theme
     */
    var $active_theme = array();


    /**
     * Active theme ID
     */
    var $id = "";


    /**
     * Constructor
     *
     * @since 1.0
     */
    public function __construct() {

        add_action( 'admin_post_megamenu_save_theme', array( $this, 'save') );
        add_action( 'admin_post_megamenu_add_theme', array( $this, 'create') );
        add_action( 'admin_post_megamenu_delete_theme', array( $this, 'delete') );
        add_action( 'admin_post_megamenu_revert_theme', array( $this, 'revert') );
        add_action( 'admin_post_megamenu_duplicate_theme', array( $this, 'duplicate') );

        add_action( 'admin_menu', array( $this, 'megamenu_themes_page') );
        add_action( "admin_enqueue_scripts", array( $this, 'enqueue_theme_editor_scripts' ) );

        if ( class_exists( "Mega_Menu_Style_Manager" ) ) {

            $style_manager = new Mega_Menu_Style_Manager();

            $this->themes = $style_manager->get_themes();
            $this->id = isset( $_GET['theme'] ) ? $_GET['theme'] : 'default';
            $this->active_theme = $this->themes[$this->id];

        }
    }

    /**
     * Save changes to an exiting theme.
     *
     * @since 1.0
     */
    public function save() {

        check_admin_referer( 'megamenu_save_theme' );

        $theme = esc_attr( $_POST['theme_id'] );

        $saved_themes = get_site_option( "megamenu_themes" );

        if ( isset( $saved_themes[ $theme ] ) ) {
            unset( $saved_themes[ $theme ] );
        }

        $saved_themes[ $theme ] = array_map( 'esc_attr', $_POST['settings'] );

        update_site_option( "megamenu_themes", $saved_themes );

        do_action("megamenu_after_theme_save");

        wp_redirect( admin_url( "themes.php?page=megamenu_theme_editor&theme={$theme}&saved=true" ) );

    }


    /**
     * Duplicate an existing theme.
     *
     * @since 1.0
     */
    public function duplicate() {

        check_admin_referer( 'megamenu_duplicate_theme' );

        $theme = esc_attr( $_GET['theme_id'] );

        $copy = $this->themes[$theme];
        $copy['title'] = $copy['title'] . " Copy";

        $saved_themes = get_site_option( "megamenu_themes" );

        $next_id = $this->get_next_theme_id();

        $new_theme_id = "custom_theme_" . $next_id;

        $saved_themes[ 'custom_theme_' . $next_id ] = $copy;

        update_site_option( "megamenu_themes", $saved_themes );

        do_action("megamenu_after_theme_duplicate");

        wp_redirect( admin_url( "themes.php?page=megamenu_theme_editor&theme={$new_theme_id}&duplicated=true") );

    }


    /**
     * Delete a theme
     * 
     * @since 1.0
     */
    public function delete() {

        check_admin_referer( 'megamenu_delete_theme' );

        $theme = esc_attr( $_GET['theme_id'] );

        if ( $this->theme_is_being_used_by_menu( $theme ) ) {

            wp_redirect( admin_url( "themes.php?page=megamenu_theme_editor&theme={$theme}&deleted=false") );
            return;
        }

        $saved_themes = get_site_option( "megamenu_themes" );

        if ( isset( $saved_themes[$theme] ) ) {
            unset( $saved_themes[$theme] );
        }

        update_site_option( "megamenu_themes", $saved_themes );

        do_action("megamenu_after_theme_delete");

        wp_redirect( admin_url( "themes.php?page=megamenu_theme_editor&theme=default&deleted=true") );

    }


    /**
     * Revert a theme (only available for default themes, you can't revert a custom theme)
     *
     * @since 1.0
     */
    public function revert() {

        check_admin_referer( 'megamenu_revert_theme' );

        $theme = esc_attr( $_GET['theme_id'] );

        $saved_themes = get_site_option( "megamenu_themes" );

        if ( isset( $saved_themes[$theme] ) ) {
            unset( $saved_themes[$theme] );
        }

        update_site_option( "megamenu_themes", $saved_themes );

        do_action("megamenu_after_theme_revert");

        wp_redirect( admin_url( "themes.php?page=megamenu_theme_editor&theme={$theme}&reverted=true") );

    }


    /**
     * Create a new custom theme
     *
     * @since 1.0
     */
    public function create() {

        check_admin_referer( 'megamenu_create_theme' );

        $saved_themes = get_site_option( "megamenu_themes" );

        $next_id = $this->get_next_theme_id();

        $new_theme_id = "custom_theme_" . $next_id;

        $new_theme = $this->themes['default'];

        $new_theme['title'] = "Custom {$next_id}";

        $saved_themes[$new_theme_id] = $new_theme;

        update_site_option( "megamenu_themes", $saved_themes );

        do_action("megamenu_after_theme_create");

        wp_redirect( admin_url( "themes.php?page=megamenu_theme_editor&theme={$new_theme_id}&created=true") );

    }


    /**
     * Returns the next available custom theme ID
     *
     * @since 1.0
     */
    public function get_next_theme_id() {
        
        $last_id = 0;

        if ( $saved_themes = get_site_option( "megamenu_themes" ) ) {

            foreach ( $saved_themes as $key => $value ) {
                $parts = explode( "_", $key );
                $theme_id = end( $parts );

                if ($theme_id > $last_id) {
                    $last_id = $theme_id;
                }
            }

        }

        $next_id = $last_id + 1;

        return $next_id;
    }


    /**
     * Checks to see if a certain theme is in use.
     *
     * @since 1.0
     * @param string $theme
     */
    public function theme_is_being_used_by_menu( $theme ) {
        $settings = get_site_option( "megamenu_settings" );

        if ( ! $settings ) {
            return false;
        }

        $locations = get_nav_menu_locations();

        if ( count( $locations ) ) {

            foreach ( $locations as $location => $menu_id ) {

                if ( isset( $settings[ $location ]['theme'] ) && $settings[ $location ]['theme'] == $theme ) {
                    return true;
                }

            }

        }

        return false;
    }


    /**
     * Adds the "Menu Themes" menu item and page.
     *
     * @since 1.0
     */
    public function megamenu_themes_page() {

        $page = add_theme_page(__('Mega Menu Themes', 'megamenu'), __('Menu Themes', 'megamenu'), 'edit_theme_options', 'megamenu_theme_editor', array($this, 'theme_editor' ) );
    
    }


    /**
     * Main Menu Themes page content
     *
     * @since 1.0
     */
    public function theme_editor() {

        ?>

        <div class='megamenu_wrap'>
            <div class='megamenu_right'>
                <div class='theme_settings'>
                    <?php $this->print_messages(); ?>
                    <?php echo $this->form(); ?>
                </div>
            </div>
        </div>

        <div class='megamenu_left'>
            <h4><?php _e("Select theme to edit", "megamenu"); ?></h4>
            <ul class='megamenu_theme_selector'>
                <?php echo $this->theme_selector(); ?>
            </ul>
            <a href='<?php echo wp_nonce_url(admin_url("admin-post.php?action=megamenu_add_theme"), 'megamenu_create_theme') ?>'><?php _e("Create a new theme", "megamenu"); ?></a>
        </div>

        <?php
    }


    /**
     * Display messages to the user
     *
     * @since 1.0
     */
    public function print_messages() {

        $style_manager = new Mega_Menu_Style_Manager();

        $test = $style_manager->generate_css_for_location( 'test', $this->active_theme, 0 );

        if ( is_wp_error( $test ) ) {
            echo "<p class='fail'>" . $test->get_error_message() . "</p>";
        }

        if ( isset( $_GET['deleted'] ) && $_GET['deleted'] == 'false' ) {
            echo "<p class='fail'>" . __("Failed to delete theme. The theme is in use by a menu.") . "</p>";
        }

        if ( isset( $_GET['deleted'] ) && $_GET['deleted'] == 'true' ) {
            echo "<p class='success'>" . __("Theme Deleted") . "</p>";
        }

        if ( isset( $_GET['duplicated'] ) ) {
            echo "<p class='success'>" . __("Theme Duplicated") . "</p>";
        }

        if ( isset( $_GET['saved'] ) ) {
            echo "<p class='success'>" . __("Changes Saved") . "</p>";
        }

        if ( isset( $_GET['reverted'] ) ) {
            echo "<p class='success'>" . __("Theme Reverted") . "</p>";
        }

        if ( isset( $_GET['created'] ) ) {
            echo "<p class='success'>" . __("New Theme Created") . "</p>";
        }

    }


    /**
     * Lists the available themes
     *
     * @since 1.0
     */
    public function theme_selector() {

        $list_items = "";

        foreach ( $this->themes as $id => $theme ) {
            $class = $id == $this->id ? 'mega_active' : '';

            $style_manager = new Mega_Menu_Style_Manager();
            $test = $style_manager->generate_css_for_location( 'tmp-location', $theme, 0 );
            $error = is_wp_error( $test ) ? 'error' : '';

            $list_items .= "<li class='{$class} {$error}'><a href='" . admin_url("themes.php?page=megamenu_theme_editor&theme={$id}") . "'>{$theme['title']}</a></li>";
        }

        return $list_items;

    }


    /**
     * Checks to see if a given string contains any of the provided search terms
     *
     * @param srgin $key
     * @param array $needles
     * @since 1.0
     */
    private function string_contains( $key, $needles ) {

        foreach ( $needles as $needle ) {

            if ( strpos( $key, $needle ) !== FALSE ) { 
                return true;
            }
        }

        return false;

    }


    /**
     * Displays the theme editor form.
     *
     * @since 1.0
     */
    public function form() {
        
        ?>

        <form action="<?php echo admin_url('admin-post.php'); ?>" method="post">
            <input type="hidden" name="theme_id" value="<?php echo $this->id; ?>" />
            <input type="hidden" name="action" value="megamenu_save_theme" />
            <?php wp_nonce_field( 'megamenu_save_theme' ); ?>
        <?php 

            $sorted_settings = array(

                'general' => array(
                    'title' => __("General Settings", "megamenu"),
                    'settings' => array()
                ),
                'container' => array(
                    'title' => __("Menu Bar", "megamenu"),
                    'settings' => array()
                ),
                'top_level_menu_item' => array(
                    'title' => __("Top Level Menu Items", "megamenu"),
                    'settings' => array()
                ),
                'panel' => array(
                    'title' => __("Mega Panels", "megamenu"),
                    'settings' => array()
                ),
                'flyout' => array(
                    'title' => __("Flyout Menus", "megamenu"),
                    'settings' => array()
                ),
                'custom_css' => array(
                    'title' => __("Custom SCSS", "megamenu"),
                    'settings' => array()
                )
                
            );

            foreach ( $this->active_theme as $key => $value ) {

                if ( $this->string_contains( $key, array( 'container' ) ) ) {
                    $sorted_settings['container']['settings'][$key] = $value;
                } 
                else if ( $this->string_contains( $key, array( 'menu_item' ) ) ) {
                    $sorted_settings['top_level_menu_item']['settings'][$key] = $value;
                }
                else if ( $this->string_contains( $key, array ( 'panel') ) ) {
                    $sorted_settings['panel']['settings'][$key] = $value;
                }
                else if ( $this->string_contains( $key, array ( 'flyout') ) ) {
                    $sorted_settings['flyout']['settings'][$key] = $value;
                }
                else if ( $this->string_contains( $key, array ( 'custom_css') ) ) {
                    $sorted_settings['custom_css']['settings'][$key] = $value;
                }
                else {
                    $sorted_settings['general']['settings'][$key] = $value;
                }
                
            }

            echo "<h3>" . __("Editing Theme: ", "megamenu") . $this->active_theme['title'] . "</h3>";

            foreach ($sorted_settings as $section => $content ) {

                echo "<h4>" . $content['title'] . "</h4>";

                foreach ( $content['settings'] as $key => $value ) {

                    echo "<div class='row {$key}'><h5>" . ucwords(str_replace(array("_", "container", "menu item", "panel"), array(" ", "", "", ""), $key)) . "</h5>";

                    if ( $this->string_contains( $key, array( 'custom' ) ) ) {
                        $this->print_theme_textarea_option( $key, $value );
                    }
                    else if ( $this->string_contains( $key, array( 'color', 'background' ) ) ) {
                        $this->print_theme_color_option( $key, $value );
                    }
                    else if ( $this->string_contains( $key, array( 'weight' ) ) ) {
                        $this->print_theme_weight_option( $key, $value );
                    }
                    else if ( $this->string_contains( $key, array( 'font_size' ) ) ) {
                        $this->print_theme_freetext_option( $key, $value );
                    }
                    else if ( $this->string_contains( $key, array( 'font' ) ) ) {
                        $this->print_theme_font_option( $key, $value );
                    }
                    else if ( $this->string_contains( $key, array( 'transform' ) ) ) {
                        $this->print_theme_transform_option( $key, $value );
                    }
                    else if ( $this->string_contains( $key, array( 'arrow' ) ) ) {
                        $this->print_theme_arrow_option( $key, $value );
                    } 
                    else if ( $this->string_contains( $key, array( 'title', 'top', 'left', 'bottom', 'right', 'spacing', 'height' ) ) ) {
                        $this->print_theme_freetext_option( $key, $value );
                    }
                    else {
                        $this->print_theme_freetext_option( $key, $value );
                    }

                    echo "</div>";
                }

            }

            submit_button();

            ?>

            <?php if ( $this->string_contains( $this->id, array("custom") ) ) : ?>

                <a class='delete confirm' href='<?php echo wp_nonce_url(admin_url("admin-post.php?action=megamenu_delete_theme&theme_id={$this->id}"), 'megamenu_delete_theme') ?>'><?php _e("Delete Theme", "megamenu"); ?></a>

            <?php else : ?>

                <a class='revert confirm' href='<?php echo wp_nonce_url(admin_url("admin-post.php?action=megamenu_revert_theme&theme_id={$this->id}"), 'megamenu_revert_theme') ?>'><?php _e("Revert Changes", "megamenu"); ?></a>

            <?php endif; ?>

            <a class='duplicate' href='<?php echo wp_nonce_url(admin_url("admin-post.php?action=megamenu_duplicate_theme&theme_id={$this->id}"), 'megamenu_duplicate_theme') ?>'><?php _e("Duplicate Theme", "megamenu"); ?></a>

            </form>

        <?php

    }

    /**
     * Print an arrow dropdown selection box
     *
     * @since 1.0
     * @param string $key
     * @param string $value
     */
    public function print_theme_arrow_option( $key, $value ) {
        
        $arrow_icons = $this->arrow_icons(); 

        ?>
            <span class="selected_icon <?php echo $arrow_icons[$value] ?>"></span>
            <select class='icon_dropdown' name='settings[<?php echo $key ?>]'>

                <?php 

                    echo "<option value='disabled'>" . __("Disabled", "megamenu") . "</option>";

                    foreach ($arrow_icons as $code => $class) {
                        $name = str_replace('dashicons-', '', $class);
                        $name = ucwords(str_replace(array('-','arrow'), ' ', $name));
                        echo "<option data-class='{$class}' value='{$code}' " . selected( $value == $code ) . ">{$name}</option>";
                    }

                ?>
            </select>

        <?php
    }

    /**
     * Print a colorpicker
     *
     * @since 1.0
     * @param string $key
     * @param string $value
     */
    public function print_theme_color_option( $key, $value ) {

        if ( $value == 'transparent' ) {
            $value = 'rgba(0,0,0,0)';
        }

        echo "<input type='text' class='mm_colorpicker' name='settings[$key]' value='{$value}' />";

    }


    /**
     * Print a font weight selector
     *
     * @since 1.0
     * @param string $key
     * @param string $value
     */
    public function print_theme_weight_option( $key, $value ) {

        echo "<select name='settings[$key]'>";
        echo "    <option value='normal' " . selected( $value, 'normal', true) . ">" . __("Normal", "megamenu") . "</option>";
        echo "    <option value='bold'"    . selected( $value, 'bold', true) . ">" . __("Bold", "megamenu") . "</option>";
        echo "</select>";

    }


    /**
     * Print a font transform selector
     *
     * @since 1.0
     * @param string $key
     * @param string $value
     */
    public function print_theme_transform_option( $key, $value ) {

        echo "<select name='settings[$key]'>";
        echo "    <option value='none' "      . selected( $value, 'none', true) . ">" . __("Normal", "megamenu") . "</option>";
        echo "    <option value='capitalize'" . selected( $value, 'capitalize', true) . ">" . __("Capitalize", "megamenu") . "</option>";
        echo "    <option value='uppercase'"  . selected( $value, 'uppercase', true) . ">" . __("Uppercase", "megamenu") . "</option>";
        echo "    <option value='lowercase'"  . selected( $value, 'lowercase', true) . ">" . __("Lowercase", "megamenu") . "</option>";
        echo "</select>";

    }


    /**
     * Print a textarea
     *
     * @since 1.0
     * @param string $key
     * @param string $value
     */
    public function print_theme_textarea_option( $key, $value ) {

        echo "<textarea name='settings[$key]'>" . stripslashes( $value ) . "</textarea>";

    }


    /**
     * Print a font selector
     *
     * @since 1.0
     * @param string $key
     * @param string $value
     */
    public function print_theme_font_option( $key, $value ) {

        echo "<select name='settings[$key]'>";

        echo "<option value='inherit'>" . __("Theme Default", "megamenu") . "</option>";

        foreach ( $this->fonts() as $font ) {
            echo "<option value=\"{$font}\" " . selected( $font, $value ) . ">{$font}</option>";
        }

        echo "</select>";
    }


    /**
     * Print a text input
     *
     * @since 1.0
     * @param string $key
     * @param string $value
     */
    public function print_theme_freetext_option( $key, $value ) {

        echo "<input type='text' name='settings[$key]' value='{$value}' />";

    }


    /**
     * Returns a list of available fonts.
     *
     * @since 1.0
     */
    public function fonts() {

        $fonts = array(
            "Georgia, serif",
            "Palatino Linotype, Book Antiqua, Palatino, serif",
            "Times New Roman, Times, serif",
            "Arial, Helvetica, sans-serif",
            "Arial Black, Gadget, sans-serif",
            "Comic Sans MS, cursive, sans-serif",
            "Impact, Charcoal, sans-serif",
            "Lucida Sans Unicode, Lucida Grande, sans-serif",
            "Tahoma, Geneva, sans-serif",
            "Trebuchet MS, Helvetica, sans-serif",
            "Verdana, Geneva, sans-serif",
            "Courier New, Courier, monospace",
            "Lucida Console, Monaco, monospace"
        );

        $fonts = apply_filters( "megamenu_fonts", $fonts );

        return $fonts;

    }


    /**
     * List of all available arrow DashIcon classes.
     *
     * @since 1.0
     * @return array - Sorted list of icon classes
     */
    private function arrow_icons() {

        $icons = array(
            'dash-f142' => 'dashicons-arrow-up',
            'dash-f140' => 'dashicons-arrow-down',
            'dash-f139' => 'dashicons-arrow-right',
            'dash-f141' => 'dashicons-arrow-left',
            'dash-f342' => 'dashicons-arrow-up-alt',
            'dash-f346' => 'dashicons-arrow-down-alt',
            'dash-f344' => 'dashicons-arrow-right-alt',
            'dash-f340' => 'dashicons-arrow-left-alt',
            'dash-f343' => 'dashicons-arrow-up-alt2',
            'dash-f347' => 'dashicons-arrow-down-alt2',
            'dash-f345' => 'dashicons-arrow-right-alt2',
            'dash-f341' => 'dashicons-arrow-left-alt2',
        );

        $icons = apply_filters( "megamenu_arrow_icons", $icons );

        return $icons;
        
    }

    /**
     * Enqueue required CSS and JS for Mega Menu
     *
     * @since 1.0
     */
    public function enqueue_theme_editor_scripts( $hook ) {

        if( 'appearance_page_megamenu_theme_editor' != $hook )
            return;

        wp_enqueue_style( 'spectrum', MEGAMENU_BASE_URL . 'js/spectrum/spectrum.css', false, MEGAMENU_VERSION );
        wp_enqueue_style( 'mega-menu-theme-editor', MEGAMENU_BASE_URL . 'css/theme-editor.css', false, MEGAMENU_VERSION );

        wp_enqueue_script( 'mega-menu-theme-editor', MEGAMENU_BASE_URL . 'js/theme-editor.js', array('jquery'), MEGAMENU_VERSION );
        wp_enqueue_script( 'spectrum', MEGAMENU_BASE_URL . 'js/spectrum/spectrum.js', array( 'jquery' ), MEGAMENU_VERSION );

        wp_localize_script( 'mega-menu-theme-editor', 'megamenu_theme_editor',
            array(
                'confirm' => __("Are you sure?", "megamenu")
            )
        );
    }

}

endif;