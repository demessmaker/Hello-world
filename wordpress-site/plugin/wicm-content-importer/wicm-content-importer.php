<?php
/**
 * Plugin Name: WICM Content Importer
 * Plugin URI: https://westislandmusiclessons.com
 * Description: Complete site importer for West Island Conservatory of Music with SEO optimization, image migration, contact form, blog posts, footer widget, and favicon setup.
 * Version: 3.1.0
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

        // Frontend hooks — only run on the frontend
        if ( ! is_admin() ) {
            add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_styles' ) );
            add_action( 'wp_footer', array( $this, 'output_back_to_top' ) );
            add_action( 'wp_head', array( $this, 'output_seo_meta' ), 1 );
            add_action( 'wp_head', array( $this, 'output_schema_markup' ), 2 );
            add_action( 'wp_head', array( $this, 'output_open_graph' ), 3 );
            add_action( 'wp_head', array( $this, 'output_canonical_url' ), 4 );
            add_filter( 'pre_get_document_title', array( $this, 'custom_page_title' ), 10 );
            add_filter( 'document_title_parts', array( $this, 'filter_title_parts' ), 10 );
        }

        // Register footer widget
        add_action( 'widgets_init', array( $this, 'register_footer_widget' ) );
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
     * Enqueue custom theme styles on the frontend.
     */
    public function enqueue_frontend_styles() {
        wp_enqueue_style(
            'wicm-theme-styles',
            plugin_dir_url( __FILE__ ) . 'assets/css/wicm-theme.css',
            array(),
            '3.1.0'
        );
    }

    /**
     * Output back-to-top button and scroll script in footer.
     */
    public function output_back_to_top() {
        ?>
        <a href="#" class="wicm-back-to-top" id="wicm-back-to-top" aria-label="Back to top">&uarr;</a>
        <script>
        (function(){
            var btn = document.getElementById('wicm-back-to-top');
            if (!btn) return;
            window.addEventListener('scroll', function(){
                btn.classList.toggle('visible', window.scrollY > 300);
            });
            btn.addEventListener('click', function(e){
                e.preventDefault();
                window.scrollTo({top: 0, behavior: 'smooth'});
            });
        })();
        </script>
        <?php
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
                        <li><strong>Contact Us</strong> — Address, phone, email, hours, contact form</li>
                        <li><strong>Our Store</strong> — Product categories, featured instruments</li>
                        <li><strong>Testimonials</strong> — 7 student/parent testimonials</li>
                    </ul>
                </div>

                <div class="import-section">
                    <h3>Blog Posts (6)</h3>
                    <ul>
                        <li>The Piano Man — History of piano in popular music</li>
                        <li>Percussion Instruments — Drumming guide</li>
                        <li>History of the Guitar — Origins to modern day</li>
                        <li>The Sounds of Modern Music — Technology meets tradition</li>
                        <li>How to Decide Which Guitar to Buy — Buying guide</li>
                        <li>Saxophone, Clarinet, Trumpet, Violin &amp; Bass Lessons</li>
                    </ul>
                </div>

                <div class="import-section">
                    <h3>Images &amp; Media</h3>
                    <ul>
                        <li>Download and import all images from the original site</li>
                        <li>Set SEO-optimized alt text on all images</li>
                        <li>Set site logo as favicon/site icon</li>
                        <li>Attach images to their respective pages</li>
                    </ul>
                </div>

                <div class="import-section">
                    <h3>Contact Form</h3>
                    <ul>
                        <li>Create Contact Form 7 form (Name, Email, Phone, Message)</li>
                        <li>Embed form in Contact Us page</li>
                    </ul>
                </div>

                <div class="import-section">
                    <h3>Footer Widget</h3>
                    <ul>
                        <li>Business hours, address, and contact info in footer</li>
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
                                    All 6 pages with full content + SEO meta
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Import Blog Posts</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="import_posts" value="1" checked>
                                    6 blog posts with SEO meta
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Import Images</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="import_images" value="1" checked>
                                    Download all images from original site
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Setup Contact Form</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="import_contact_form" value="1" checked>
                                    Create Contact Form 7 form and embed in Contact page
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Setup Footer Widget</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="import_footer" value="1" checked>
                                    Add business info to footer widget area
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Set Favicon</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="import_favicon" value="1" checked>
                                    Set site logo as favicon/site icon
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

        // Import images first (so we can use them in pages)
        $image_ids = array();
        if ( ! empty( $_POST['import_images'] ) ) {
            $image_results = $this->import_images();
            $results       = array_merge( $results, $image_results['results'] );
            $image_ids     = $image_results['image_ids'];
        }

        // Import pages
        $page_ids = array();
        if ( ! empty( $_POST['import_pages'] ) ) {
            $page_results = $this->import_pages( $image_ids );
            $results      = array_merge( $results, $page_results['results'] );
            $page_ids     = $page_results['page_ids'];
        }

        // Import blog posts
        if ( ! empty( $_POST['import_posts'] ) ) {
            $results = array_merge( $results, $this->import_blog_posts() );
        }

        // Setup contact form and embed in contact page
        if ( ! empty( $_POST['import_contact_form'] ) ) {
            $results = array_merge( $results, $this->setup_contact_form( isset( $page_ids['contact'] ) ? $page_ids['contact'] : 0 ) );
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

        // Setup footer widget
        if ( ! empty( $_POST['import_footer'] ) ) {
            $results = array_merge( $results, $this->setup_footer_widget() );
        }

        // Set favicon
        if ( ! empty( $_POST['import_favicon'] ) && ! empty( $image_ids['logo'] ) ) {
            update_option( 'site_icon', $image_ids['logo'] );
            $results[] = array(
                'success' => true,
                'message' => 'Site favicon/icon set from logo',
            );
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
     * Download and import all images from the original site.
     */
    private function import_images() {
        $results   = array();
        $image_ids = array();

        // Ensure media handling functions are available
        if ( ! function_exists( 'media_sideload_image' ) ) {
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }

        $images_data = $this->load_json( 'images.json' );
        if ( ! $images_data ) {
            $results[] = array(
                'success' => false,
                'message' => 'Could not load images.json',
            );
            return array( 'results' => $results, 'image_ids' => $image_ids );
        }

        // Import logo
        if ( ! empty( $images_data['logo'] ) ) {
            $logo = $images_data['logo'];
            $logo_id = $this->sideload_image( $logo['url'], $logo['alt'] );
            if ( $logo_id ) {
                $image_ids['logo'] = $logo_id;

                // Also set as custom logo for the theme
                set_theme_mod( 'custom_logo', $logo_id );

                $results[] = array(
                    'success' => true,
                    'message' => "Imported logo (ID: {$logo_id})",
                );
            } else {
                $results[] = array(
                    'success' => false,
                    'message' => 'Failed to import logo image',
                );
            }
        }

        // Import page images
        $page_image_count = 0;
        if ( ! empty( $images_data['page_images'] ) ) {
            foreach ( $images_data['page_images'] as $page_key => $page_imgs ) {
                foreach ( $page_imgs as $img ) {
                    $img_id = $this->sideload_image( $img['url'], $img['alt'] );
                    if ( $img_id ) {
                        $image_ids[ $img['placement'] ] = $img_id;
                        $page_image_count++;
                    }
                }
            }
            $results[] = array(
                'success' => $page_image_count > 0,
                'message' => "Imported {$page_image_count} page images with SEO alt text",
            );
        }

        // Import slider images
        $slider_count = 0;
        if ( ! empty( $images_data['slider_images'] ) ) {
            foreach ( $images_data['slider_images'] as $idx => $img ) {
                $img_id = $this->sideload_image( $img['url'], $img['alt'] );
                if ( $img_id ) {
                    $image_ids[ 'slider_' . $idx ] = $img_id;
                    $slider_count++;
                }
            }
            $results[] = array(
                'success' => $slider_count > 0,
                'message' => "Imported {$slider_count} slider/hero images with SEO alt text",
            );
        }

        // Store image IDs for later use
        update_option( 'wicm_imported_images', $image_ids );

        return array( 'results' => $results, 'image_ids' => $image_ids );
    }

    /**
     * Download an image from a URL and add it to the WordPress media library.
     *
     * @param string $url The image URL to download.
     * @param string $alt The alt text for the image.
     * @param int    $post_id Optional post to attach the image to.
     * @return int|false The attachment ID, or false on failure.
     */
    private function sideload_image( $url, $alt = '', $post_id = 0 ) {
        if ( ! function_exists( 'media_sideload_image' ) ) {
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }

        // Download the file to a temp location
        $tmp = download_url( $url );
        if ( is_wp_error( $tmp ) ) {
            return false;
        }

        // Extract filename from URL
        $url_path = wp_parse_url( $url, PHP_URL_PATH );
        $filename = sanitize_file_name( basename( $url_path ) );

        $file_array = array(
            'name'     => $filename,
            'tmp_name' => $tmp,
        );

        // Sideload the file into the media library
        $attachment_id = media_handle_sideload( $file_array, $post_id );

        if ( is_wp_error( $attachment_id ) ) {
            @unlink( $tmp );
            return false;
        }

        // Set alt text
        if ( $alt ) {
            update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( $alt ) );
        }

        return $attachment_id;
    }

    /**
     * Import all pages from JSON files.
     */
    private function import_pages( $image_ids = array() ) {
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

            $html_content = $data['html_content'];

            // Insert images into HTML content if we have imported images
            if ( ! empty( $image_ids ) ) {
                $html_content = $this->insert_images_into_content( $html_content, $key, $image_ids );
            }

            $page_id = wp_insert_post( array(
                'post_title'     => $data['title'],
                'post_name'      => $data['slug'],
                'post_content'   => $html_content,
                'post_status'    => 'publish',
                'post_type'      => 'page',
                'comment_status' => 'closed',
            ) );

            if ( is_wp_error( $page_id ) ) {
                $results[] = array(
                    'success' => false,
                    'message' => "Failed to create page: {$page_info['title']} - " . $page_id->get_error_message(),
                );
            } else {
                $page_ids[ $key ] = $page_id;

                // Set featured image if we have a relevant slider image
                $this->set_page_featured_image( $key, $page_id, $image_ids );

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
     * Insert imported images into page HTML content.
     */
    private function insert_images_into_content( $html, $page_key, $image_ids ) {
        if ( 'home' === $page_key ) {
            // Build image slider from imported slider images
            $slider_html = $this->build_slider_html( $image_ids );
            if ( $slider_html ) {
                // Replace the hero section with slider + hero overlay
                $html = str_replace(
                    '<div class="hero-section">',
                    $slider_html . "\n" . '<div class="hero-section">',
                    $html
                );
            }

            // Insert service card images
            $image_map = array(
                'service-card-voice' => array( 'before' => '<h3>Voice Training</h3>', 'alt' => 'Voice training lessons at West Island Conservatory of Music' ),
                'service-card-piano' => array( 'before' => '<h3>Piano &amp; Keyboard Lessons</h3>', 'alt' => 'Piano lessons at West Island Conservatory of Music' ),
                'service-card-guitar' => array( 'before' => '<h3>Guitar &amp; Strings Lessons</h3>', 'alt' => 'Guitar lessons in West Island Montreal' ),
            );

            foreach ( $image_map as $placement => $info ) {
                if ( ! empty( $image_ids[ $placement ] ) ) {
                    $img_url = wp_get_attachment_url( $image_ids[ $placement ] );
                    if ( $img_url ) {
                        $img_tag = '<img src="' . esc_url( $img_url ) . '" alt="' . esc_attr( $info['alt'] ) . '" loading="lazy" width="252" height="111">' . "\n      ";
                        $html = str_replace( $info['before'], $img_tag . $info['before'], $html );
                    }
                }
            }

            // Insert product images (Gibson guitars)
            $product_image_map = array(
                'slider_11' => array( 'before' => '<h3>2016 Gibson Les Paul Studio</h3>', 'alt' => 'Gibson Les Paul Studio Alpine White' ),
                'slider_12' => array( 'before' => '<h3>2017 Gibson Les Paul Classic T</h3>', 'alt' => 'Gibson Les Paul Classic Green Ocean Burst' ),
            );

            foreach ( $product_image_map as $key => $info ) {
                if ( ! empty( $image_ids[ $key ] ) ) {
                    $img_url = wp_get_attachment_url( $image_ids[ $key ] );
                    if ( $img_url ) {
                        $img_tag = '<img src="' . esc_url( $img_url ) . '" alt="' . esc_attr( $info['alt'] ) . '" loading="lazy">' . "\n      ";
                        $html = str_replace( $info['before'], $img_tag . $info['before'], $html );
                    }
                }
            }
        }

        if ( 'testimonials' === $page_key ) {
            // Insert Amanda Walsh photo
            if ( ! empty( $image_ids['testimonial-amanda'] ) ) {
                $img_url = wp_get_attachment_url( $image_ids['testimonial-amanda'] );
                if ( $img_url ) {
                    $img_tag = '<img src="' . esc_url( $img_url ) . '" alt="Amanda Walsh - former violin and voice student" loading="lazy" width="150" height="150" class="testimonial-photo">';
                    $html = str_replace(
                        '<strong>Amanda Walsh</strong>',
                        $img_tag . "\n      <strong>Amanda Walsh</strong>",
                        $html
                    );
                }
            }
        }

        return $html;
    }

    /**
     * Build a CSS-only image slider from imported slider images.
     */
    private function build_slider_html( $image_ids ) {
        $slider_images = array();
        for ( $i = 0; $i < 13; $i++ ) {
            $key = 'slider_' . $i;
            if ( ! empty( $image_ids[ $key ] ) ) {
                $url = wp_get_attachment_url( $image_ids[ $key ] );
                $alt = get_post_meta( $image_ids[ $key ], '_wp_attachment_image_alt', true );
                if ( $url ) {
                    $slider_images[] = array( 'url' => $url, 'alt' => $alt );
                }
            }
        }

        if ( empty( $slider_images ) ) {
            return '';
        }

        $html = '<div class="wicm-slider">' . "\n";
        $html .= '  <div class="wicm-slider-track">' . "\n";
        foreach ( $slider_images as $img ) {
            $html .= '    <img src="' . esc_url( $img['url'] ) . '" alt="' . esc_attr( $img['alt'] ) . '" width="1197" height="404">' . "\n";
        }
        $html .= '  </div>' . "\n";
        $html .= '</div>' . "\n";

        return $html;
    }

    /**
     * Set featured image for a page based on available slider images.
     */
    private function set_page_featured_image( $page_key, $page_id, $image_ids ) {
        $featured_map = array(
            'home'         => 'slider_0',  // strings slider
            'courses'      => 'slider_2',  // piano
            'about'        => 'slider_1',  // voice
            'contact'      => 'slider_4',  // store
            'store'        => 'slider_4',  // store
            'testimonials' => 'slider_7',  // girl band
        );

        if ( isset( $featured_map[ $page_key ] ) && ! empty( $image_ids[ $featured_map[ $page_key ] ] ) ) {
            set_post_thumbnail( $page_id, $image_ids[ $featured_map[ $page_key ] ] );
        }
    }

    /**
     * Import blog posts from JSON.
     */
    private function import_blog_posts() {
        $results = array();

        $posts_data = $this->load_json( 'posts/blog-posts.json' );
        if ( ! $posts_data || empty( $posts_data['posts'] ) ) {
            $results[] = array(
                'success' => false,
                'message' => 'Could not load posts/blog-posts.json',
            );
            return $results;
        }

        $post_count = 0;
        foreach ( $posts_data['posts'] as $post_data ) {
            // Create or get category
            $cat_id = 0;
            if ( ! empty( $post_data['category'] ) ) {
                $cat = get_cat_ID( $post_data['category'] );
                if ( ! $cat ) {
                    $cat = wp_create_category( $post_data['category'] );
                }
                $cat_id = $cat;
            }

            $post_id = wp_insert_post( array(
                'post_title'     => $post_data['title'],
                'post_name'      => $post_data['slug'],
                'post_content'   => $post_data['content'],
                'post_status'    => 'publish',
                'post_type'      => 'post',
                'post_category'  => $cat_id ? array( $cat_id ) : array(),
                'comment_status' => 'closed',
            ) );

            if ( ! is_wp_error( $post_id ) ) {
                $post_count++;

                // Save SEO metadata
                if ( ! empty( $post_data['seo'] ) ) {
                    $seo = $post_data['seo'];
                    if ( ! empty( $seo['meta_title'] ) ) {
                        update_post_meta( $post_id, '_wicm_meta_title', sanitize_text_field( $seo['meta_title'] ) );
                    }
                    if ( ! empty( $seo['meta_description'] ) ) {
                        update_post_meta( $post_id, '_wicm_meta_description', sanitize_text_field( $seo['meta_description'] ) );
                    }
                }
            }
        }

        $results[] = array(
            'success' => $post_count > 0,
            'message' => "Created {$post_count} blog posts with categories and SEO meta",
        );

        return $results;
    }

    /**
     * Create a Contact Form 7 form and embed it in the contact page.
     */
    private function setup_contact_form( $contact_page_id ) {
        $results = array();

        // Check if Contact Form 7 is active
        if ( ! class_exists( 'WPCF7_ContactForm' ) ) {
            $results[] = array(
                'success' => false,
                'message' => 'Contact Form 7 plugin is not active. Please install and activate it first.',
            );
            return $results;
        }

        // Check if we already created a form
        $existing = get_posts( array(
            'post_type'  => 'wpcf7_contact_form',
            'title'      => 'WICM Contact Form',
            'numberposts' => 1,
        ) );

        if ( ! empty( $existing ) ) {
            $form_id = $existing[0]->ID;
        } else {
            // Create the CF7 form
            $form_template = '<div class="wicm-contact-form">
<label>Your Name (required)
    [text* your-name]</label>

<label>Your Email (required)
    [email* your-email]</label>

<label>Your Phone
    [tel your-phone]</label>

<label>Instrument of Interest
    [select instrument "Piano" "Guitar" "Voice" "Drums" "Violin" "Saxophone" "Clarinet" "Trumpet" "Flute" "Bass" "Cello" "Keyboard" "Other"]</label>

<label>Your Message
    [textarea your-message]</label>

[submit "Send Message"]
</div>';

            $mail_template = 'From: [your-name] <[your-email]>
Subject: WICM Website Inquiry - [instrument]

Name: [your-name]
Email: [your-email]
Phone: [your-phone]
Instrument: [instrument]

Message:
[your-message]

--
This message was sent from the West Island Conservatory of Music website contact form.';

            $form = WPCF7_ContactForm::get_template();
            $form->set_properties( array(
                'form'     => $form_template,
                'mail'     => array_merge( $form->prop( 'mail' ), array(
                    'subject'   => 'WICM Website Inquiry - [instrument]',
                    'body'      => $mail_template,
                    'recipient' => 'westislandmusic@gmail.com',
                ) ),
                'messages' => array_merge( $form->prop( 'messages' ), array(
                    'mail_sent_ok' => 'Thank you for contacting West Island Conservatory of Music! We will get back to you shortly.',
                ) ),
            ) );
            $form->set_title( 'WICM Contact Form' );
            $form->save();
            $form_id = $form->id();
        }

        $results[] = array(
            'success' => true,
            'message' => "Created Contact Form 7 form (ID: {$form_id})",
        );

        // Embed the form shortcode in the contact page
        if ( $contact_page_id ) {
            $contact_page = get_post( $contact_page_id );
            if ( $contact_page ) {
                $form_shortcode = "\n\n" . '<div class="contact-form-section">' . "\n" .
                    '  <h2>Send Us a Message</h2>' . "\n" .
                    '  <p>Fill out the form below and we\'ll get back to you as soon as possible. Or call us directly at <a href="tel:514-428-5080">514-428-5080</a>.</p>' . "\n" .
                    '  [contact-form-7 id="' . $form_id . '" title="WICM Contact Form"]' . "\n" .
                    '</div>';

                wp_update_post( array(
                    'ID'           => $contact_page_id,
                    'post_content' => $contact_page->post_content . $form_shortcode,
                ) );

                $results[] = array(
                    'success' => true,
                    'message' => 'Embedded contact form in Contact Us page',
                );
            }
        }

        return $results;
    }

    /**
     * Setup footer widget with business hours and contact info.
     */
    private function setup_footer_widget() {
        $results = array();

        $settings = $this->load_json( 'site-settings.json' );
        if ( ! $settings ) {
            $results[] = array(
                'success' => false,
                'message' => 'Could not load site-settings.json for footer widget',
            );
            return $results;
        }

        $address = $settings['contact']['address'];

        // Build the footer HTML content
        $footer_html = '<div class="wicm-footer-widget">' . "\n";
        $footer_html .= '<h4>West Island Conservatory of Music</h4>' . "\n";
        $footer_html .= '<p><strong>Address:</strong><br>' . "\n";
        $footer_html .= esc_html( $address['building'] ) . '<br>' . "\n";
        $footer_html .= esc_html( $address['street'] ) . '<br>' . "\n";
        $footer_html .= esc_html( $address['city'] ) . ', ' . esc_html( $address['province'] ) . ' ' . esc_html( $address['postal_code'] ) . '</p>' . "\n";
        $footer_html .= '<p><strong>Phone:</strong> <a href="tel:' . esc_attr( $settings['contact']['phone'] ) . '">' . esc_html( $settings['contact']['phone'] ) . '</a></p>' . "\n";
        $footer_html .= '<p><strong>Email:</strong> <a href="mailto:' . esc_attr( $settings['contact']['email'] ) . '">' . esc_html( $settings['contact']['email'] ) . '</a></p>' . "\n";
        $footer_html .= '</div>';

        // Build hours widget
        $hours_html = '<div class="wicm-hours-widget">' . "\n";
        $hours_html .= '<h4>Business Hours</h4>' . "\n";
        $hours_html .= '<table class="wicm-hours-table">' . "\n";
        $day_labels = array(
            'monday' => 'Monday', 'tuesday' => 'Tuesday', 'wednesday' => 'Wednesday',
            'thursday' => 'Thursday', 'friday' => 'Friday', 'saturday' => 'Saturday', 'sunday' => 'Sunday',
        );
        foreach ( $day_labels as $key => $label ) {
            $hours_html .= '<tr><td>' . $label . '</td><td>' . esc_html( $settings['hours'][ $key ] ) . '</td></tr>' . "\n";
        }
        $hours_html .= '</table>' . "\n";
        $hours_html .= '</div>';

        // Find footer widget areas (try common names)
        $sidebars = wp_get_sidebars_widgets();
        $footer_sidebar = null;

        $footer_names = array( 'footer-1', 'footer', 'sidebar-footer', 'footer-widget-area', 'footer-sidebar' );
        foreach ( $footer_names as $name ) {
            if ( isset( $sidebars[ $name ] ) ) {
                $footer_sidebar = $name;
                break;
            }
        }

        // If no footer sidebar found, try the first available sidebar
        if ( ! $footer_sidebar ) {
            global $wp_registered_sidebars;
            foreach ( $wp_registered_sidebars as $sidebar_id => $sidebar ) {
                if ( stripos( $sidebar_id, 'footer' ) !== false || stripos( $sidebar['name'], 'footer' ) !== false ) {
                    $footer_sidebar = $sidebar_id;
                    break;
                }
            }
        }

        // Add contact info widget
        $widget_id_base = 'custom_html';
        $widget_instances = get_option( 'widget_custom_html', array() );
        if ( ! is_array( $widget_instances ) ) {
            $widget_instances = array();
        }

        // Add contact info widget
        $next_id = empty( $widget_instances ) ? 2 : max( array_keys( array_filter( $widget_instances, 'is_array' ) ) ) + 1;
        $widget_instances[ $next_id ] = array(
            'title'   => 'Contact Us',
            'content' => $footer_html,
        );

        // Add hours widget
        $hours_id = $next_id + 1;
        $widget_instances[ $hours_id ] = array(
            'title'   => 'Business Hours',
            'content' => $hours_html,
        );

        update_option( 'widget_custom_html', $widget_instances );

        // Assign widgets to footer sidebar if found
        if ( $footer_sidebar ) {
            $sidebars[ $footer_sidebar ][] = "custom_html-{$next_id}";
            $sidebars[ $footer_sidebar ][] = "custom_html-{$hours_id}";
            wp_set_sidebars_widgets( $sidebars );

            $results[] = array(
                'success' => true,
                'message' => "Added contact info and business hours widgets to footer ({$footer_sidebar})",
            );
        } else {
            // If no footer area, try sidebar-1 as fallback
            if ( isset( $sidebars['sidebar-1'] ) ) {
                $sidebars['sidebar-1'][] = "custom_html-{$next_id}";
                $sidebars['sidebar-1'][] = "custom_html-{$hours_id}";
                wp_set_sidebars_widgets( $sidebars );

                $results[] = array(
                    'success' => true,
                    'message' => 'Added contact info and business hours widgets to sidebar (no footer area found — assign manually in Appearance > Widgets)',
                );
            } else {
                $results[] = array(
                    'success' => false,
                    'message' => 'No widget area found. Widgets created but not assigned — go to Appearance > Widgets to place them.',
                );
            }
        }

        return $results;
    }

    /**
     * Register the WICM footer widget area if the theme doesn't have one.
     */
    public function register_footer_widget() {
        register_sidebar( array(
            'name'          => 'WICM Footer',
            'id'            => 'wicm-footer',
            'description'   => 'Footer widget area for West Island Conservatory of Music',
            'before_widget' => '<div id="%1$s" class="widget %2$s">',
            'after_widget'  => '</div>',
            'before_title'  => '<h4 class="widget-title">',
            'after_title'   => '</h4>',
        ) );
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

    // =========================================================================
    // SEO OUTPUT METHODS (Frontend)
    // =========================================================================

    /**
     * Output SEO meta description for imported pages.
     */
    public function output_seo_meta() {
        if ( ! is_page() && ! is_single() ) {
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

        // Get logo URL if available
        $logo_url = '';
        $custom_logo_id = get_theme_mod( 'custom_logo' );
        if ( $custom_logo_id ) {
            $logo_url = wp_get_attachment_url( $custom_logo_id );
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

        // Add logo to schema if available
        if ( $logo_url ) {
            $schema['logo'] = $logo_url;
            $schema['image'] = $logo_url;
        }

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
        if ( ( is_page() || is_single() ) && ! is_front_page() ) {
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
        if ( ! is_page() && ! is_single() ) {
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

        // Add featured image as og:image if available
        if ( has_post_thumbnail( $post_id ) ) {
            $thumb_url = get_the_post_thumbnail_url( $post_id, 'large' );
            if ( $thumb_url ) {
                echo '<meta property="og:image" content="' . esc_url( $thumb_url ) . '">' . "\n";
            }
        }

        // Twitter card tags
        echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
        echo '<meta name="twitter:title" content="' . esc_attr( $og_title ) . '">' . "\n";
        echo '<meta name="twitter:description" content="' . esc_attr( $og_desc ) . '">' . "\n";
        if ( has_post_thumbnail( $post_id ) ) {
            $thumb_url = get_the_post_thumbnail_url( $post_id, 'large' );
            if ( $thumb_url ) {
                echo '<meta name="twitter:image" content="' . esc_url( $thumb_url ) . '">' . "\n";
            }
        }
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
     * Override document title for imported pages and posts.
     */
    public function custom_page_title( $title ) {
        if ( ! is_page() && ! is_single() ) {
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
