<?php
/**
 * Plugin Name: WICM Content Importer
 * Plugin URI: https://westislandmusiclessons.com
 * Description: One-click importer for West Island Conservatory of Music website content. Creates all pages, menus, and configures site settings.
 * Version: 1.0.0
 * Author: West Island Conservatory of Music
 * License: GPL v2 or later
 * Text Domain: wicm-importer
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WICM_Content_Importer {

    private $plugin_dir;
    private $content_dir;

    public function __construct() {
        $this->plugin_dir  = plugin_dir_path( __FILE__ );
        $this->content_dir = $this->plugin_dir . 'content/';

        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_post_wicm_import', array( $this, 'handle_import' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
    }

    /**
     * Add the import page to the WordPress admin menu.
     */
    public function add_admin_menu() {
        add_management_page(
            'WICM Content Importer',
            'WICM Importer',
            'manage_options',
            'wicm-importer',
            array( $this, 'render_admin_page' )
        );
    }

    /**
     * Enqueue admin styles on our page only.
     */
    public function enqueue_styles( $hook ) {
        if ( 'tools_page_wicm-importer' !== $hook ) {
            return;
        }
        wp_add_inline_style( 'wp-admin', '
            .wicm-wrap { max-width: 800px; }
            .wicm-wrap .card { padding: 20px; margin-bottom: 20px; }
            .wicm-wrap .import-section { margin: 15px 0; padding: 15px; background: #f9f9f9; border-left: 4px solid #0073aa; }
            .wicm-wrap .import-section h3 { margin-top: 0; }
            .wicm-wrap .status-ok { color: #46b450; }
            .wicm-wrap .status-warn { color: #ffb900; }
            .wicm-wrap .status-error { color: #dc3232; }
            .wicm-results { margin-top: 20px; }
            .wicm-results li { padding: 4px 0; }
            .wicm-results .dashicons { margin-right: 5px; }
        ' );
    }

    /**
     * Render the admin import page.
     */
    public function render_admin_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized access' );
        }

        $imported = get_option( 'wicm_import_completed', false );
        ?>
        <div class="wrap wicm-wrap">
            <h1>West Island Conservatory of Music - Content Importer</h1>

            <?php if ( $imported ) : ?>
                <div class="notice notice-success">
                    <p><strong>Content has already been imported.</strong> Re-importing will create duplicate pages.</p>
                </div>
            <?php endif; ?>

            <?php $this->show_results_notice(); ?>

            <div class="card">
                <h2>What This Importer Will Do</h2>
                <div class="import-section">
                    <h3>Site Settings</h3>
                    <ul>
                        <li>Set site title to "West Island Conservatory of Music"</li>
                        <li>Set tagline to "Making music magical"</li>
                        <li>Configure timezone (America/Toronto)</li>
                        <li>Set permalink structure to pretty URLs</li>
                    </ul>
                </div>

                <div class="import-section">
                    <h3>Pages (6)</h3>
                    <ul>
                        <li><strong>Home</strong> — Hero section, services overview, featured products</li>
                        <li><strong>Courses</strong> — 16 course offerings, program features</li>
                        <li><strong>About Us</strong> — History, staff, mission</li>
                        <li><strong>Contact Us</strong> — Address, phone, email, hours</li>
                        <li><strong>Our Store</strong> — Product categories, featured instruments</li>
                        <li><strong>Testimonials</strong> — 7 student/parent testimonials</li>
                    </ul>
                </div>

                <div class="import-section">
                    <h3>Navigation Menu</h3>
                    <ul>
                        <li>Primary Menu: Home, Courses, Our Store, Testimonials, About Us, Contact Us</li>
                    </ul>
                </div>
            </div>

            <div class="card">
                <h2>Import Options</h2>
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                    <?php wp_nonce_field( 'wicm_import_nonce', 'wicm_nonce' ); ?>
                    <input type="hidden" name="action" value="wicm_import">

                    <table class="form-table">
                        <tr>
                            <th scope="row">Import Settings</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="import_settings" value="1" checked>
                                    Site title, tagline, timezone, permalinks
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Import Pages</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="import_pages" value="1" checked>
                                    All 6 pages with full content
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Set Static Front Page</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="set_front_page" value="1" checked>
                                    Set Home as the static front page
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Import Menu</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="import_menu" value="1" checked>
                                    Create primary navigation menu
                                </label>
                            </td>
                        </tr>
                    </table>

                    <?php submit_button( 'Import Content Now', 'primary', 'submit', true ); ?>
                </form>
            </div>
        </div>
        <?php
    }

    /**
     * Show results notice after import.
     */
    private function show_results_notice() {
        $results = get_transient( 'wicm_import_results' );
        if ( ! $results ) {
            return;
        }
        delete_transient( 'wicm_import_results' );

        echo '<div class="wicm-results">';
        echo '<h2>Import Results</h2>';
        echo '<ul>';
        foreach ( $results as $result ) {
            $icon  = $result['success'] ? 'yes-alt' : 'warning';
            $class = $result['success'] ? 'status-ok' : 'status-error';
            printf(
                '<li><span class="dashicons dashicons-%s %s"></span> %s</li>',
                esc_attr( $icon ),
                esc_attr( $class ),
                esc_html( $result['message'] )
            );
        }
        echo '</ul></div>';
    }

    /**
     * Handle the import form submission.
     */
    public function handle_import() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized access' );
        }

        check_admin_referer( 'wicm_import_nonce', 'wicm_nonce' );

        $results = array();

        // Import settings
        if ( ! empty( $_POST['import_settings'] ) ) {
            $results = array_merge( $results, $this->import_settings() );
        }

        // Import pages
        $page_ids = array();
        if ( ! empty( $_POST['import_pages'] ) ) {
            $page_results = $this->import_pages();
            $results      = array_merge( $results, $page_results['results'] );
            $page_ids     = $page_results['page_ids'];
        }

        // Set static front page
        if ( ! empty( $_POST['set_front_page'] ) && ! empty( $page_ids['home'] ) ) {
            update_option( 'show_on_front', 'page' );
            update_option( 'page_on_front', $page_ids['home'] );
            $results[] = array(
                'success' => true,
                'message' => 'Home set as static front page',
            );
        }

        // Import menu
        if ( ! empty( $_POST['import_menu'] ) ) {
            $results = array_merge( $results, $this->import_menu( $page_ids ) );
        }

        update_option( 'wicm_import_completed', true );
        set_transient( 'wicm_import_results', $results, 60 );

        wp_safe_redirect( admin_url( 'tools.php?page=wicm-importer' ) );
        exit;
    }

    /**
     * Import site settings.
     */
    private function import_settings() {
        $results = array();

        $settings = $this->load_json( 'site-settings.json' );
        if ( ! $settings ) {
            $results[] = array(
                'success' => false,
                'message' => 'Could not load site-settings.json',
            );
            return $results;
        }

        update_option( 'blogname', $settings['site_title'] );
        update_option( 'blogdescription', $settings['tagline'] );
        update_option( 'timezone_string', $settings['timezone'] );
        update_option( 'date_format', 'F j, Y' );
        update_option( 'time_format', 'g:i A' );
        update_option( 'default_comment_status', 'closed' );

        // Set permalink structure
        global $wp_rewrite;
        $wp_rewrite->set_permalink_structure( '/%postname%/' );
        $wp_rewrite->flush_rules();

        $results[] = array(
            'success' => true,
            'message' => 'Site settings imported (title, tagline, timezone, permalinks)',
        );

        return $results;
    }

    /**
     * Import all pages from JSON files.
     */
    private function import_pages() {
        $results  = array();
        $page_ids = array();

        $pages = array(
            'home'         => array( 'file' => 'pages/home.json', 'title' => 'Home' ),
            'courses'      => array( 'file' => 'pages/courses.json', 'title' => 'Courses' ),
            'about'        => array( 'file' => 'pages/about.json', 'title' => 'About Us' ),
            'contact'      => array( 'file' => 'pages/contact.json', 'title' => 'Contact Us' ),
            'store'        => array( 'file' => 'pages/our-store.json', 'title' => 'Our Store' ),
            'testimonials' => array( 'file' => 'pages/testimonials.json', 'title' => 'Testimonials' ),
        );

        foreach ( $pages as $key => $page_info ) {
            $data = $this->load_json( $page_info['file'] );
            if ( ! $data ) {
                $results[] = array(
                    'success' => false,
                    'message' => "Could not load {$page_info['file']}",
                );
                continue;
            }

            $page_id = wp_insert_post( array(
                'post_title'   => $data['title'],
                'post_name'    => $data['slug'],
                'post_content' => $data['html_content'],
                'post_status'  => 'publish',
                'post_type'    => 'page',
                'comment_status' => 'closed',
            ) );

            if ( is_wp_error( $page_id ) ) {
                $results[] = array(
                    'success' => false,
                    'message' => "Failed to create page: {$page_info['title']} - " . $page_id->get_error_message(),
                );
            } else {
                $page_ids[ $key ] = $page_id;
                $results[] = array(
                    'success' => true,
                    'message' => "Created page: {$page_info['title']} (ID: {$page_id})",
                );
            }
        }

        return array(
            'results'  => $results,
            'page_ids' => $page_ids,
        );
    }

    /**
     * Create the primary navigation menu.
     */
    private function import_menu( $page_ids ) {
        $results = array();

        // Delete existing menu if present
        $existing = wp_get_nav_menu_object( 'Primary Menu' );
        if ( $existing ) {
            wp_delete_nav_menu( $existing->term_id );
        }

        $menu_id = wp_create_nav_menu( 'Primary Menu' );
        if ( is_wp_error( $menu_id ) ) {
            $results[] = array(
                'success' => false,
                'message' => 'Failed to create menu: ' . $menu_id->get_error_message(),
            );
            return $results;
        }

        $menu_items = array(
            array( 'key' => 'home', 'title' => 'Home', 'order' => 1 ),
            array( 'key' => 'courses', 'title' => 'Courses', 'order' => 2 ),
            array( 'key' => 'store', 'title' => 'Our Store', 'order' => 3 ),
            array( 'key' => 'testimonials', 'title' => 'Testimonials', 'order' => 4 ),
            array( 'key' => 'about', 'title' => 'About Us', 'order' => 5 ),
            array( 'key' => 'contact', 'title' => 'Contact Us', 'order' => 6 ),
        );

        $items_added = 0;
        foreach ( $menu_items as $item ) {
            if ( empty( $page_ids[ $item['key'] ] ) ) {
                continue;
            }

            $result = wp_update_nav_menu_item( $menu_id, 0, array(
                'menu-item-title'     => $item['title'],
                'menu-item-object'    => 'page',
                'menu-item-object-id' => $page_ids[ $item['key'] ],
                'menu-item-type'      => 'post_type',
                'menu-item-status'    => 'publish',
                'menu-item-position'  => $item['order'],
            ) );

            if ( ! is_wp_error( $result ) ) {
                $items_added++;
            }
        }

        // Try to assign menu to primary theme location
        $locations = get_theme_mod( 'nav_menu_locations', array() );
        $theme_locations = get_registered_nav_menus();

        $assigned = false;
        foreach ( array( 'primary', 'main-menu', 'header-menu', 'menu-1' ) as $location ) {
            if ( isset( $theme_locations[ $location ] ) ) {
                $locations[ $location ] = $menu_id;
                set_theme_mod( 'nav_menu_locations', $locations );
                $assigned = true;
                break;
            }
        }

        $results[] = array(
            'success' => true,
            'message' => "Created Primary Menu with {$items_added} items" . ( $assigned ? ' (assigned to theme)' : ' (assign manually in Appearance > Menus)' ),
        );

        return $results;
    }

    /**
     * Load and decode a JSON file from the content directory.
     */
    private function load_json( $filename ) {
        $filepath = $this->content_dir . $filename;
        if ( ! file_exists( $filepath ) ) {
            return false;
        }

        $json = file_get_contents( $filepath );
        if ( false === $json ) {
            return false;
        }

        $data = json_decode( $json, true );
        if ( null === $data ) {
            return false;
        }

        return $data;
    }
}

// Initialize the plugin
new WICM_Content_Importer();
