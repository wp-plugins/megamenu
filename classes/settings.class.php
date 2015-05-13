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
        add_action( 'admin_post_megamenu_regenerate_css', array( $this, 'tools_regenerate_css') );
        add_action( 'admin_post_megamenu_delete_data', array( $this, 'delete_data') );

        add_action( 'megamenu_page_theme_editor', array( $this, 'theme_editor_page'));
        add_action( 'megamenu_page_tools', array( $this, 'tools_page'));
        add_action( 'megamenu_page_general_settings', array( $this, 'general_settings_page'));

        add_action( 'admin_menu', array( $this, 'megamenu_themes_page') );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_theme_editor_scripts' ) );

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

        $submitted_settings = $_POST['settings'];

        if ( isset( $submitted_settings['checkboxes'] ) ) {

            foreach ( $submitted_settings['checkboxes'] as $checkbox ) {

                if ( isset( $submitted_settings[ $checkbox ] ) ) {

                    $submitted_settings[ $checkbox ] = 'on';

                } else {

                    $submitted_settings[ $checkbox ] = 'off';

                }

            }

            unset( $submitted_settings['checkboxes'] );

        }

        $saved_themes[ $theme ] = array_map( 'esc_attr', $submitted_settings );

        update_site_option( "megamenu_themes", $saved_themes );

        do_action("megamenu_after_theme_save");

        wp_redirect( admin_url( "themes.php?page=megamenu_settings&tab=theme_editor&theme={$theme}&saved=true" ) );

    }


    /**
     * Clear the CSS cache.
     *
     * @since 1.5
     */
    public function tools_regenerate_css() {

        check_admin_referer( 'megamenu_regenerate_css' );

        do_action( 'megamenu_generate_css' );

        wp_redirect( admin_url( 'themes.php?page=megamenu_settings&tab=tools&regenerate_css=true' ) );

    }


    /**
     * Deletes all Max Mega Menu data from the database
     *
     * @since 1.5
     */
    public function delete_data() {

        check_admin_referer( 'megamenu_delete_data' );

        // delete menu settings
        delete_option("megamenu_settings");

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
        delete_transient( "megamenu_css" );

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

        $submitted_settings = apply_filters( "megamenu_submitted_settings", $_POST['settings'] );

        $existing_settings = get_option( 'megamenu_settings' );

        $new_settings = array_merge( (array)$existing_settings, $submitted_settings );

        $tab = isset( $_POST['tab'] ) ? $_POST['tab'] : 'general_settings';
        
        update_option( 'megamenu_settings', $new_settings );

        do_action("megamenu_after_save_general_settings");

        wp_redirect( admin_url( "themes.php?page=megamenu_settings&tab={$tab}&saved=true" ) );

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
        $settings = get_option( "megamenu_settings" );

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
     * Content for 'Settings' tab
     *
     * @since 1.4
     */
    public function general_settings_page( $saved_settings ) {

        $css = isset( $saved_settings['css'] ) ? $saved_settings['css'] : 'ajax';
        $mobile_second_click = isset( $saved_settings['mobile_second_click'] ) ? $saved_settings['mobile_second_click'] : 'close';

        ?>

        <div class='menu_settings'>

            <form action="<?php echo admin_url('admin-post.php'); ?>" method="post">
                <input type="hidden" name="action" value="megamenu_save_settings" />
                <?php wp_nonce_field( 'megamenu_save_settings' ); ?>
                
                <h4 class='first'><?php _e("General Settings", "megamenu"); ?></h4>

                <p><?php _e("These settings define the overall behaviour of Max Mega Menu.", "megamenu"); ?> <?php _e("Menu specific settings (e.g, click or hover event, menu theme, transition effect) can be found under", "megamenu"); ?> <a href='<?php echo admin_url( "nav-menus.php"); ?>'><?php _e("Appearance > Menus", "megamenu"); ?></a>.</p>

                <table>
                    <tr>
                        <td class='mega-name'>
                            <?php _e("CSS Output", "megamenu"); ?>
                            <div class='mega-description'>
                            </div>
                        </td>
                        <td class='mega-value'>
                            <select name='settings[css]' id='mega_css'>
                                <option value='ajax' <?php echo selected( $css == 'ajax'); ?>><?php _e("Enqueue dynamically via admin-ajax.php", "megamenu"); ?></option>
                                <option value='fs' <?php echo selected( $css == 'fs'); ?>><?php _e("Save to filesystem", "megamenu"); ?></option>
                                <option value='head' <?php echo selected( $css == 'head'); ?>><?php _e("Output in &lt;head&gt;", "megamenu"); ?></option>
                                <option value='disabled' <?php echo selected( $css == 'disabled'); ?>><?php _e("Don't output CSS", "megamenu"); ?></option>
                            <select>
                            <div class='mega-description'>
                                <div class='ajax' style='display: <?php echo $css == 'ajax' ? 'block' : 'none' ?>'><?php _e("Default. CSS will be enqueued dynamically through admin-ajax.php and loaded from the cache.", "megamenu"); ?></div>
                                <div class='fs' style='display: <?php echo $css == 'fs' ? 'block' : 'none' ?>'><?php _e("CSS will be saved to wp-content/uploads/maxmegamenu/style.css and enqueued from there.", "megamenu"); ?></div>
                                <div class='head' style='display: <?php echo $css == 'head' ? 'block' : 'none' ?>'><?php _e("CSS will be loaded from the cache in a &lt;style&gt; tag in the &lt;head&gt; of the page.", "megamenu"); ?></div>
                                <div class='disabled' style='display: <?php echo $css == 'disabled' ? 'block' : 'none' ?>'><?php _e("CSS will not be output, you must enqueue the CSS for the menu manually.", "megamenu"); ?></div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class='mega-name'>
                            <?php _e("Click Event Behaviour", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("Define what should happen when the event is set to 'click'. This also applies to mobiles.", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'>
                            <select name='settings[second_click]'>
                                <option value='close' <?php echo selected( $mobile_second_click == 'close'); ?>><?php _e("First click will open a sub menu, second click will close the sub menu.", "megamenu"); ?></option>
                                <option value='go' <?php echo selected( $mobile_second_click == 'go'); ?>><?php _e("First click will open a sub menu, second click will follow the link.", "megamenu"); ?></option>
                            <select>
                            <div class='mega-description'>
                            </div>
                        </td>
                    </tr>
                </table>

                <?php do_action( "megamenu_general_settings", $saved_settings ); ?>

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
    public function tools_page( $saved_settings ) {

        ?>

        <div class='menu_settings'>

            <form action="<?php echo admin_url('admin-post.php'); ?>" method="post">
                <?php wp_nonce_field( 'megamenu_regenerate_css' ); ?>
                <input type="hidden" name="action" value="megamenu_regenerate_css" />

                <h4 class='first'><?php _e("Cache", "megamenu"); ?></h4>
                <p><?php _e("Max Mega Menu automatically generates the CSS required to display your menu each time you make a change to a menu or a menu theme. The generated CSS is then cached for performance.", "megamenu"); ?></p>
                <p><?php _e("Use this tool to manually regenerate the menu CSS and update the cache.", "megamenu"); ?></p>

                <input type='submit' class='button button-primary' value='<?php _e("Regenerate CSS", "megamenu"); ?>' />
            </form>

            <form action="<?php echo admin_url('admin-post.php'); ?>" method="post">
                <?php wp_nonce_field( 'megamenu_delete_data' ); ?>
                <input type="hidden" name="action" value="megamenu_delete_data" />

                <h4><?php _e("Plugin Data", "megamenu"); ?></h4>
                <p><?php _e("Delete all saved Max Mega Menu plugin data from the database. Use with caution!", "megamenu"); ?></p>

                <input type='submit' class='button button-primary confirm' value='<?php _e("Delete Data", "megamenu"); ?>' />
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

        $tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'general_settings';

        $header_links = apply_filters( "megamenu_header_links", array(
            'homepage' => array(
                'url' => 'https://maxmegamenu.com/',
                'target' => '_mmmpro',
                'text' => __("Homepage", "megamenu"),
                'class' => ''
            ),
            'documentation' => array(
                'url' => 'https://maxmegamenu.com/documentation/getting-started/installation/',
                'text' => __("Documentation", "megamenu"),
                'target' => '_mmmpro',
                'class' => ''
            )
        ) );

        if ( ! is_plugin_active('megamenu-pro/megamenu-pro.php') ) {
            $header_links['pro'] = array(
                'url' => 'https://maxmegamenu.com/upgrade/?utm_source=free&amp;utm_medium=link&amp;utm_campaign=pro',
                'target' => '_mmmpro',
                'text' => __("Upgrade to Pro - $19", "megamenu"),
                'class' => 'mega-highlight'
            );
        }

        $versions = apply_filters( "megamenu_versions", array(
            'core' => array(
                'version' => MEGAMENU_VERSION,
                'text' => __("Core version", "megamenu")
            ),
            'pro' => array(
                'version' => "<a href='https://maxmegamenu.com/upgrade/?utm_source=free&amp;utm_medium=link&amp;utm_campaign=pro' target='_mmmpro'>not installed</a>",
                'text' => __("Pro extension", "megamenu")
            )
        ) );

        ?>

        <div class='megamenu_outer_wrap'>
            <div class='megamenu_header_top'>
                <ul>
                    <?php
                        foreach ( $header_links as $id => $data ) {
                            echo "<li class='{$data['class']}'><a href='{$data['url']}' target='{$data['target']}'>{$data['text']}";
                            echo "</a>";
                            echo "</li>";
                        }
                    ?>
                </ul>
            </div>
            <div class='megamenu_header'>
                <div class='megamenu_header_left'>
                    <h2><?php _e("Max Mega Menu", "megamenu"); ?></h2>
                    <div class='version'>
                        <?php

                            $total = count( $versions );
                            $count = 0;
                            $separator = ' - ';

                            foreach ( $versions as $id => $data ) {
                                echo $data['text'] . ": <b>" . $data['version'] . "</b>";

                                $count = $count + 1;

                                if ( $total > 0 && $count != $total ) {
                                    echo $separator;
                                }
                            }
                        ?>
                    </div>
                </div>
            </div>
            <div class='megamenu_wrap'>
                <div class='megamenu_right'>
                    <?php $this->print_messages(); ?>

                    <?php 

                        $saved_settings = get_option("megamenu_settings");

                        if ( has_action( "megamenu_page_{$tab}" ) ) {
                            do_action( "megamenu_page_{$tab}", $saved_settings ); 
                        }

                    ?>
                </div>
            </div>

            <div class='megamenu_left'>
                <ul>
                    <?php 

                        $tabs = apply_filters("megamenu_menu_tabs", array(
                            'general_settings' => __("General Settings", "megamenu"),
                            'tools' => __("Tools", "megamenu"),
                            'theme_editor' => __("Theme Editor", "megamenu")
                        ));

                        foreach ( $tabs as $key => $title ) {
                            $class = $tab == $key ? 'active' : '';

                            $url = esc_url( add_query_arg(
                                array(
                                    'page'=>'megamenu_settings',
                                    'tab' => $key
                                ),
                                admin_url("themes.php")
                            ) );

                            echo "<li><a class='{$class}' href='{$url}'>{$title}</a></li>";
                        }

                    ?>
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

        $menu_id = 0;

        $menus = get_registered_nav_menus();

        if ( count( $menus ) ) {

            $locations = get_nav_menu_locations();

            foreach ($menus as $location => $description ) {
    
                if ( isset( $locations[ $location ] ) ) {

                    $menu_id = $locations[ $location ];
                    continue;

                }
    
            }

        }

        $test = $style_manager->generate_css_for_location( 'test', $this->active_theme, $menu_id );

        if ( is_wp_error( $test ) ) {
            echo "<p class='fail'>" . $test->get_error_message() . "</p>";
        }

        if ( isset( $_GET['deleted'] ) && $_GET['deleted'] == 'false' ) {
            echo "<p class='fail'>" . __("Failed to delete theme. The theme is in use by a menu.", "megamenu") . "</p>";
        }

        if ( isset( $_GET['regenerate_css'] ) && $_GET['regenerate_css'] == 'true' ) {
            echo "<p class='success'>" . __("CSS cache cleared and CSS regenerated", "megamenu") . "</p>";
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

        do_action("megamenu_print_messages");

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
    public function theme_editor_page( $saved_settings ) {
        
        $this->init();

        $create_url = esc_url( add_query_arg(
            array(
                'action'=>'megamenu_add_theme'
            ),
            wp_nonce_url( admin_url("admin-post.php"), 'megamenu_create_theme' )
        ) );

        $duplicate_url = esc_url( add_query_arg(
            array(
                'action'=>'megamenu_duplicate_theme',
                'theme_id' => $this->id
            ),
            wp_nonce_url( admin_url("admin-post.php"), 'megamenu_duplicate_theme' )
        ) );

        $delete_url = esc_url( add_query_arg(
            array(
                'action'=>'megamenu_delete_theme',
                'theme_id' => $this->id
            ),
            wp_nonce_url( admin_url("admin-post.php"), 'megamenu_delete_theme' )
        ) );

        $revert_url = esc_url( add_query_arg(
            array(
                'action'=>'megamenu_revert_theme',
                'theme_id' => $this->id
            ),
            wp_nonce_url( admin_url("admin-post.php"), 'megamenu_revert_theme' )
        ) );

        ?>

        <div class='menu_settings'>

            <div class='theme_selector'>
                <?php _e("Select theme to edit", "megamenu"); ?> <?php echo $this->theme_selector(); ?> <?php _e("or", "megamenu"); ?>
                <a href='<?php echo $create_url ?>'><?php _e("create a new theme", "megamenu"); ?></a> <?php _e("or", "megamenu"); ?>
                <a href='<?php echo $duplicate_url ?>'><?php _e("duplicate this theme", "megamenu"); ?></a>
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
                            <?php _e("Arrow", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("Select the arrow styles.", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'>
                            <label>
                                <span class='mega-short-desc'><?php _e("Up", "megamenu"); ?></span>
                                <?php $this->print_theme_arrow_option( 'arrow_up' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Down", "megamenu"); ?></span>
                                <?php $this->print_theme_arrow_option( 'arrow_down' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Left", "megamenu"); ?></span>
                                <?php $this->print_theme_arrow_option( 'arrow_left' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Right", "megamenu"); ?></span>
                                <?php $this->print_theme_arrow_option( 'arrow_right' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td class='mega-name'>
                            <?php _e("Responsive Breakpoint", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("Set the width at which the menu turns into a mobile menu. Set to 0 to disable responsive menu.", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'><?php $this->print_theme_freetext_option( 'responsive_breakpoint' ); ?></td>
                    </tr>
                    <tr>
                        <td class='mega-name'>
                            <?php _e("Responsive Menu Text", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("Text to display next to the mobile toggle icon.", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'><?php $this->print_theme_freetext_option( 'responsive_text' ); ?></td>
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
                    <tr>
                        <td class='mega-name'>
                            <?php _e("Shadow", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("Apply a shadow to mega and flyout menus.", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'>
                            <label>
                                <span class='mega-short-desc'><?php _e("Enabled", "megamenu"); ?></span>
                                <?php $this->print_theme_checkbox_option( 'shadow' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Horizonal", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'shadow_horizontal' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Vertical", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'shadow_vertical' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Blur", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'shadow_blur' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Spread", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'shadow_spread' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Color", "megamenu"); ?></span>
                                <?php $this->print_theme_color_option( 'shadow_color' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td class='mega-name'>
                            <?php _e("Hover Transitions", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("Apply hover transitions to menu items. Note: Transitions will not apply to gradient backgrounds.", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'>
                            <label>
                                <span class='mega-short-desc'><?php _e("Enabled", "megamenu"); ?></span>
                                <?php $this->print_theme_checkbox_option( 'transitions' ); ?>
                            </label>
                        </td>
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
                            <?php _e("Menu Border Radius", "megamenu"); ?>
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
                    <tr>
                        <td class='mega-name'>
                            <?php _e("Menu Items Align", "megamenu"); ?>
                            <div class='mega-description'> 
                                <?php _e("Align <i>all</i> menu items to the left (default), centrally or to the right.", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'>
                            <?php $this->print_theme_align_option( 'menu_item_align' ); ?>
                            <div class='mega-info'><?php _e("This option will apply to all menu items. To align an individual menu item to the right, edit the menu item itself and set 'Menu Item Align' to 'Right'.", "megamenu"); ?>
                        </td>
                    </tr>
                </table>

                <h4><?php _e("Top Level Menu Items", "megamenu"); ?></h4>

                <table>
                    <tr>
                        <td class='mega-name'>
                            <?php _e("Menu Item Background", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("The background color for each top level menu item. Tip: Set these values to transparent if you've already set a background color on the menu bar.", "megamenu"); ?>
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
                            <label>
                                <span class='mega-short-desc'><?php _e("Color", "megamenu"); ?></span>
                                <?php $this->print_theme_color_option( 'menu_item_link_color' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Size", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'menu_item_link_font_size' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Family", "megamenu"); ?></span>
                                <?php $this->print_theme_font_option( 'menu_item_link_font' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Transform", "megamenu"); ?></span>
                                <?php $this->print_theme_transform_option( 'menu_item_link_text_transform' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Weight", "megamenu"); ?></span>
                                <?php $this->print_theme_weight_option( 'menu_item_link_weight' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Decoration", "megamenu"); ?></span>
                                <?php $this->print_theme_text_decoration_option( 'menu_item_link_text_decoration' ); ?>
                            </label>
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
                            <label>
                                <span class='mega-short-desc'><?php _e("Color", "megamenu"); ?></span>
                                <?php $this->print_theme_color_option( 'menu_item_link_color_hover' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Weight", "megamenu"); ?></span>
                                <?php $this->print_theme_weight_option( 'menu_item_link_weight_hover' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Decoration", "megamenu"); ?></span>
                                <?php $this->print_theme_text_decoration_option( 'menu_item_link_text_decoration_hover' ); ?>
                            </label>
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
                            <?php _e("Menu Item Border", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("Set the border to display on each top level menu item.", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'>
                            <label>
                                <span class='mega-short-desc'><?php _e("Color", "megamenu"); ?></span>
                                <?php $this->print_theme_color_option( 'menu_item_border_color' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Top", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'menu_item_border_top' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Right", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'menu_item_border_right' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Bottom", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'menu_item_border_bottom' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Left", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'menu_item_border_left' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td class='mega-name'>
                            <?php _e("Menu Item Border (Hover)", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("Set the hover border color.", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'>
                            <label>
                                <span class='mega-short-desc'><?php _e("Color", "megamenu"); ?></span>
                                <?php $this->print_theme_color_option( 'menu_item_border_color_hover' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td class='mega-name'>
                            <?php _e("Menu Item Border Radius", "megamenu"); ?>
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
                    <tr>
                        <td class='mega-name'>
                            <?php _e("Menu Item Divider", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("Show a small divider bar between each menu item.", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'>
                            <label>
                                <span class='mega-short-desc'><?php _e("Enabled", "megamenu"); ?></span>
                                <?php $this->print_theme_checkbox_option( 'menu_item_divider' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Color", "megamenu"); ?></span>
                                <?php $this->print_theme_color_option( 'menu_item_divider_color' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Glow Opacity", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'menu_item_divider_glow_opacity' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td class='mega-name'>
                            <?php _e("Highlight Current Item", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("Apply the 'hover' styling to current menu items.", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'><?php $this->print_theme_checkbox_option( 'menu_item_highlight_current' ); ?></td>
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
                                <?php _e("Mega Panel width", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'>
                            <?php $this->print_theme_freetext_option( 'panel_width' ); ?>
                            <div class='mega-info'><?php _e("A 100% wide panel will only ever be as wide as the menu itself. For a fixed panel width set this to a pixel value.", "megamenu"); ?></div>
                            <div class='mega-info'><?php _e("Advanced: Enter a jQuery selector to synchronize the width and position of the sub menu with existing page element (e.g. body, #container, .page).", "megamenu"); ?></div>
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
                            <?php _e("Panel Border Radius", "megamenu"); ?>
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
                            <?php _e("Widget Heading Font", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("Set the font to use Widget headers in the mega menu. Tip: set this to the same style as the Second Level Menu Item Header font to keep your styling consistent.", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'>
                            <label>
                                <span class='mega-short-desc'><?php _e("Color", "megamenu"); ?></span>
                                <?php $this->print_theme_color_option( 'panel_header_color' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Size", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'panel_header_font_size' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Family", "megamenu"); ?></span>
                                <?php $this->print_theme_font_option( 'panel_header_font' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Weight", "megamenu"); ?></span>
                                <?php $this->print_theme_weight_option( 'panel_header_font_weight' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Transform", "megamenu"); ?></span>
                                <?php $this->print_theme_transform_option( 'panel_header_text_transform' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Decoration", "megamenu"); ?></span>
                                <?php $this->print_theme_text_decoration_option( 'panel_header_text_decoration' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td class='mega-name'>
                            <?php _e("Widget Content Font", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("Set the font to use for panel contents.", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'>
                            <label>
                                <span class='mega-short-desc'><?php _e("Color", "megamenu"); ?></span>
                                <?php $this->print_theme_color_option( 'panel_font_color' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Size", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'panel_font_size' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Family", "megamenu"); ?></span>
                                <?php $this->print_theme_font_option( 'panel_font_family' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td class='mega-name'>
                            <?php _e("Widget Heading Padding", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("Set the padding for the widget headings.", "megamenu"); ?>
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
                    <tr>
                        <td class='mega-name'>
                            <?php _e("Widget Heading Margin", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("Set the margin for the widget headings.", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'>
                            <label>
                                <span class='mega-short-desc'><?php _e("Top", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'panel_header_margin_top' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Right", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'panel_header_margin_right' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Bottom", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'panel_header_margin_bottom' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Left", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'panel_header_margin_left' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td class='mega-name'>
                            <?php _e("Border", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("Set the border for the widget headings.", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'>
                            <label>
                                <span class='mega-short-desc'><?php _e("Color", "megamenu"); ?></span>
                                <?php $this->print_theme_color_option( 'panel_header_border_color' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Top", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'panel_header_border_top' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Right", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'panel_header_border_right' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Bottom", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'panel_header_border_bottom' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Left", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'panel_header_border_left' ); ?>
                            </label>
                        </td>
                    </tr>
                </table>

                <h5><?php _e("Second Level Menu Items", "megamenu"); ?></h5>

                <table>

                    <tr>
                        <td class='mega-name'>
                            <?php _e("Font", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("Set the font for second level menu items when they're displayed in a Mega Menu.", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'>
                            <label>
                                <span class='mega-short-desc'><?php _e("Color", "megamenu"); ?></span>
                                <?php $this->print_theme_color_option( 'panel_second_level_font_color' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Size", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'panel_second_level_font_size' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Family", "megamenu"); ?></span>
                                <?php $this->print_theme_font_option( 'panel_second_level_font' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Weight", "megamenu"); ?></span>
                                <?php $this->print_theme_weight_option( 'panel_second_level_font_weight' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Transform", "megamenu"); ?></span>
                                <?php $this->print_theme_transform_option( 'panel_second_level_text_transform' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Decoration", "megamenu"); ?></span>
                                <?php $this->print_theme_text_decoration_option( 'panel_second_level_text_decoration' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td class='mega-name'>
                            <?php _e("Font (Hover)", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("Set the font style on hover.", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'>
                            <label>
                                <span class='mega-short-desc'><?php _e("Color", "megamenu"); ?></span>
                                <?php $this->print_theme_color_option( 'panel_second_level_font_color_hover' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Weight", "megamenu"); ?></span>
                                <?php $this->print_theme_weight_option( 'panel_second_level_font_weight_hover' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Decoration", "megamenu"); ?></span>
                                <?php $this->print_theme_text_decoration_option( 'panel_second_level_text_decoration_hover' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td class='mega-name'>
                            <?php _e("Background (Hover)", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("Set the background hover color for second level menu items.", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'>
                            <label>
                                <span class='mega-short-desc'><?php _e("From", "megamenu"); ?></span>
                                <?php $this->print_theme_color_option( 'panel_second_level_background_hover_from' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("To", "megamenu"); ?></span>
                                <?php $this->print_theme_color_option( 'panel_second_level_background_hover_to' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td class='mega-name'>
                            <?php _e("Padding", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("Set the padding for the second level menu items.", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'>
                            <label>
                                <span class='mega-short-desc'><?php _e("Top", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'panel_second_level_padding_top' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Right", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'panel_second_level_padding_right' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Bottom", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'panel_second_level_padding_bottom' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Left", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'panel_second_level_padding_left' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td class='mega-name'>
                            <?php _e("Margin", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("Set the margin for the second level menu items.", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'>
                            <label>
                                <span class='mega-short-desc'><?php _e("Top", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'panel_second_level_margin_top' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Right", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'panel_second_level_margin_right' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Bottom", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'panel_second_level_margin_bottom' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Left", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'panel_second_level_margin_left' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td class='mega-name'>
                            <?php _e("Border", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("Set the border for the second level menu items.", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'>
                            <label>
                                <span class='mega-short-desc'><?php _e("Color", "megamenu"); ?></span>
                                <?php $this->print_theme_color_option( 'panel_second_level_border_color' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Top", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'panel_second_level_border_top' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Right", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'panel_second_level_border_right' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Bottom", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'panel_second_level_border_bottom' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Left", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'panel_second_level_border_left' ); ?>
                            </label>
                        </td>
                    </tr>
                </table>

                <h5><?php _e("Third Level Menu Items", "megamenu"); ?></h5>

                <table>

                    <tr>
                        <td class='mega-name'>
                            <?php _e("Font", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("Set the font for third level menu items when they're displayed in a Mega Menu.", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'>
                            <label>
                                <span class='mega-short-desc'><?php _e("Color", "megamenu"); ?></span>
                                <?php $this->print_theme_color_option( 'panel_third_level_font_color' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Size", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'panel_third_level_font_size' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Family", "megamenu"); ?></span>
                                <?php $this->print_theme_font_option( 'panel_third_level_font' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Weight", "megamenu"); ?></span>
                                <?php $this->print_theme_weight_option( 'panel_third_level_font_weight' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Transform", "megamenu"); ?></span>
                                <?php $this->print_theme_transform_option( 'panel_third_level_text_transform' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Decoration", "megamenu"); ?></span>
                                <?php $this->print_theme_text_decoration_option( 'panel_third_level_text_decoration' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td class='mega-name'>
                            <?php _e("Font (Hover)", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("Set the font style on hover.", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'>
                            <label>
                                <span class='mega-short-desc'><?php _e("Color", "megamenu"); ?></span>
                                <?php $this->print_theme_color_option( 'panel_third_level_font_color_hover' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Weight", "megamenu"); ?></span>
                                <?php $this->print_theme_weight_option( 'panel_third_level_font_weight_hover' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Decoration", "megamenu"); ?></span>
                                <?php $this->print_theme_text_decoration_option( 'panel_third_level_text_decoration_hover' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td class='mega-name'>
                            <?php _e("Background (Hover)", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("Set the background hover color for third level menu items.", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'>
                            <label>
                                <span class='mega-short-desc'><?php _e("From", "megamenu"); ?></span>
                                <?php $this->print_theme_color_option( 'panel_third_level_background_hover_from' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("To", "megamenu"); ?></span>
                                <?php $this->print_theme_color_option( 'panel_third_level_background_hover_to' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td class='mega-name'>
                            <?php _e("Padding", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("Set the padding for the third level menu items.", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'>
                            <label>
                                <span class='mega-short-desc'><?php _e("Top", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'panel_third_level_padding_top' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Right", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'panel_third_level_padding_right' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Bottom", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'panel_third_level_padding_bottom' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Left", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'panel_third_level_padding_left' ); ?>
                            </label>
                        </td>
                    </tr>
                </table>

                <h4><?php _e("Flyout Menus", "megamenu"); ?></h4>

                <table>
                    <tr>
                        <td class='mega-name'>
                            <?php _e("Menu Background", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("Set the background color for the flyout menu.", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'>
                            <label>
                                <span class='mega-short-desc'><?php _e("From", "megamenu"); ?></span>
                                <?php $this->print_theme_color_option( 'flyout_menu_background_from' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("To", "megamenu"); ?></span>
                                <?php $this->print_theme_color_option( 'flyout_menu_background_to' ); ?>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <td class='mega-name'>
                            <?php _e("Menu Width", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("The width of each flyout menu. This must be a fixed pixel value.", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'>
                            <?php $this->print_theme_freetext_option( 'flyout_width' ); ?>
                            <div class='mega-info'><?php _e("Set this value to the width of your longest menu item title to stop menu items wrapping onto 2 lines.", "megamenu"); ?></div>
                        </td>
                    </tr>
                   <tr>
                        <td class='mega-name'>
                            <?php _e("Menu Padding", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("Set the padding for the whole flyout menu.", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'>
                            <label>
                                <span class='mega-short-desc'><?php _e("Top", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'flyout_padding_top' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Right", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'flyout_padding_right' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Bottom", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'flyout_padding_bottom' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Left", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'flyout_padding_left' ); ?>
                            </label>
                            <div class='mega-info'><?php _e("Only suitable for single level flyout menus. If you're using multi level flyout menus set these values to 0px.", "megamenu"); ?></div>
                        </td>
                    </tr>
                    <tr>
                        <td class='mega-name'>
                            <?php _e("Menu Border", "megamenu"); ?>
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
                            <?php _e("Menu Border Radius", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("Set rounded corners for flyout menus. Rounded corners will be applied to all flyout menu levels.", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'>
                            <label>
                                <span class='mega-short-desc'><?php _e("Top Left", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'flyout_border_radius_top_left' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Top Right", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'flyout_border_radius_top_right' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Bottom Right", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'flyout_border_radius_bottom_right' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Bottom Left", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'flyout_border_radius_bottom_left' ); ?>
                            </label>
                        </td>
                    </tr>
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
                            <?php _e("Item Font", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("Set the font for the flyout menu items.", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'>
                            <label>
                                <span class='mega-short-desc'><?php _e("Color", "megamenu"); ?></span>
                                <?php $this->print_theme_color_option( 'flyout_link_color' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Size", "megamenu"); ?></span>
                                <?php $this->print_theme_freetext_option( 'flyout_link_size' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Family", "megamenu"); ?></span>
                                <?php $this->print_theme_font_option( 'flyout_link_family' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Transform", "megamenu"); ?></span>
                                <?php $this->print_theme_transform_option( 'flyout_link_text_transform' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Weight", "megamenu"); ?></span>
                                <?php $this->print_theme_weight_option( 'flyout_link_weight' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Decoration", "megamenu"); ?></span>
                                <?php $this->print_theme_text_decoration_option( 'flyout_link_text_decoration' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td class='mega-name'>
                            <?php _e("Item Font (Hover)", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("Set the font weight for the flyout menu items (on hover).", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'>
                            <label>
                                <span class='mega-short-desc'><?php _e("Color", "megamenu"); ?></span>
                                <?php $this->print_theme_color_option( 'flyout_link_color_hover' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Weight", "megamenu"); ?></span>
                                <?php $this->print_theme_weight_option( 'flyout_link_weight_hover' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Decoration", "megamenu"); ?></span>
                                <?php $this->print_theme_text_decoration_option( 'flyout_link_text_decoration_hover' ); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td class='mega-name'>
                            <?php _e("Item Divider", "megamenu"); ?>
                            <div class='mega-description'>
                                <?php _e("Show a line divider below each menu item.", "megamenu"); ?>
                            </div>
                        </td>
                        <td class='mega-value'>
                            <label>
                                <span class='mega-short-desc'><?php _e("Enabled", "megamenu"); ?></span>
                                <?php $this->print_theme_checkbox_option( 'flyout_menu_item_divider' ); ?>
                            </label>
                            <label>
                                <span class='mega-short-desc'><?php _e("Color", "megamenu"); ?></span>
                                <?php $this->print_theme_color_option( 'flyout_menu_item_divider_color' ); ?>
                            </label>
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
                                <li><code>#{$wrap}</code> <?php _e("converts to the ID selector of the menu wrapper, e.g. div#mega-menu-wrap-primary", "megamenu"); ?></li>
                                <li><code>#{$menu}</code> <?php _e("converts to the ID selector of the menu, e.g. ul#mega-menu-primary", "megamenu"); ?></li>
                                <li><?php _e("Use @import rules to import CSS from other plugins or your theme directory, e.g:"); ?>
                                <br /><br /><code>#{$wrap} #{$menu} {<br />&nbsp;&nbsp;&nbsp;&nbsp;@import "shortcodes-ultimate/assets/css/box-shortcodes.css";<br />}</code></li>
                            </ul>
                        </td>
                    </tr>
                </table>

                <div class='megamenu_submit'>
                    <div class='mega_left'>
                        <?php submit_button(); ?>
                    </div>
                    <div class='mega_right'>
                        <?php if ( $this->string_contains( $this->id, array("custom") ) ) : ?>
                            <a class='delete confirm' href='<?php echo $delete_url; ?>'><?php _e("Delete Theme", "megamenu"); ?></a>
                        <?php else : ?>
                            <a class='revert confirm' href='<?php echo $revert_url; ?>'><?php _e("Revert Theme", "megamenu"); ?></a>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

        <?php

    }


    /**
     * Print a select dropdown with left, center and right options
     *
     * @since 1.6.1
     * @param string $key
     * @param string $value
     */
    public function print_theme_align_option( $key ) {

        $value = $this->active_theme[$key];

        ?>

            <select name='settings[<?php echo $key ?>]'>
                <option value='left' <?php selected( $value, 'left' ); ?>><?php _e("Left", "megamenu") ?></option>
                <option value='center' <?php selected( $value, 'center' ); ?>><?php _e("Center", "megamenu") ?></option>
                <option value='right' <?php selected( $value, 'right' ); ?>><?php _e("Right", "megamenu") ?></option>
            </select>

        <?php
    }

    /**
     * Print a select dropdown with text decoration options
     *
     * @since 1.6.1
     * @param string $key
     * @param string $value
     */
    public function print_theme_text_decoration_option( $key ) {

        $value = $this->active_theme[$key];

        ?>

            <select name='settings[<?php echo $key ?>]'>
                <option value='none' <?php selected( $value, 'none' ); ?>><?php _e("None", "megamenu") ?></option>
                <option value='underline' <?php selected( $value, 'underline' ); ?>><?php _e("Underline", "megamenu") ?></option>
            </select>

        <?php
    }


    /**
     * Print a checkbox option
     *
     * @since 1.6.1
     * @param string $key
     * @param string $value
     */
    public function print_theme_checkbox_option( $key ) {

        $value = $this->active_theme[$key];

        ?>

            <input type='hidden' name='checkboxes[<?php echo $key ?>]' />
            <input type='checkbox' name='settings[<?php echo $key ?>]' <?php checked( $value, 'on' ); ?> />

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