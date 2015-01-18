<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // disable direct access
}

if ( ! class_exists( 'Mega_Menu_Settings' ) ) :

/**
 * Handles all admin related functionality.
 */
class Mega_Menu_Settings{


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

        add_action( 'admin_post_megamenu_save_theme', array( $this, 'save_theme') );
        add_action( 'admin_post_megamenu_add_theme', array( $this, 'create_theme') );
        add_action( 'admin_post_megamenu_delete_theme', array( $this, 'delete_theme') );
        add_action( 'admin_post_megamenu_revert_theme', array( $this, 'revert_theme') );
        add_action( 'admin_post_megamenu_duplicate_theme', array( $this, 'duplicate_theme') );

        add_action( 'admin_post_megamenu_save_settings', array( $this, 'save_settings') );
        add_action( 'admin_post_megamenu_clear_cache', array( $this, 'clear_cache') );
        add_action( 'admin_post_megamenu_delete_data', array( $this, 'delete_data') );

        add_action( 'admin_menu', array( $this, 'megamenu_themes_page') );
        add_action( "admin_enqueue_scripts", array( $this, 'enqueue_theme_editor_scripts' ) );

    }

    /**
     *
     * @since 1.4
     */
    public function init() {

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
    public function save_theme() {

        check_admin_referer( 'megamenu_save_theme' );

        $theme = esc_attr( $_POST['theme_id'] );

        $saved_themes = get_site_option( "megamenu_themes" );

        if ( isset( $saved_themes[ $theme ] ) ) {
            unset( $saved_themes[ $theme ] );
        }

        $saved_themes[ $theme ] = array_map( 'esc_attr', $_POST['settings'] );

        update_site_option( "megamenu_themes", $saved_themes );

        do_action("megamenu_after_theme_save");

        wp_redirect( admin_url( "themes.php?page=megamenu_settings&tab=theme_editor&theme={$theme}&saved=true" ) );

    }


    /**
     * Clear the CSS cache.
     *
     * @since 1.5
     */
    public function clear_cache() {

        check_admin_referer( 'megamenu_clear_cache' );

        delete_site_transient( 'megamenu_css' );

        wp_redirect( admin_url( "themes.php?page=megamenu_settings&tab=tools&clear_cache=true" ) );

    }


    /**
     * Deletes all Max Mega Menu data from the database
     *
     * @since 1.5
     */
    public function delete_data() {

        check_admin_referer( 'megamenu_delete_data' );

        // delete menu settings
        delete_site_option("megamenu_settings");

        // delete all widgets assigned to menus
        $widget_manager = new Mega_Menu_Widget_Manager();

        if ( $mega_menu_widgets = $widget_manager->get_mega_menu_sidebar_widgets() ) {

            foreach ( $mega_menu_widgets as $widget_id ) {

                $widget_manager->delete_widget( $widget_id );

            }

        }

        // delete all mega menu metadata stored against menu items
        delete_metadata( 'post', 0, '_megamenu', '', true );

        // clear cache
        delete_site_transient( "megamenu_css" );

        // delete custom themes
        delete_site_option( "megamenu_themes" );

        wp_redirect( admin_url( "themes.php?page=megamenu_settings&tab=tools&delete_data=true" ) );

    }

    /**
     * Save menu general settings.
     *
     * @since 1.0
     */
    public function save_settings() {

        check_admin_referer( 'megamenu_save_settings' );

        $submitted_settings = array_map( 'esc_attr', $_POST['settings'] );

        if ( ! isset( $submitted_settings['getting_started'] ) ) {

            $submitted_settings['getting_started'] = 'disabled';

        }

        if ( ! get_site_option( 'megamenu_settings' ) ) {

            add_site_option( 'megamenu_settings', $submitted_settings );

        } else {

            $existing_settings = get_site_option( 'megamenu_settings' );

            $new_settings = array_merge( $existing_settings, $submitted_settings );
 
            update_site_option( 'megamenu_settings', $new_settings );

        }

        do_action("megamenu_after_save_general_settings");

        wp_redirect( admin_url( "themes.php?page=megamenu_settings&tab=general_settings&saved=true" ) );

    }


    /**
     * Duplicate an existing theme.
     *
     * @since 1.0
     */
    public function duplicate_theme() {

        check_admin_referer( 'megamenu_duplicate_theme' );

        $this->init();

        $theme = esc_attr( $_GET['theme_id'] );

        $copy = $this->themes[$theme];

        $saved_themes = get_site_option( "megamenu_themes" );

        $next_id = $this->get_next_theme_id();

        $copy['title'] = $copy['title'] . " " . __('Copy', 'megamenu');

        $new_theme_id = "custom_theme_" . $next_id;

        $saved_themes[ $new_theme_id ] = $copy;

        update_site_option( "megamenu_themes", $saved_themes );

        do_action("megamenu_after_theme_duplicate");

        wp_redirect( admin_url( "themes.php?page=megamenu_settings&tab=theme_editor&theme={$new_theme_id}&duplicated=true") );

    }


    /**
     * Delete a theme
     * 
     * @since 1.0
     */
    public function delete_theme() {

        check_admin_referer( 'megamenu_delete_theme' );

        $theme = esc_attr( $_GET['theme_id'] );

        if ( $this->theme_is_being_used_by_menu( $theme ) ) {

            wp_redirect( admin_url( "themes.php?page=megamenu_settings&tab=theme_editor&theme={$theme}&deleted=false") );
            return;
        }

        $saved_themes = get_site_option( "megamenu_themes" );

        if ( isset( $saved_themes[$theme] ) ) {
            unset( $saved_themes[$theme] );
        }

        update_site_option( "megamenu_themes", $saved_themes );

        do_action("megamenu_after_theme_delete");

        wp_redirect( admin_url( "themes.php?page=megamenu_settings&tab=theme_editor&theme=default&deleted=true") );

    }


    /**
     * Revert a theme (only available for default themes, you can't revert a custom theme)
     *
     * @since 1.0
     */
    public function revert_theme() {

        check_admin_referer( 'megamenu_revert_theme' );

        $theme = esc_attr( $_GET['theme_id'] );

        $saved_themes = get_site_option( "megamenu_themes" );

        if ( isset( $saved_themes[$theme] ) ) {
            unset( $saved_themes[$theme] );
        }

        update_site_option( "megamenu_themes", $saved_themes );

        do_action("megamenu_after_theme_revert");

        wp_redirect( admin_url( "themes.php?page=megamenu_settings&tab=theme_editor&theme={$theme}&reverted=true") );

    }


    /**
     * Create a new custom theme
     *
     * @since 1.0
     */
    public function create_theme() {

        check_admin_referer( 'megamenu_create_theme' );

        $this->init();

        $saved_themes = get_site_option( "megamenu_themes" );

        $next_id = $this->get_next_theme_id();

        $new_theme_id = "custom_theme_" . $next_id;

        $new_theme = $this->themes['default'];

        $new_theme['title'] = "Custom {$next_id}";

        $saved_themes[$new_theme_id] = $new_theme;

        update_site_option( "megamenu_themes", $saved_themes );

        do_action("megamenu_after_theme_create");

        wp_redirect( admin_url( "themes.php?page=megamenu_settings&tab=theme_editor&theme={$new_theme_id}&created=true") );

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

                if ( strpos( $key, 'custom_theme' ) !== FALSE ) {

                    $parts = explode( "_", $key );
                    $theme_id = end( $parts );

                    if ($theme_id > $last_id) {
                        $last_id = $theme_id;
                    }       

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

                if ( has_nav_menu( $location ) && isset( $settings[ $location ]['theme'] ) && $settings[ $location ]['theme'] == $theme ) {
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

        $page = add_theme_page(__('Max Mega Menu', 'megamenu'), __('Max Mega Menu', 'megamenu'), 'edit_theme_options', 'megamenu_settings', array($this, 'page' ) );
    
    }


    /**
     * Content for 'Getting Started' tab
     *
     * @since 1.4
     */
    public function getting_started() {

        ?>

        <h4 class='first'><?php _e("Menu Setup", "megamenu"); ?></h4>

        <p><?php _e("Under", "megamenu"); ?> <a href='<?php echo admin_url( "nav-menus.php"); ?>'><?php _e("Appearance > Menus", "megamenu"); ?></a> <?php _e(", create a new menu (or use an existing menu). Ensure the Menu is tagged to a Theme Location under 'Menu Settings'.", "megamenu"); ?></p>

        <p><?php _e("Once your menu is created and assigned to a location, you will see the settings for Mega Menu on the left hand side (under 'Mega Menu Settings').", "megamenu"); ?></p>

        <p><?php _e("Check the 'Enable' checkbox and click 'Save'. Your menu will now be turned into a Mega Menu for the relevant Theme Location.", "megamenu"); ?></p>

        <h4><?php _e("Creating Mega Menus", "megamenu"); ?></h4>

        <p><?php _e("A Mega Menu is the name given to a large panel of content which is displayed below a menu item when the user clicks or hovers over the menu item.", "megamenu"); ?><p>

        <p><?php _e("To create a Mega Menu Panel for one of your menu items:", "megamenu"); ?></p>

        <ul class='bullets'>
            <li><?php _e("Go to", "megamenu"); ?> <a href='<?php echo admin_url( "nav-menus.php"); ?>'><?php _e("Appearance > Menus", "megamenu"); ?></a></li>
            <li><?php _e("Hover over the Menu Item which you wish to add a panel for (the Menu Item must be positioned at the top level)", "megamenu"); ?></li>
            <li><?php _e("Click the 'Mega Menu' link, the menu item manager will load in a lightbox.", "megamenu"); ?></li>
            <li><?php _e("Use the Widget Manager to add widgets to the panel. The Widget Manager will let you move, resize and configure your widgets.", "megamenu"); ?></li>
            <li><i><?php _e("If you create a mega mega menu on a top level menu item, but your menu item also has sub menu items, the sub menu items will be listed before the Panel Widgets when you view the menu on your site.", "megamenu"); ?></i></li>
        </ul>

        <h4><?php _e("Customising your Menu", "megamenu"); ?></h4>

        <p><?php _e("You'll find a theme editor to the left of this article (under 'Menu Themes') which allows you to edit the appearance of your Mega Menus.", "megamenu"); ?></p>

        <p><?php _e("The Theme Editor allows you to modify all aspects of the Menu styling, including the font, color and size (height) of your menus.", "megamenu"); ?></p>

        <p><?php _e("To apply your new theme to a menu go back to", "megamenu"); ?> <a href='<?php echo admin_url( "nav-menus.php"); ?>'><?php _e("Appearance > Menus", "megamenu"); ?></a> <?php _e("and select your new theme from the 'Theme' dropdown in the Mega Menu Settings.", "megamenu"); ?></p>

        <h4><?php _e("More information", "megamenu"); ?></h4>

        <ul>
            <li><a href='http://www.maxmegamenu.com' target='_blank'><?php _e("Plugin homepage", "megamenu"); ?></a></li>
            <li><a href='https://wordpress.org/support/plugin/megamenu/' target='_blank'><?php _e("Support forums", "megamenu"); ?></a></li>
            <li><?php _e("Like the plugin?", "megamenu"); ?> <a href='https://wordpress.org/support/view/plugin-reviews/megamenu#postform' target='_blank'><?php _e("Please leave a review!", "megamenu"); ?></a></li>
        </ul>

        <?php
    }

    /**
     *
     * @since 1.4
     */
    public function getting_started_page_is_enabled() {

        $saved_settings = get_site_option( "megamenu_settings" );

        if ( ! isset( $saved_settings['getting_started'] ) ) {
            return true;
        }

        if ( $saved_settings['getting_started'] == 'enabled' ) {
            return true;
        }

        return false;

    }

    /**
     * Content for 'Settings' tab
     *
     * @since 1.4
     */
    public function settings_page() {

        $saved_settings = get_site_option( "megamenu_settings" );

        $css = isset( $saved_settings['css'] ) ? $saved_settings['css'] : 'ajax';

        ?>

        <div class='menu_settings'>

            <form action="<?php echo admin_url('admin-post.php'); ?>" method="post">
                <input type="hidden" name="action" value="megamenu_save_settings" />
                <?php wp_nonce_field( 'megamenu_save_settings' ); ?>
                
                <h4 class='first'><?php _e("Global Settings", "megamenu"); ?></h4>

                <table>
                    <tr>
                        <td class='mega-name'>
                            <?php _e("Getting Started", "megamenu"); ?>
                            <div class='mega-description'>
                            </div>
                        </td>
                        <td class='mega-value'>
                            <label><input type='checkbox' name='settings[getting_started]' value='enabled' <?php echo checked( $this->getting_started_page_is_enabled() ); ?> /><?php _e("Show the Getting Started page", "megamenu"); ?></label>
                        </td>
                    </tr>
                    <tr>
                        <td class='mega-name'>
                            <?php _e("CSS Output", "megamenu"); ?>
                            <div class='mega-description'>
                            </div>
                        </td>
                        <td class='mega-value'>
                            <select name='settings[css]' id='mega_css'>
                                <option value='ajax' <?php echo selected( $css == 'ajax'); ?>><?php _e("Enqueue dynamically via admin-ajax.php", "megamenu"); ?></option>
                                <option value='head' <?php echo selected( $css == 'head'); ?>><?php _e("Output in &lt;head&gt;", "megamenu"); ?></option>
                                <option value='disabled' <?php echo selected( $css == 'disabled'); ?>><?php _e("Don't output CSS", "megamenu"); ?></option>
                            <select>
                            <div class='mega-description'>
                                <div class='ajax' style='display: <?php echo $css == 'ajax' ? 'block' : 'none' ?>'><?php _e("Default. CSS will be enqueued dynamically through admin-ajax.php and loaded from the cache.", "megamenu"); ?></div>
                                <div class='head' style='display: <?php echo $css == 'head' ? 'block' : 'none' ?>'><?php _e("CSS will be loaded from the cache in a &lt;style&gt; tag in the &lt;head&gt; of the page.", "megamenu"); ?></div>
                                <div class='disabled' style='display: <?php echo $css == 'disabled' ? 'block' : 'none' ?>'><?php _e("CSS will not be output, you must enqueue the CSS for the menu manually.", "megamenu"); ?></div>
                            </div>
                        </td>
                    </tr>
                </table>

                <h4><?php _e("Menu Settings", "megamenu"); ?></h4>

                <p><i><?php _e("Menu specific settings (e.g, click or hover event, menu theme, transition effect) can be found under", "megamenu"); ?> <a href='<?php echo admin_url( "nav-menus.php"); ?>'><?php _e("Appearance > Menus", "megamenu"); ?></a></i></p>

                <?php

                    submit_button();

                ?>
            </form>
        </div>

        <?php
    }



    /**
     * Content for 'Tools' tab
     *
     * @since 1.4
     */
    public function tools_page() {

        ?>

        <div class='menu_settings'>

            <form action="<?php echo admin_url('admin-post.php'); ?>" method="post">
                <?php wp_nonce_field( 'megamenu_clear_cache' ); ?>
                <input type="hidden" name="action" value="megamenu_clear_cache" />

                <h4 class='first'><?php _e("Cache", "megamenu"); ?></h4>

                <input type='submit' class='button button-primary' name='clear-cache' value='<?php _e("Empty Cache", "megamenu"); ?>' />
                <p><?php _e("Clear the CSS cache.", "megamenu"); ?></p>
            </form>

            <form action="<?php echo admin_url('admin-post.php'); ?>" method="post">
                <?php wp_nonce_field( 'megamenu_delete_data' ); ?>
                <input type="hidden" name="action" value="megamenu_delete_data" />

                <h4><?php _e("Plugin Data", "megamenu"); ?></h4>

                <input type='submit' class='button button-primary confirm' name='clear-cache' value='<?php _e("Delete Data", "megamenu"); ?>' />
                <p><?php _e("Delete all saved Max Mega Menu plugin data from the database. Use with caution!", "megamenu"); ?></p>
            </form>
        </div>

        <?php
    }


    /**
     * Main settings page wrapper.
     *
     * @since 1.4
     */
    public function page() {

        ?>

            
            <div class='megamenu_outer_wrap'>

                <div class='megamenu_header'>
                    <h2><?php _e("Max Mega Menu", "megamenu"); ?> <small>v<?php echo MEGAMENU_VERSION; ?></small></h2>
                </div>
                <div class='megamenu_wrap'>
                    <div class='megamenu_right'>
                        <?php $this->print_messages(); ?>

                        <?php 

                        if ( isset( $_GET['tab'] ) ) {

                            switch( $_GET['tab'] ) {
                                case "theme_editor" :
                                    $this->theme_editor();
                                    $active_tab = 'theme_editor';
                                    break;
                                case "general_settings" :
                                    $this->settings_page();
                                    $active_tab = 'general_settings';
                                    break;
                                case "tools" :
                                    $this->tools_page();
                                    $active_tab = 'tools';
                                    break;
                                default :
                                    if ($this->getting_started_page_is_enabled() ) {
                                        $this->getting_started();
                                        $active_tab = 'getting_started';
                                    } else {
                                        $this->settings_page();
                                        $active_tab = 'general_settings';
                                    }
                            }

                        } else {

                            if ($this->getting_started_page_is_enabled() ) {
                                $this->getting_started();
                                $active_tab = 'getting_started';
                            } else {
                                $this->settings_page();
                                $active_tab = 'general_settings';
                            }

                        }

                        ?>
                    </div>
                </div>

                <div class='megamenu_left'>
                    <ul>
                        <?php if ($this->getting_started_page_is_enabled() ) : ?>
                        <li><a class='<?php echo $active_tab == 'getting_started' ? 'active' : '' ?>' href='<?php echo admin_url( "themes.php?page=megamenu_settings&tab=getting_started") ?>'><?php _e("Getting Started", "megamenu"); ?></a></li>                
                        <?php endif; ?>
                        <li><a class='<?php echo $active_tab == 'general_settings' ? 'active' : '' ?>' href='<?php echo admin_url( "themes.php?page=megamenu_settings&tab=general_settings") ?>'><?php _e("Global Settings", "megamenu"); ?></a></li>                
                        <li><a class='<?php echo $active_tab == 'tools' ? 'active' : '' ?>' href='<?php echo admin_url( "themes.php?page=megamenu_settings&tab=tools") ?>'><?php _e("Tools", "megamenu"); ?></a></li>                
                        <li><a class='<?php echo $active_tab == 'theme_editor' ? 'active' : '' ?>' href='<?php echo admin_url( "themes.php?page=megamenu_settings&tab=theme_editor") ?>'><?php _e("Menu Themes", "megamenu"); ?></a></li>
                    </ul>
                </div>

            </div>

        <?php
    }


    /**
     * Display messages to the user
     *
     * @since 1.0
     */
    public function print_messages() {

        $this->init();

        $style_manager = new Mega_Menu_Style_Manager();

        $test = $style_manager->generate_css_for_location( 'test', $this->active_theme, 0 );

        if ( is_wp_error( $test ) ) {
            echo "<p class='fail'>" . $test->get_error_message() . "</p>";
        }

        if ( isset( $_GET['deleted'] ) && $_GET['deleted'] == 'false' ) {
            echo "<p class='fail'>" . __("Failed to delete theme. The theme is in use by a menu.", "megamenu") . "</p>";
        }

        if ( isset( $_GET['clear_cache'] ) && $_GET['clear_cache'] == 'true' ) {
            echo "<p class='success'>" . __("CSS cache cleared", "megamenu") . "</p>";
        }

        if ( isset( $_GET['delete_data'] ) && $_GET['delete_data'] == 'true' ) {
            echo "<p class='success'>" . __("All plugin data removed", "megamenu") . "</p>";
        }


        if ( isset( $_GET['deleted'] ) && $_GET['deleted'] == 'true' ) {
            echo "<p class='success'>" . __("Theme Deleted", "megamenu") . "</p>";
        }

        if ( isset( $_GET['duplicated'] ) ) {
            echo "<p class='success'>" . __("Theme Duplicated", "megamenu") . "</p>";
        }

        if ( isset( $_GET['saved'] ) ) {
            echo "<p class='success'>" . __("Changes Saved", "megamenu") . "</p>";
        }

        if ( isset( $_GET['reverted'] ) ) {
            echo "<p class='success'>" . __("Theme Reverted", "megamenu") . "</p>";
        }

        if ( isset( $_GET['created'] ) ) {
            echo "<p class='success'>" . __("New Theme Created", "megamenu") . "</p>";
        }

    }


    /**
     * Lists the available themes
     *
     * @since 1.0
     */
    public function theme_selector() {

        $list_items = "<select id='theme_selector'>";

        foreach ( $this->themes as $id => $theme ) {
            $selected = $id == $this->id ? 'selected=selected' : '';

            $style_manager = new Mega_Menu_Style_Manager();
            $test = $style_manager->generate_css_for_location( 'tmp-location', $theme, 0 );
            $error = is_wp_error( $test ) ? 'error' : '';

            $list_items .= "<option {$selected} value='" . admin_url("themes.php?page=megamenu_settings&tab=theme_editor&theme={$id}") . "'>{$theme['title']}</option>";
        }

        return $list_items .= "</select>";

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
    public function theme_editor() {
        
        $this->init();

        ?>

        <div class='menu_settings'>

            <div class='theme_selector'>
                <?php _e("Select theme to edit", "megamenu"); ?> <?php echo $this->theme_selector(); ?> <?php _e("or", "megamenu"); ?>
                <a class='' href='<?php echo wp_nonce_url(admin_url("admin-post.php?action=megamenu_add_theme"), 'megamenu_create_theme') ?>'><?php _e("create a new theme", "megamenu"); ?></a>
            </div>
            

            <form action="<?php echo admin_url('admin-post.php'); ?>" method="post">
                <input type="hidden" name="theme_id" value="<?php echo $this->id; ?>" />
                <input type="hidden" name="action" value="megamenu_save_theme" />
                <?php wp_nonce_field( 'megamenu_save_theme' ); ?>
                <h3><?php _e("Editing Theme:", "megamenu"); ?> <?php echo $this->active_theme['title']; ?></h3>
                <h4><?php _e("General Theme Settings", "megamenu"); ?></h4>

                <table>
                    <tr>
                        <td class='mega-name'>
                            <?php _e("Theme Title", "megamenu"); ?>
                            <div class='mega-description'>
                            </div>
                        </td>
                        <td class='mega-value'><?php $this->print_theme_freetext_option( 'title' ); ?></td>
                    </tr>
                    <tr>
                        <td class='mega-name'>
                            <?php _e("Arrow Up", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("Select the 'Up' arrow style.", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'><?php $this->print_theme_arrow_option( 'arrow_up' ); ?></td>
                    </tr>
                    <tr>
                        <td class='mega-name'>
                            <?php _e("Arrow Down", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("Select the 'Down' arrow style.", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'><?php $this->print_theme_arrow_option( 'arrow_down' ); ?></td>
                    </tr>
                    <tr>
                        <td class='mega-name'>
                            <?php _e("Arrow Left", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("Select the 'Left' arrow style.", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'><?php $this->print_theme_arrow_option( 'arrow_left' ); ?></td>
                    </tr>
                    <tr>
                        <td class='mega-name'>
                            <?php _e("Arrow Right", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("Select the 'Right' arrow style.", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'><?php $this->print_theme_arrow_option( 'arrow_right' ); ?></td>
                    </tr>
                    <tr>
                        <td class='mega-name'>
                            <?php _e("Main Font", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("Set the main font to use for panel contents and flyout menu items.", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'>
                            <?php $this->print_theme_color_option( 'font_color' ); ?>
                            <?php $this->print_theme_freetext_option( 'font_size' ); ?>
                            <?php $this->print_theme_font_option( 'font_family' ); ?>
                        </td>
                    </tr>
                    <tr>
                        <td class='mega-name'>
                            <?php _e("Responsive Breakpoint", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("Set the width at which the menu turns into a mobile menu.", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'><?php $this->print_theme_freetext_option( 'responsive_breakpoint' ); ?></td>
                    </tr>
                    <tr>
                        <td class='mega-name'>
                            <?php _e("Line Height", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("Set the general line height to use in the panel contents.", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'><?php $this->print_theme_freetext_option( 'line_height' ); ?></td>
                    </tr>
                    <tr>
                        <td class='mega-name'>
                            <?php _e("Z-Index", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("Set the z-index to ensure the panels appear ontop of other content.", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'><?php $this->print_theme_freetext_option( 'z_index' ); ?></td>
                    </tr>
                </table>

                <h4><?php _e("Menu Bar", "megamenu"); ?></h4>

                <table>
                    <tr>
                        <td class='mega-name'>
                            <?php _e("Menu Background", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("The background color for the main menu bar. Set each value to transparent for a 'button' style menu.", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'>
                            <label>
                                <span class='mega-short-desc'><?php _e("From", "megamenu"); ?></span>
                                <?php $this->print_theme_color_option( 'container_background_from' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("To", "megamenu"); ?></span>
                                <?php $this->print_theme_color_option( 'container_background_to' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td class='mega-name'>
                            <?php _e("Menu Padding", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("Padding for the main menu bar.", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'>
                            <label>
                                <span class='mega-short-desc'><?php _e("Top", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'container_padding_top' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Right", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'container_padding_right' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Bottom", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'container_padding_bottom' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Left", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'container_padding_left' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td class='mega-name'>
                            <?php _e("Rounded Corners", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("Set a border radius on the main menu bar.", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'>
                            <label>
                                <span class='mega-short-desc'><?php _e("Top Left", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'container_border_radius_top_left' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Top Right", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'container_border_radius_top_right' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Bottom Right", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'container_border_radius_bottom_right' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Bottom Left", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'container_border_radius_bottom_left' ); ?>
                            </label>
                        </td>
                    </tr>
                </table>

                <h4><?php _e("Top Level Menu Items", "megamenu"); ?></h4>

                <table>
                    <tr>
                        <td class='mega-name'>
                            <?php _e("Menu Item Background", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("The background color for each top level menu item. Tip: Set these values to transparent if you've already set a background color on the menu container.", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'>
                            <label>
                                <span class='mega-short-desc'><?php _e("From", "megamenu"); ?></span>
                                <?php $this->print_theme_color_option( 'menu_item_background_from' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("To", "megamenu"); ?></span>
                                <?php $this->print_theme_color_option( 'menu_item_background_to' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td class='mega-name'>
                            <?php _e("Menu Item Background (Hover)", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("The background color for a top level menu item (on hover).", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'>
                            <label>
                                <span class='mega-short-desc'><?php _e("From", "megamenu"); ?></span>
                                <?php $this->print_theme_color_option( 'menu_item_background_hover_from' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("To", "megamenu"); ?></span>
                                <?php $this->print_theme_color_option( 'menu_item_background_hover_to' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td class='mega-name'>
                            <?php _e("Menu Item Spacing", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("Define the size of the gap between each top level menu item.", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'>
                            <?php $this->print_theme_freetext_option( 'menu_item_spacing' ); ?>
                        </td>
                    </tr>
                    <tr>
                        <td class='mega-name'>
                            <?php _e("Menu Item Height", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("Define the height of each top level menu item. This value, plus the container top and bottom padding values define the overall height of the menu bar.", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'>
                            <?php $this->print_theme_freetext_option( 'menu_item_link_height' ); ?>
                        </td>
                    </tr>
                    <tr>
                        <td class='mega-name'>
                            <?php _e("Font", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("The font to use for each top level menu item.", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'>
                            <?php $this->print_theme_color_option( 'menu_item_link_color' ); ?>
                            <?php $this->print_theme_freetext_option( 'menu_item_link_font_size' ); ?>
                            <?php $this->print_theme_font_option( 'menu_item_link_font' ); ?>
                            <?php $this->print_theme_weight_option( 'menu_item_link_weight' ); ?>
                        </td>
                    </tr>
                    <tr>
                        <td class='mega-name'>
                            <?php _e("Font (Hover)", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("Set the font to use for each top level menu item (on hover).", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'>
                            <?php $this->print_theme_color_option( 'menu_item_link_color_hover' ); ?>
                            <?php $this->print_theme_weight_option( 'menu_item_link_weight_hover' ); ?>
                        </td>
                    </tr>
                    <tr>
                        <td class='mega-name'>
                            <?php _e("Text Transform", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("Set the padding for the headings. Use this to set the gap between the widget heading and the widget content.", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'>
                            <?php $this->print_theme_transform_option( 'menu_item_link_text_transform' ); ?>
                        </td>
                    </tr>
                    <tr>
                        <td class='mega-name'>
                            <?php _e("Menu Item Padding", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("Set the padding for each top level menu item.", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'>
                            <label>
                                <span class='mega-short-desc'><?php _e("Top", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'menu_item_link_padding_top' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Right", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'menu_item_link_padding_right' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Bottom", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'menu_item_link_padding_bottom' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Left", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'menu_item_link_padding_left' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td class='mega-name'>
                            <?php _e("Menu Item Rounded Corners", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("Set rounded corners for each top level menu item.", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'>
                            <label>
                                <span class='mega-short-desc'><?php _e("Top Left", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'menu_item_link_border_radius_top_left' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Top Right", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'menu_item_link_border_radius_top_right' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Bottom Right", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'menu_item_link_border_radius_bottom_right' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Bottom Left", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'menu_item_link_border_radius_bottom_left' ); ?>
                            </label>
                        </td>
                    </tr>
                </table>

                <h4><?php _e("Mega Panels", "megamenu"); ?></h4>

                <table>
                    <tr>
                        <td class='mega-name'>
                            <?php _e("Panel Background", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("Set a background color for a whole panel.", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'>
                            <label>
                                <span class='mega-short-desc'><?php _e("From", "megamenu"); ?></span>
                                <?php $this->print_theme_color_option( 'panel_background_from' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("To", "megamenu"); ?></span>
                                <?php $this->print_theme_color_option( 'panel_background_to' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td class='mega-name'>
                            <?php _e("Panel Width", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("Mega Panel width. Note: A 100% wide panel will only ever be as wide as the menu itself. For a fixed panel width set this to a pixel value.", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'>
                            <?php $this->print_theme_freetext_option( 'panel_width' ); ?>
                        </td>
                    </tr>
                    <tr>
                        <td class='mega-name'>
                            <?php _e("Panel Border", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("Set the border to display on the Mega Panel.", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'>
                            <label>
                                <span class='mega-short-desc'><?php _e("Color", "megamenu"); ?></span>
                                <?php $this->print_theme_color_option( 'panel_border_color' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Top", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'panel_border_top' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Right", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'panel_border_right' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Bottom", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'panel_border_bottom' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Left", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'panel_border_left' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td class='mega-name'>
                            <?php _e("Panel Padding", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("Set the padding for the whole panel. Set these values 0px if you wish your panel content to go edge-to-edge.", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'>
                            <label>
                                <span class='mega-short-desc'><?php _e("Top", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'panel_padding_top' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Right", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'panel_padding_right' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Bottom", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'panel_padding_bottom' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Left", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'panel_padding_left' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td class='mega-name'>
                            <?php _e("Rounded Corners", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("Set rounded corners for the panel.", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'>
                            <label>
                                <span class='mega-short-desc'><?php _e("Top Left", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'panel_border_radius_top_left' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Top Right", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'panel_border_radius_top_right' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Bottom Right", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'panel_border_radius_bottom_right' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Bottom Left", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'panel_border_radius_bottom_left' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td class='mega-name'>
                            <?php _e("Widget Padding", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("Set the padding for each widget in the panel. Use this to define the spacing between each widget in the panel.", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'>
                            <label>
                                <span class='mega-short-desc'><?php _e("Top", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'panel_widget_padding_top' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Right", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'panel_widget_padding_right' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Bottom", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'panel_widget_padding_bottom' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Left", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'panel_widget_padding_left' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td class='mega-name'>
                            <?php _e("Heading Font", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("Set the font to use for Widget Headers. This setting is also used for second level menu items when they're displayed in a Mega Menu.", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'>
                            <?php $this->print_theme_color_option( 'panel_header_color' ); ?>
                            <?php $this->print_theme_freetext_option( 'panel_header_font_size' ); ?>
                            <?php $this->print_theme_font_option( 'panel_header_font' ); ?>
                            <?php $this->print_theme_weight_option( 'panel_header_font_weight' ); ?>
                        </td>
                    </tr>
                    <tr>
                        <td class='mega-name'>
                            <?php _e("Heading Text Transform", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("Set the text transform style for the Widget Headers and second level menu items.", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'>
                            <?php $this->print_theme_transform_option( 'panel_header_text_transform' ); ?>
                        </td>
                    </tr>
                    <tr>
                        <td class='mega-name'>
                            <?php _e("Heading Padding", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("Set the padding for the headings. Use this to set the gap between the widget heading and the widget content.", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'>
                            <label>
                                <span class='mega-short-desc'><?php _e("Top", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'panel_header_padding_top' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Right", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'panel_header_padding_right' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Bottom", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'panel_header_padding_bottom' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Left", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'panel_header_padding_left' ); ?>
                            </label>
                        </td>
                    </tr>
                </table>


                <h4><?php _e("Flyout Menus", "megamenu"); ?></h4>

                <table>
                    <tr>
                        <td class='mega-name'>
                            <?php _e("Item Background", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("Set the background color for a flyout menu item.", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'>
                            <label>
                                <span class='mega-short-desc'><?php _e("From", "megamenu"); ?></span>
                                <?php $this->print_theme_color_option( 'flyout_background_from' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("To", "megamenu"); ?></span>
                                <?php $this->print_theme_color_option( 'flyout_background_to' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td class='mega-name'>
                            <?php _e("Item Background (Hover)", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("Set the background color for a flyout menu item (on hover).", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'>
                            <label>
                                <span class='mega-short-desc'><?php _e("From", "megamenu"); ?></span>
                                <?php $this->print_theme_color_option( 'flyout_background_hover_from' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("To", "megamenu"); ?></span>
                                <?php $this->print_theme_color_option( 'flyout_background_hover_to' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td class='mega-name'>
                            <?php _e("Item Height", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("The height of each flyout menu item.", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'>
                            <?php $this->print_theme_freetext_option( 'flyout_link_height' ); ?>
                        </td>
                    </tr>
                    <tr>
                        <td class='mega-name'>
                            <?php _e("Item Padding", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("Set the padding for each flyout menu item.", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'>
                            <label>
                                <span class='mega-short-desc'><?php _e("Top", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'flyout_link_padding_top' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Right", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'flyout_link_padding_right' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Bottom", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'flyout_link_padding_bottom' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Left", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'flyout_link_padding_left' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td class='mega-name'>
                            <?php _e("Flyout Menu Width", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("The width of each flyout menu.", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'>
                            <?php $this->print_theme_freetext_option( 'flyout_width' ); ?>
                        </td>
                    </tr>
                    <tr>
                        <td class='mega-name'>
                            <?php _e("Flyout Menu Border", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("Set the border for the flyout menu.", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'>
                            <label>
                                <span class='mega-short-desc'><?php _e("Color", "megamenu"); ?></span>
                                <?php $this->print_theme_color_option( 'flyout_border_color' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Top", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'flyout_border_top' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Right", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'flyout_border_right' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Bottom", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'flyout_border_bottom' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Left", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'flyout_border_left' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td class='mega-name'>
                            <?php _e("Font Weight", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("Set the font weight for the flyout menu items.", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'>
                            <?php $this->print_theme_weight_option( 'flyout_link_weight' ); ?>
                        </td>
                    </tr>
                    <tr>
                        <td class='mega-name'>
                            <?php _e("Font Weight (Hover)", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("Set the font weight for the flyout menu items (on hover).", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'>
                            <?php $this->print_theme_weight_option( 'flyout_link_weight_hover' ); ?>
                        </td>
                    </tr>
                </table>

                <h4><?php _e("Custom Styling", "megamenu"); ?></h4>

                <table>
                    <tr>
                        <td class='mega-name'>
                            <?php _e("CSS Editor", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("Define any custom CSS you wish to add to menus using this theme. You can use standard CSS or SCSS.", "megamenu"); ?>
                            </div>
                            
                        </td>
                        <td class='mega-value'>
                            <?php $this->print_theme_textarea_option( 'custom_css' ); ?>
                            <p><b><?php _e("Custom Styling Tips", "megamenu"); ?></b></p>
                            <ul class='custom_styling_tips'>
                                <li><code>#{$wrap}</code> <?php _e("converts to the ID selector of the menu wrapper, e.g. div#mega-menu-wrap-primary-14", "megamenu"); ?></li>
                                <li><code>#{$menu}</code> <?php _e("converts to the ID selector of the menu, e.g. ul#mega-menu-primary-1", "megamenu"); ?></li>
                                <li><?php _e("Use @import rules to import CSS from other plugins or your theme directory, e.g:"); ?>
                                <br /><br /><code>#{$wrap} #{$menu} {<br />&nbsp;&nbsp;&nbsp;&nbsp;@import "shortcodes-ultimate/assets/css/box-shortcodes.css";<br />}</code></li>
                            </ul>
                        </td>
                    </tr>

                </table>

                <?php

                submit_button();

                ?>

                <?php if ( $this->string_contains( $this->id, array("custom") ) ) : ?>

                    <a class='delete confirm' href='<?php echo wp_nonce_url(admin_url("admin-post.php?action=megamenu_delete_theme&theme_id={$this->id}"), 'megamenu_delete_theme') ?>'><?php _e("Delete Theme", "megamenu"); ?></a>

                <?php else : ?>

                    <a class='revert confirm' href='<?php echo wp_nonce_url(admin_url("admin-post.php?action=megamenu_revert_theme&theme_id={$this->id}"), 'megamenu_revert_theme') ?>'><?php _e("Revert Changes", "megamenu"); ?></a>

                <?php endif; ?>

                <a class='duplicate' href='<?php echo wp_nonce_url(admin_url("admin-post.php?action=megamenu_duplicate_theme&theme_id={$this->id}"), 'megamenu_duplicate_theme') ?>'><?php _e("Duplicate Theme", "megamenu"); ?></a>

                </form>

            </div>

        <?php

    }

    /**
     * Print an arrow dropdown selection box
     *
     * @since 1.0
     * @param string $key
     * @param string $value
     */
    public function print_theme_arrow_option( $key ) {

        $value = $this->active_theme[$key];
        
        $arrow_icons = $this->arrow_icons(); 

        ?>
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
            <span class="selected_icon <?php echo $arrow_icons[$value] ?>"></span>


        <?php
    }

    /**
     * Print a colorpicker
     *
     * @since 1.0
     * @param string $key
     * @param string $value
     */
    public function print_theme_color_option( $key ) {

        $value = $this->active_theme[$key];

        if ( $value == 'transparent' ) {
            $value = 'rgba(0,0,0,0)';
        }

        if ( $value == 'rgba(0,0,0,0)' ) {
            $value_text = 'transparent';
        } else {
            $value_text = $value;
        }

        echo "<div class='mm-picker-container'>";
        echo "    <input type='text' class='mm_colorpicker' name='settings[$key]' value='{$value}' />";
        echo "    <div class='chosen-color'>{$value_text}</div>";
        echo "</div>";

    }


    /**
     * Print a font weight selector
     *
     * @since 1.0
     * @param string $key
     * @param string $value
     */
    public function print_theme_weight_option( $key ) {

        $value = $this->active_theme[$key];

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
    public function print_theme_transform_option( $key ) {

        $value = $this->active_theme[$key];

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
    public function print_theme_textarea_option( $key ) {

        $value = $this->active_theme[$key];

        echo "<textarea id='codemirror' name='settings[$key]'>" . stripslashes( $value ) . "</textarea>";

    }


    /**
     * Print a font selector
     *
     * @since 1.0
     * @param string $key
     * @param string $value
     */
    public function print_theme_font_option( $key ) {

        $value = $this->active_theme[$key];

        echo "<select name='settings[$key]'>";

        echo "<option value='inherit'>" . __("Theme Default", "megamenu") . "</option>";

        foreach ( $this->fonts() as $font ) {
            $parts = explode(",", $font);
            $font_name = trim($parts[0]);
            echo "<option value=\"{$font}\" " . selected( $font, $value ) . ">{$font_name}</option>";
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
    public function print_theme_freetext_option( $key ) {

        $value = $this->active_theme[$key];

        echo "<input class='mega-setting-{$key}' type='text' name='settings[$key]' value='{$value}' />";

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

        if( 'appearance_page_megamenu_settings' != $hook )
            return;

        wp_enqueue_style( 'spectrum', MEGAMENU_BASE_URL . 'js/spectrum/spectrum.css', false, MEGAMENU_VERSION );
        wp_enqueue_style( 'mega-menu-settings', MEGAMENU_BASE_URL . 'css/admin-settings.css', false, MEGAMENU_VERSION );
        wp_enqueue_style( 'codemirror', MEGAMENU_BASE_URL . 'js/codemirror/codemirror.css', false, MEGAMENU_VERSION );

        wp_enqueue_script( 'spectrum', MEGAMENU_BASE_URL . 'js/spectrum/spectrum.js', array( 'jquery' ), MEGAMENU_VERSION );
        wp_enqueue_script( 'codemirror', MEGAMENU_BASE_URL . 'js/codemirror/codemirror.js', array(), MEGAMENU_VERSION );
        wp_enqueue_script( 'mega-menu-theme-editor', MEGAMENU_BASE_URL . 'js/settings.js', array('jquery', 'spectrum', 'codemirror'), MEGAMENU_VERSION );

        wp_localize_script( 'mega-menu-theme-editor', 'megamenu_settings',
            array(
                'confirm' => __("Are you sure?", "megamenu")
            )
        );
    }

}

endif;