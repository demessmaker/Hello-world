<?php
/**
 * Plugin Name: WICM Content Importer
 * Plugin URI: https://westislandmusiclessons.com
 * Description: One-click importer for West Island Conservatory of Music website content with built-in SEO optimization. Creates all pages, menus, configures site settings, and adds schema markup, meta tags, and Open Graph support.
 * Version: 2.0.0
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

        // SEO hooks — only run on the frontend
        if ( ! is_admin() ) {
            add_action( 'wp_head', array( $this, 'output_seo_meta' ), 1 );
            add_action( 'wp_head', array( $this, 'output_schema_markup' ), 2 );
            add_action( 'wp_head', array( $this, 'output_open_graph' ), 3 );
            add_action( 'wp_head', array( $this, 'output_canonical_url' ), 4 );
            add_filter( 'pre_get_document_title', array( $this, 'custom_page_title' ), 10 );
            add_filter( 'document_title_parts', array( $this, 'filter_title_parts' ), 10 );
        }
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
                    <h3>SEO Optimization (Automatic)</h3>
                    <ul>
                        <li>Custom meta titles optimized for local search</li>
                        <li>Meta descriptions with keywords and CTAs</li>
                        <li>Open Graph tags for social media sharing</li>
                        <li>Twitter Card meta tags</li>
                        <li>Canonical URLs to prevent duplicate content</li>
                        <li>LocalBusiness (MusicSchool) schema markup (JSON-LD)</li>
                        <li>BreadcrumbList schema for navigation</li>
                        <li>Review/AggregateRating schema on testimonials page</li>
                        <li>Service catalog schema with lesson types</li>
                        <li>Robots meta tags (index, follow)</li>
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

                // Save SEO metadata if present
                if ( isset( $data['seo'] ) ) {
                    $seo = $data['seo'];
                    if ( ! empty( $seo['meta_title'] ) ) {
                        update_post_meta( $page_id, '_wicm_meta_title', sanitize_text_field( $seo['meta_title'] ) );
                    }
                    if ( ! empty( $seo['meta_description'] ) ) {
                        update_post_meta( $page_id, '_wicm_meta_description', sanitize_text_field( $seo['meta_description'] ) );
                    }
                    if ( ! empty( $seo['og_title'] ) ) {
                        update_post_meta( $page_id, '_wicm_og_title', sanitize_text_field( $seo['og_title'] ) );
                    }
                    if ( ! empty( $seo['og_description'] ) ) {
                        update_post_meta( $page_id, '_wicm_og_description', sanitize_text_field( $seo['og_description'] ) );
                    }
                    if ( ! empty( $seo['og_type'] ) ) {
                        update_post_meta( $page_id, '_wicm_og_type', sanitize_text_field( $seo['og_type'] ) );
                    }
                    if ( ! empty( $seo['focus_keywords'] ) ) {
                        update_post_meta( $page_id, '_wicm_focus_keywords', array_map( 'sanitize_text_field', $seo['focus_keywords'] ) );
                    }
                }

                $results[] = array(
                    'success' => true,
                    'message' => "Created page: {$page_info['title']} (ID: {$page_id})" . ( isset( $data['seo'] ) ? ' + SEO meta' : '' ),
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
     * Output SEO meta description for imported pages.
     */
    public function output_seo_meta() {
        if ( ! is_page() ) {
            return;
        }

        $post_id = get_the_ID();
        $meta_description = get_post_meta( $post_id, '_wicm_meta_description', true );

        if ( $meta_description ) {
            echo '<meta name="description" content="' . esc_attr( $meta_description ) . '">' . "\n";
        }

        // Output robots meta
        echo '<meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">' . "\n";
    }

    /**
     * Output LocalBusiness schema markup (JSON-LD) on all frontend pages.
     */
    public function output_schema_markup() {
        $settings = $this->load_json( 'site-settings.json' );
        if ( ! $settings ) {
            return;
        }

        $address = $settings['contact']['address'];

        // Build opening hours spec
        $hours_spec = array();
        $weekday_map = array(
            'monday'    => 'Monday',
            'tuesday'   => 'Tuesday',
            'wednesday' => 'Wednesday',
            'thursday'  => 'Thursday',
            'friday'    => 'Friday',
            'saturday'  => 'Saturday',
        );

        foreach ( $weekday_map as $key => $day ) {
            if ( isset( $settings['hours'][ $key ] ) && 'Closed' !== $settings['hours'][ $key ] ) {
                $times = explode( ' - ', $settings['hours'][ $key ] );
                if ( count( $times ) === 2 ) {
                    $hours_spec[] = array(
                        '@type'     => 'OpeningHoursSpecification',
                        'dayOfWeek' => $day,
                        'opens'     => gmdate( 'H:i', strtotime( trim( $times[0] ) ) ),
                        'closes'    => gmdate( 'H:i', strtotime( trim( $times[1] ) ) ),
                    );
                }
            }
        }

        $schema = array(
            '@context'    => 'https://schema.org',
            '@type'       => 'MusicSchool',
            'name'        => $settings['site_title'],
            'description' => 'The West Island\'s leading music school offering private lessons in piano, guitar, voice, drums, and 12 other instruments. Serving Pointe-Claire, DDO, Kirkland, Beaconsfield, Pierrefonds, and Dorval.',
            'url'         => home_url( '/' ),
            'telephone'   => $settings['contact']['phone'],
            'email'       => $settings['contact']['email'],
            'address'     => array(
                '@type'           => 'PostalAddress',
                'streetAddress'   => $address['street'],
                'addressLocality' => $address['city'],
                'addressRegion'   => $address['province'],
                'postalCode'      => $address['postal_code'],
                'addressCountry'  => $address['country'],
            ),
            'geo' => array(
                '@type'     => 'GeoCoordinates',
                'latitude'  => 45.4490,
                'longitude' => -73.8166,
            ),
            'openingHoursSpecification' => $hours_spec,
            'priceRange'    => '$$',
            'paymentAccepted' => implode( ', ', $settings['payment_methods'] ),
            'currenciesAccepted' => $settings['currency'],
            'areaServed' => array_map( function( $area ) {
                return array(
                    '@type' => 'City',
                    'name'  => $area,
                );
            }, $settings['service_areas'] ),
            'sameAs' => array(
                $settings['social_media']['facebook'],
            ),
            'hasOfferCatalog' => array(
                '@type' => 'OfferCatalog',
                'name'  => 'Music Lessons',
                'itemListElement' => array(
                    array( '@type' => 'Offer', 'itemOffered' => array( '@type' => 'Service', 'name' => 'Piano Lessons' ) ),
                    array( '@type' => 'Offer', 'itemOffered' => array( '@type' => 'Service', 'name' => 'Guitar Lessons' ) ),
                    array( '@type' => 'Offer', 'itemOffered' => array( '@type' => 'Service', 'name' => 'Voice Lessons' ) ),
                    array( '@type' => 'Offer', 'itemOffered' => array( '@type' => 'Service', 'name' => 'Drum Lessons' ) ),
                    array( '@type' => 'Offer', 'itemOffered' => array( '@type' => 'Service', 'name' => 'Violin Lessons' ) ),
                    array( '@type' => 'Offer', 'itemOffered' => array( '@type' => 'Service', 'name' => 'Saxophone Lessons' ) ),
                ),
            ),
        );

        // Add Review schema from testimonials if on testimonials page
        if ( is_page( 'testimonials' ) ) {
            $testimonials_data = $this->load_json( 'pages/testimonials.json' );
            if ( $testimonials_data && isset( $testimonials_data['content']['testimonials'] ) ) {
                $reviews = array();
                foreach ( $testimonials_data['content']['testimonials'] as $testimonial ) {
                    $reviews[] = array(
                        '@type'        => 'Review',
                        'author'       => array(
                            '@type' => 'Person',
                            'name'  => $testimonial['name'],
                        ),
                        'reviewBody'   => $testimonial['quote'],
                        'reviewRating' => array(
                            '@type'       => 'Rating',
                            'ratingValue' => '5',
                            'bestRating'  => '5',
                        ),
                    );
                }
                $schema['review']          = $reviews;
                $schema['aggregateRating'] = array(
                    '@type'       => 'AggregateRating',
                    'ratingValue' => '5',
                    'reviewCount' => count( $reviews ),
                    'bestRating'  => '5',
                );
            }
        }

        echo '<script type="application/ld+json">' . "\n";
        echo wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
        echo "\n</script>\n";

        // BreadcrumbList schema
        if ( is_page() && ! is_front_page() ) {
            $breadcrumb = array(
                '@context'        => 'https://schema.org',
                '@type'           => 'BreadcrumbList',
                'itemListElement' => array(
                    array(
                        '@type'    => 'ListItem',
                        'position' => 1,
                        'name'     => 'Home',
                        'item'     => home_url( '/' ),
                    ),
                    array(
                        '@type'    => 'ListItem',
                        'position' => 2,
                        'name'     => get_the_title(),
                        'item'     => get_permalink(),
                    ),
                ),
            );

            echo '<script type="application/ld+json">' . "\n";
            echo wp_json_encode( $breadcrumb, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
            echo "\n</script>\n";
        }
    }

    /**
     * Output Open Graph meta tags.
     */
    public function output_open_graph() {
        if ( ! is_page() ) {
            return;
        }

        $post_id     = get_the_ID();
        $og_title    = get_post_meta( $post_id, '_wicm_og_title', true );
        $og_desc     = get_post_meta( $post_id, '_wicm_og_description', true );
        $og_type     = get_post_meta( $post_id, '_wicm_og_type', true );
        $site_name   = get_bloginfo( 'name' );

        if ( ! $og_title ) {
            $og_title = get_the_title() . ' | ' . $site_name;
        }
        if ( ! $og_desc ) {
            $og_desc = get_post_meta( $post_id, '_wicm_meta_description', true );
        }
        if ( ! $og_type ) {
            $og_type = 'website';
        }

        echo '<meta property="og:title" content="' . esc_attr( $og_title ) . '">' . "\n";
        echo '<meta property="og:description" content="' . esc_attr( $og_desc ) . '">' . "\n";
        echo '<meta property="og:type" content="' . esc_attr( $og_type ) . '">' . "\n";
        echo '<meta property="og:url" content="' . esc_url( get_permalink() ) . '">' . "\n";
        echo '<meta property="og:site_name" content="' . esc_attr( $site_name ) . '">' . "\n";
        echo '<meta property="og:locale" content="en_CA">' . "\n";

        // Twitter card tags
        echo '<meta name="twitter:card" content="summary">' . "\n";
        echo '<meta name="twitter:title" content="' . esc_attr( $og_title ) . '">' . "\n";
        echo '<meta name="twitter:description" content="' . esc_attr( $og_desc ) . '">' . "\n";
    }

    /**
     * Output canonical URL.
     */
    public function output_canonical_url() {
        if ( is_page() || is_single() ) {
            echo '<link rel="canonical" href="' . esc_url( get_permalink() ) . '">' . "\n";
        } elseif ( is_front_page() ) {
            echo '<link rel="canonical" href="' . esc_url( home_url( '/' ) ) . '">' . "\n";
        }
    }

    /**
     * Override document title for imported pages.
     */
    public function custom_page_title( $title ) {
        if ( ! is_page() ) {
            return $title;
        }

        $meta_title = get_post_meta( get_the_ID(), '_wicm_meta_title', true );
        if ( $meta_title ) {
            return $meta_title;
        }

        return $title;
    }

    /**
     * Filter title parts to remove "Home -" prefix.
     */
    public function filter_title_parts( $title_parts ) {
        if ( is_front_page() ) {
            $meta_title = get_post_meta( get_the_ID(), '_wicm_meta_title', true );
            if ( $meta_title ) {
                $title_parts['title'] = $meta_title;
                unset( $title_parts['tagline'] );
                unset( $title_parts['site'] );
            }
        }
        return $title_parts;
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
