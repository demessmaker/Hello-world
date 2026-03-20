<?php
/**
 * Plugin Name: WICM Developer Importer
 * Plugin URI: https://westislandmusicschool.com
 * Description: One-click content importer for West Island Music School V2. Creates pages, programs, testimonials, menus, contact form, and SEO setup.
 * Version: 2.0.0
 * Author: West Island Music School
 * License: GPL v2 or later
 * Text Domain: wicm-dev-importer
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WICM_Developer_Importer {

    private $plugin_dir;
    private $content_dir;

    public function __construct() {
        $this->plugin_dir  = plugin_dir_path( __FILE__ );
        $this->content_dir = $this->plugin_dir . 'content/';

        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_post_wicm_dev_import', array( $this, 'handle_import' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles' ) );

        // Register custom post types
        add_action( 'init', array( $this, 'register_post_types' ) );

        // Frontend SEO hooks
        if ( ! is_admin() ) {
            add_action( 'wp_head', array( $this, 'output_seo_meta' ), 1 );
            add_action( 'wp_head', array( $this, 'output_schema_markup' ), 2 );
            add_action( 'wp_head', array( $this, 'output_open_graph' ), 3 );
            add_action( 'wp_head', array( $this, 'output_canonical_url' ), 4 );
            add_filter( 'pre_get_document_title', array( $this, 'custom_page_title' ), 10 );
        }

        // Shortcodes
        add_shortcode( 'wicm_programs', array( $this, 'programs_shortcode' ) );
        add_shortcode( 'wicm_testimonials', array( $this, 'testimonials_shortcode' ) );

        // Disable wpautop on pages to prevent HTML mangling
        add_action( 'init', array( $this, 'disable_wpautop_pages' ) );
    }

    /**
     * Disable wpautop on pages so custom HTML sections are not mangled.
     */
    public function disable_wpautop_pages() {
        add_filter( 'the_content', array( $this, 'maybe_skip_wpautop' ), 0 );
    }

    public function maybe_skip_wpautop( $content ) {
        if ( is_page() ) {
            remove_filter( 'the_content', 'wpautop' );
        }
        return $content;
    }

    /**
     * Register Custom Post Types.
     */
    public function register_post_types() {
        register_post_type( 'wicm_program', array(
            'labels'       => array(
                'name'          => 'Programs',
                'singular_name' => 'Program',
            ),
            'public'       => true,
            'has_archive'  => true,
            'rewrite'      => array( 'slug' => 'program', 'with_front' => false ),
            'menu_icon'    => 'dashicons-format-audio',
            'supports'     => array( 'title', 'editor', 'thumbnail', 'custom-fields' ),
            'show_in_rest' => true,
        ) );

        register_post_type( 'wicm_testimonial', array(
            'labels'       => array(
                'name'          => 'Testimonials',
                'singular_name' => 'Testimonial',
            ),
            'public'       => true,
            'has_archive'  => false,
            'menu_icon'    => 'dashicons-format-quote',
            'supports'     => array( 'title', 'custom-fields' ),
            'show_in_rest' => true,
        ) );
    }

    /**
     * Admin menu.
     */
    public function add_admin_menu() {
        add_management_page(
            'WICM Developer Importer',
            'WICM V2 Importer',
            'manage_options',
            'wicm-dev-importer',
            array( $this, 'render_admin_page' )
        );
    }

    /**
     * Admin styles.
     */
    public function admin_styles( $hook ) {
        if ( 'tools_page_wicm-dev-importer' !== $hook ) {
            return;
        }
        wp_add_inline_style( 'wp-admin', '
            .wicm-wrap { max-width: 860px; }
            .wicm-wrap .card { padding: 24px; margin-bottom: 20px; border-left: 4px solid #dc2626; }
            .wicm-wrap .status-ok { color: #16a34a; }
            .wicm-wrap .status-error { color: #dc2626; }
            .wicm-results li { padding: 6px 0; }
            .wicm-results .dashicons { margin-right: 6px; }
        ' );
    }

    /**
     * Render admin page.
     */
    public function render_admin_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized' );
        }

        $imported = get_option( 'wicm_dev_import_done', false );
        ?>
        <div class="wrap wicm-wrap">
            <h1>West Island Music School - V2 Content Importer</h1>

            <?php if ( $imported ) : ?>
                <div class="notice notice-info"><p><strong>Content was already imported.</strong> Re-importing will update existing pages with the latest content.</p></div>
            <?php endif; ?>

            <?php $this->show_results(); ?>

            <div class="card">
                <h2>Import Options</h2>
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                    <?php wp_nonce_field( 'wicm_dev_import', 'wicm_nonce' ); ?>
                    <input type="hidden" name="action" value="wicm_dev_import">
                    <table class="form-table">
                        <tr><th>Site Settings</th><td><label><input type="checkbox" name="import_settings" value="1" checked> Title, tagline, timezone, permalinks</label></td></tr>
                        <tr><th>Pages</th><td><label><input type="checkbox" name="import_pages" value="1" checked> Home, About, Contact, Programs, Book a Trial</label></td></tr>
                        <tr><th>Programs</th><td><label><input type="checkbox" name="import_programs" value="1" checked> Piano, Guitar, Drums, Voice, Violin</label></td></tr>
                        <tr><th>Testimonials</th><td><label><input type="checkbox" name="import_testimonials" value="1" checked> 4 testimonials with ratings</label></td></tr>
                        <tr><th>Images</th><td><label><input type="checkbox" name="import_images" value="1" checked> Download Unsplash images for programs</label></td></tr>
                        <tr><th>Contact Form</th><td><label><input type="checkbox" name="import_cf7" value="1" checked> Create CF7 form + embed in Contact page</label></td></tr>
                        <tr><th>Menu</th><td><label><input type="checkbox" name="import_menu" value="1" checked> Primary navigation menu</label></td></tr>
                        <tr><th>Front Page</th><td><label><input type="checkbox" name="set_front_page" value="1" checked> Set Home as static front page</label></td></tr>
                        <tr><th>French Content</th><td><label><input type="checkbox" name="import_french" value="1" checked> French pages, programs, testimonials &amp; menu (Polylang)</label><?php if ( ! function_exists( 'pll_set_post_language' ) ) : ?><br><small style="color:#dc2626;">Polylang plugin not active — French content will be created but not linked as translations.</small><?php endif; ?></td></tr>
                    </table>
                    <?php submit_button( 'Import All Content', 'primary', 'submit', true ); ?>
                </form>
            </div>
        </div>
        <?php
    }

    /**
     * Show import results.
     */
    private function show_results() {
        $results = get_transient( 'wicm_dev_results' );
        if ( ! $results ) return;
        delete_transient( 'wicm_dev_results' );

        echo '<div class="wicm-results"><h2>Import Results</h2><ul>';
        foreach ( $results as $r ) {
            $icon  = $r['success'] ? 'yes-alt' : 'warning';
            $class = $r['success'] ? 'status-ok' : 'status-error';
            printf( '<li><span class="dashicons dashicons-%s %s"></span>%s</li>', esc_attr( $icon ), esc_attr( $class ), esc_html( $r['message'] ) );
        }
        echo '</ul></div>';
    }

    /**
     * Handle import.
     */
    public function handle_import() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized' );
        }
        check_admin_referer( 'wicm_dev_import', 'wicm_nonce' );

        $results  = array();
        $page_ids = array();

        if ( ! empty( $_POST['import_settings'] ) ) {
            $results = array_merge( $results, $this->import_settings() );
        }

        if ( ! empty( $_POST['import_pages'] ) ) {
            $pr       = $this->import_pages();
            $results  = array_merge( $results, $pr['results'] );
            $page_ids = $pr['page_ids'];
        }

        $program_ids = array();
        if ( ! empty( $_POST['import_programs'] ) ) {
            $pr          = $this->import_programs();
            $results     = array_merge( $results, $pr['results'] );
            $program_ids = $pr['ids'];
        }

        if ( ! empty( $_POST['import_testimonials'] ) ) {
            $results = array_merge( $results, $this->import_testimonials() );
        }

        if ( ! empty( $_POST['import_images'] ) ) {
            $results = array_merge( $results, $this->import_images( $program_ids ) );
        }

        if ( ! empty( $_POST['import_cf7'] ) ) {
            $results = array_merge( $results, $this->setup_contact_form( isset( $page_ids['contact'] ) ? $page_ids['contact'] : 0 ) );
        }

        if ( ! empty( $_POST['set_front_page'] ) && ! empty( $page_ids['home'] ) ) {
            update_option( 'show_on_front', 'page' );
            update_option( 'page_on_front', $page_ids['home'] );
            if ( ! empty( $page_ids['blog'] ) ) {
                update_option( 'page_for_posts', $page_ids['blog'] );
            }
            $results[] = array( 'success' => true, 'message' => 'Set Home as front page' );
        }

        if ( ! empty( $_POST['import_menu'] ) ) {
            $results = array_merge( $results, $this->import_menu( $page_ids ) );
        }

        // French content (Polylang)
        if ( ! empty( $_POST['import_french'] ) ) {
            $fr = $this->import_french_content( $page_ids, $program_ids );
            $results = array_merge( $results, $fr );
        }

        update_option( 'wicm_dev_import_done', true );
        set_transient( 'wicm_dev_results', $results, 120 );
        wp_safe_redirect( admin_url( 'tools.php?page=wicm-dev-importer' ) );
        exit;
    }

    // =========================================================================
    // IMPORT METHODS
    // =========================================================================

    private function import_settings() {
        $s = $this->load_json( 'site-settings.json' );
        if ( ! $s ) return array( array( 'success' => false, 'message' => 'Could not load site-settings.json' ) );

        update_option( 'blogname', $s['site_title'] );
        update_option( 'blogdescription', $s['tagline'] );
        update_option( 'timezone_string', $s['timezone'] );
        update_option( 'date_format', 'F j, Y' );
        update_option( 'default_comment_status', 'closed' );

        global $wp_rewrite;
        $wp_rewrite->set_permalink_structure( '/%postname%/' );
        $wp_rewrite->flush_rules();

        return array( array( 'success' => true, 'message' => 'Site settings imported' ) );
    }

    private function import_pages() {
        $results  = array();
        $page_ids = array();
        $pages    = array(
            'home'    => 'pages/home.json',
            'about'   => 'pages/about.json',
            'contact' => 'pages/contact.json',
            'programs'=> 'pages/programs.json',
            'trial'   => 'pages/book-trial.json',
        );

        foreach ( $pages as $key => $file ) {
            $data = $this->load_json( $file );
            if ( ! $data ) {
                $results[] = array( 'success' => false, 'message' => "Could not load {$file}" );
                continue;
            }

            $content = isset( $data['html_content'] ) ? $data['html_content'] : '';

            // Convert HTML into individual Gutenberg blocks for editor compatibility
            if ( ! empty( $content ) ) {
                $content = $this->html_to_gutenberg_blocks( $content );
            }

            // Check if page already exists by slug — update it instead of creating a duplicate
            $existing = get_page_by_path( $data['slug'], OBJECT, 'page' );
            if ( $existing ) {
                $page_id = wp_update_post( array(
                    'ID'             => $existing->ID,
                    'post_title'     => $data['title'],
                    'post_content'   => $content,
                    'post_status'    => 'publish',
                    'comment_status' => 'closed',
                ) );
                $action = 'Updated';
            } else {
                $page_id = wp_insert_post( array(
                    'post_title'     => $data['title'],
                    'post_name'      => $data['slug'],
                    'post_content'   => $content,
                    'post_status'    => 'publish',
                    'post_type'      => 'page',
                    'comment_status' => 'closed',
                ) );
                $action = 'Created';
            }

            if ( ! is_wp_error( $page_id ) ) {
                $page_ids[ $key ] = $page_id;
                if ( ! empty( $data['seo'] ) ) {
                    foreach ( $data['seo'] as $meta_key => $meta_val ) {
                        update_post_meta( $page_id, '_wicm_' . $meta_key, sanitize_text_field( $meta_val ) );
                    }
                }
                // Yoast SEO meta fields
                if ( ! empty( $data['yoast_seo'] ) ) {
                    $yoast_fields = array(
                        'focuskw'               => '_yoast_wpseo_focuskw',
                        'title'                 => '_yoast_wpseo_title',
                        'metadesc'              => '_yoast_wpseo_metadesc',
                        'opengraph-title'       => '_yoast_wpseo_opengraph-title',
                        'opengraph-description' => '_yoast_wpseo_opengraph-description',
                    );
                    foreach ( $yoast_fields as $json_key => $meta_key ) {
                        if ( ! empty( $data['yoast_seo'][ $json_key ] ) ) {
                            update_post_meta( $page_id, $meta_key, sanitize_text_field( $data['yoast_seo'][ $json_key ] ) );
                        }
                    }
                }
                $results[] = array( 'success' => true, 'message' => "{$action} page: {$data['title']}" );
            } else {
                $results[] = array( 'success' => false, 'message' => "Failed: {$data['title']}" );
            }
        }

        // Create a Blog page
        $blog_id = wp_insert_post( array(
            'post_title'     => 'Blog',
            'post_name'      => 'blog',
            'post_content'   => '',
            'post_status'    => 'publish',
            'post_type'      => 'page',
            'comment_status' => 'closed',
        ) );
        if ( ! is_wp_error( $blog_id ) ) {
            $page_ids['blog'] = $blog_id;
        }

        return array( 'results' => $results, 'page_ids' => $page_ids );
    }

    private function import_programs() {
        $results = array();
        $ids     = array();

        $programs = array(
            array(
                'name'     => 'Piano Lessons',
                'slug'     => 'piano-lessons',
                'icon'     => '🎹',
                'desc'     => 'From classical to contemporary, learn piano at your own pace with personalized instruction for all skill levels.',
                'features' => array( 'Classical & Contemporary', 'Music Theory', 'Sight Reading', 'Performance Skills' ),
                'image_url'=> 'https://images.unsplash.com/photo-1552422535-c45813c61732?w=800&h=500&fit=crop',
            ),
            array(
                'name'     => 'Guitar Lessons',
                'slug'     => 'guitar-lessons',
                'icon'     => '🎸',
                'desc'     => 'Electric, acoustic, or classical - master the guitar with customized lessons tailored to your musical goals.',
                'features' => array( 'Electric & Acoustic', 'Chord Progressions', 'Fingerpicking', 'Song Writing' ),
                'image_url'=> 'https://images.unsplash.com/photo-1510915361894-db8b60106cb1?w=800&h=500&fit=crop',
            ),
            array(
                'name'     => 'Drum Lessons',
                'slug'     => 'drum-lessons',
                'icon'     => '🥁',
                'desc'     => 'Build rhythm, coordination, and technique with dynamic drum lessons that get you playing your favorite songs.',
                'features' => array( 'Rhythm Fundamentals', 'Hand Coordination', 'Multiple Styles', 'Band Integration' ),
                'image_url'=> 'https://images.unsplash.com/photo-1519892300165-cb5542fb47c7?w=800&h=500&fit=crop',
            ),
            array(
                'name'     => 'Voice Lessons',
                'slug'     => 'voice-lessons',
                'icon'     => '🎤',
                'desc'     => 'Unlock your vocal potential with training in breath control, range expansion, and performance techniques.',
                'features' => array( 'Breath Control', 'Range Expansion', 'Performance Coaching', 'Multiple Genres' ),
                'image_url'=> 'https://images.unsplash.com/photo-1516280440614-37939bbacd81?w=800&h=500&fit=crop',
            ),
            array(
                'name'     => 'Violin Lessons',
                'slug'     => 'violin-lessons',
                'icon'     => '🎻',
                'desc'     => 'Embrace the elegance of string music with patient, step-by-step violin instruction for beginners to advanced.',
                'features' => array( 'Classical Training', 'Proper Technique', 'Orchestra Prep', 'Solo Performance' ),
                'image_url'=> 'https://images.unsplash.com/photo-1612225330812-01a9c6b355ec?w=800&h=500&fit=crop',
            ),
        );

        foreach ( $programs as $prog ) {
            $post_id = wp_insert_post( array(
                'post_title'   => $prog['name'],
                'post_name'    => $prog['slug'],
                'post_content' => '<p>' . $prog['desc'] . '</p>',
                'post_status'  => 'publish',
                'post_type'    => 'wicm_program',
            ) );

            if ( ! is_wp_error( $post_id ) ) {
                update_post_meta( $post_id, '_wicm_icon', $prog['icon'] );
                update_post_meta( $post_id, '_wicm_features', $prog['features'] );
                update_post_meta( $post_id, '_wicm_image_url', $prog['image_url'] );
                $ids[ $prog['slug'] ] = $post_id;
                $results[] = array( 'success' => true, 'message' => "Created program: {$prog['name']}" );
            }
        }

        return array( 'results' => $results, 'ids' => $ids );
    }

    private function import_testimonials() {
        $results = array();
        $testimonials = array(
            array( 'name' => 'Amanda Walsh', 'role' => 'Voice Student', 'quote' => 'The warm environment and supportive teachers helped me build confidence I never knew I had. My vocal range has expanded tremendously!', 'rating' => 5 ),
            array( 'name' => 'Donna Burgess', 'role' => 'Parent', 'quote' => "Top notch instruction quality. We've been customers for 15 years and both my children have flourished under their guidance.", 'rating' => 5 ),
            array( 'name' => 'Damien Holtz', 'role' => 'Parent', 'quote' => 'The skills and lasting joy my daughter has developed through her piano lessons here are priceless. Highly recommended!', 'rating' => 5 ),
            array( 'name' => 'John McGuinness', 'role' => 'Parent', 'quote' => 'My son was selected for the Montreal Jazz Festival Blues Camp thanks to the exceptional training he received here.', 'rating' => 5 ),
        );

        $count = 0;
        foreach ( $testimonials as $t ) {
            $post_id = wp_insert_post( array(
                'post_title'  => $t['name'],
                'post_status' => 'publish',
                'post_type'   => 'wicm_testimonial',
            ) );
            if ( ! is_wp_error( $post_id ) ) {
                update_post_meta( $post_id, '_wicm_quote', $t['quote'] );
                update_post_meta( $post_id, '_wicm_role', $t['role'] );
                update_post_meta( $post_id, '_wicm_rating', $t['rating'] );
                $count++;
            }
        }

        $results[] = array( 'success' => $count > 0, 'message' => "Created {$count} testimonials" );
        return $results;
    }

    private function import_images( $program_ids ) {
        $results = array();

        if ( ! function_exists( 'media_handle_sideload' ) ) {
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }

        $images = array(
            'piano-lessons'  => array( 'url' => 'https://images.unsplash.com/photo-1552422535-c45813c61732?w=800&h=500&fit=crop', 'alt' => 'Piano lessons at West Island Music School' ),
            'guitar-lessons' => array( 'url' => 'https://images.unsplash.com/photo-1510915361894-db8b60106cb1?w=800&h=500&fit=crop', 'alt' => 'Guitar lessons at West Island Music School' ),
            'drum-lessons'   => array( 'url' => 'https://images.unsplash.com/photo-1519892300165-cb5542fb47c7?w=800&h=500&fit=crop', 'alt' => 'Drum lessons at West Island Music School' ),
            'voice-lessons'  => array( 'url' => 'https://images.unsplash.com/photo-1516280440614-37939bbacd81?w=800&h=500&fit=crop', 'alt' => 'Voice lessons at West Island Music School' ),
            'violin-lessons' => array( 'url' => 'https://images.unsplash.com/photo-1612225330812-01a9c6b355ec?w=800&h=500&fit=crop', 'alt' => 'Violin lessons at West Island Music School' ),
        );

        $count = 0;
        foreach ( $images as $slug => $img ) {
            $post_id = isset( $program_ids[ $slug ] ) ? $program_ids[ $slug ] : 0;

            $tmp = download_url( $img['url'] );
            if ( is_wp_error( $tmp ) ) continue;

            $file_array = array(
                'name'     => sanitize_file_name( $slug . '.jpg' ),
                'tmp_name' => $tmp,
            );

            $attachment_id = media_handle_sideload( $file_array, $post_id );
            if ( is_wp_error( $attachment_id ) ) {
                @unlink( $tmp );
                continue;
            }

            update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( $img['alt'] ) );

            if ( $post_id ) {
                set_post_thumbnail( $post_id, $attachment_id );
            }

            $count++;
        }

        $results[] = array( 'success' => $count > 0, 'message' => "Imported {$count} program images" );
        return $results;
    }

    private function setup_contact_form( $contact_page_id ) {
        $results = array();

        if ( ! class_exists( 'WPCF7_ContactForm' ) ) {
            $results[] = array( 'success' => false, 'message' => 'Contact Form 7 not active - install it for the contact form' );
            return $results;
        }

        $form_template = '<div class="wicm-contact-form">
<label>Your Name *
    [text* your-name]</label>

<label>Email *
    [email* your-email]</label>

<label>Phone *
    [tel* your-phone]</label>

<label>Instrument of Interest
    [select instrument "Piano" "Guitar" "Voice" "Drums" "Violin" "Other"]</label>

<label>Message
    [textarea your-message]</label>

[submit "Send Message"]
</div>';

        $form = WPCF7_ContactForm::get_template();
        $form->set_properties( array(
            'form' => $form_template,
            'mail' => array_merge( $form->prop( 'mail' ), array(
                'subject'   => 'WICM Inquiry - [instrument]',
                'body'      => "Name: [your-name]\nEmail: [your-email]\nPhone: [your-phone]\nInstrument: [instrument]\n\n[your-message]",
                'recipient' => 'info@westislandmusicschool.com',
            ) ),
        ) );
        $form->set_title( 'WICM Contact Form' );
        $form->save();

        $results[] = array( 'success' => true, 'message' => "Created Contact Form 7 (ID: {$form->id()})" );

        if ( $contact_page_id ) {
            $page = get_post( $contact_page_id );
            if ( $page ) {
                wp_update_post( array(
                    'ID'           => $contact_page_id,
                    'post_content' => $page->post_content . "\n\n" . '[contact-form-7 id="' . $form->id() . '" title="WICM Contact Form"]',
                ) );
                $results[] = array( 'success' => true, 'message' => 'Embedded contact form in Contact page' );
            }
        }

        return $results;
    }

    private function import_menu( $page_ids ) {
        $results = array();

        $existing = wp_get_nav_menu_object( 'Primary Menu' );
        if ( $existing ) {
            wp_delete_nav_menu( $existing->term_id );
        }

        $menu_id = wp_create_nav_menu( 'Primary Menu' );
        if ( is_wp_error( $menu_id ) ) {
            return array( array( 'success' => false, 'message' => 'Failed to create menu' ) );
        }

        $items = array(
            array( 'key' => 'home', 'title' => 'Home', 'order' => 1 ),
            array( 'key' => 'programs', 'title' => 'Programs', 'order' => 2 ),
            array( 'key' => 'about', 'title' => 'About Us', 'order' => 3 ),
            array( 'key' => 'contact', 'title' => 'Contact', 'order' => 5 ),
        );

        $added = 0;
        foreach ( $items as $item ) {
            if ( empty( $page_ids[ $item['key'] ] ) ) continue;
            $r = wp_update_nav_menu_item( $menu_id, 0, array(
                'menu-item-title'     => $item['title'],
                'menu-item-object'    => 'page',
                'menu-item-object-id' => $page_ids[ $item['key'] ],
                'menu-item-type'      => 'post_type',
                'menu-item-status'    => 'publish',
                'menu-item-position'  => $item['order'],
            ) );
            if ( ! is_wp_error( $r ) ) $added++;
        }

        // Assign to theme location
        $locations = get_theme_mod( 'nav_menu_locations', array() );
        $locations['primary'] = $menu_id;
        set_theme_mod( 'nav_menu_locations', $locations );

        $results[] = array( 'success' => true, 'message' => "Created menu with {$added} items (assigned to primary)" );
        return $results;
    }

    // =========================================================================
    // FRENCH CONTENT (POLYLANG)
    // =========================================================================

    /**
     * Import all French content and link translations via Polylang.
     */
    private function import_french_content( $en_page_ids, $en_program_ids ) {
        $results      = array();
        $has_polylang = function_exists( 'pll_set_post_language' );

        // Set English language on existing EN posts
        if ( $has_polylang ) {
            foreach ( $en_page_ids as $en_id ) {
                pll_set_post_language( $en_id, 'en' );
            }
            foreach ( $en_program_ids as $en_id ) {
                pll_set_post_language( $en_id, 'en' );
            }
            // Set EN on testimonials
            $en_testimonials = get_posts( array( 'post_type' => 'wicm_testimonial', 'numberposts' => 20, 'fields' => 'ids' ) );
            foreach ( $en_testimonials as $t_id ) {
                pll_set_post_language( $t_id, 'en' );
            }
        }

        // --- French Pages ---
        $fr_page_ids = array();
        $fr_pages    = array(
            'home'    => 'pages/fr/home.json',
            'about'   => 'pages/fr/about.json',
            'contact' => 'pages/fr/contact.json',
            'programs'=> 'pages/fr/programs.json',
            'trial'   => 'pages/fr/book-trial.json',
        );

        foreach ( $fr_pages as $key => $file ) {
            $data = $this->load_json( $file );
            if ( ! $data ) {
                $results[] = array( 'success' => false, 'message' => "Could not load {$file}" );
                continue;
            }

            $content = isset( $data['html_content'] ) ? $data['html_content'] : '';
            if ( ! empty( $content ) ) {
                $content = $this->html_to_gutenberg_blocks( $content );
            }

            $existing = get_page_by_path( $data['slug'], OBJECT, 'page' );
            if ( $existing ) {
                $page_id = wp_update_post( array(
                    'ID'             => $existing->ID,
                    'post_title'     => $data['title'],
                    'post_content'   => $content,
                    'post_status'    => 'publish',
                    'comment_status' => 'closed',
                ) );
                $action = 'Updated';
            } else {
                $page_id = wp_insert_post( array(
                    'post_title'     => $data['title'],
                    'post_name'      => $data['slug'],
                    'post_content'   => $content,
                    'post_status'    => 'publish',
                    'post_type'      => 'page',
                    'comment_status' => 'closed',
                ) );
                $action = 'Created';
            }

            if ( ! is_wp_error( $page_id ) ) {
                $fr_page_ids[ $key ] = $page_id;

                // SEO meta
                if ( ! empty( $data['seo'] ) ) {
                    foreach ( $data['seo'] as $meta_key => $meta_val ) {
                        update_post_meta( $page_id, '_wicm_' . $meta_key, sanitize_text_field( $meta_val ) );
                    }
                }
                // Yoast SEO
                if ( ! empty( $data['yoast_seo'] ) ) {
                    $yoast_fields = array(
                        'focuskw'               => '_yoast_wpseo_focuskw',
                        'title'                 => '_yoast_wpseo_title',
                        'metadesc'              => '_yoast_wpseo_metadesc',
                        'opengraph-title'       => '_yoast_wpseo_opengraph-title',
                        'opengraph-description' => '_yoast_wpseo_opengraph-description',
                    );
                    foreach ( $yoast_fields as $json_key => $meta_key ) {
                        if ( ! empty( $data['yoast_seo'][ $json_key ] ) ) {
                            update_post_meta( $page_id, $meta_key, sanitize_text_field( $data['yoast_seo'][ $json_key ] ) );
                        }
                    }
                }

                // Polylang: set language and link translation
                if ( $has_polylang ) {
                    pll_set_post_language( $page_id, 'fr' );
                    if ( ! empty( $en_page_ids[ $key ] ) ) {
                        pll_save_post_translations( array(
                            'en' => $en_page_ids[ $key ],
                            'fr' => $page_id,
                        ) );
                    }
                }

                $results[] = array( 'success' => true, 'message' => "{$action} FR page: {$data['title']}" );
            } else {
                $results[] = array( 'success' => false, 'message' => "Failed FR: {$data['title']}" );
            }
        }

        // FR Blog page
        $fr_blog_id = wp_insert_post( array(
            'post_title'     => 'Blogue',
            'post_name'      => 'blogue',
            'post_content'   => '',
            'post_status'    => 'publish',
            'post_type'      => 'page',
            'comment_status' => 'closed',
        ) );
        if ( ! is_wp_error( $fr_blog_id ) ) {
            $fr_page_ids['blog'] = $fr_blog_id;
            if ( $has_polylang ) {
                pll_set_post_language( $fr_blog_id, 'fr' );
                if ( ! empty( $en_page_ids['blog'] ) ) {
                    pll_save_post_translations( array(
                        'en' => $en_page_ids['blog'],
                        'fr' => $fr_blog_id,
                    ) );
                }
            }
        }

        // --- French Programs ---
        $fr_program_ids = array();
        $fr_programs = array(
            array(
                'name'     => 'Cours de piano',
                'slug'     => 'cours-de-piano',
                'en_slug'  => 'piano-lessons',
                'icon'     => "\xF0\x9F\x8E\xB9",
                'desc'     => 'Du classique au contemporain, apprenez le piano à votre rythme avec un enseignement personnalisé pour tous les niveaux.',
                'features' => array( 'Classique et contemporain', 'Théorie musicale', 'Lecture à vue', 'Compétences de performance' ),
            ),
            array(
                'name'     => 'Cours de guitare',
                'slug'     => 'cours-de-guitare',
                'en_slug'  => 'guitar-lessons',
                'icon'     => "\xF0\x9F\x8E\xB8",
                'desc'     => 'Électrique, acoustique ou classique — maîtrisez la guitare avec des cours personnalisés adaptés à vos objectifs musicaux.',
                'features' => array( 'Électrique et acoustique', 'Progressions d\'accords', 'Fingerpicking', 'Composition' ),
            ),
            array(
                'name'     => 'Cours de batterie',
                'slug'     => 'cours-de-batterie',
                'en_slug'  => 'drum-lessons',
                'icon'     => "\xF0\x9F\xA5\x81",
                'desc'     => 'Développez le rythme, la coordination et la technique avec des cours de batterie dynamiques qui vous font jouer vos chansons préférées.',
                'features' => array( 'Fondamentaux du rythme', 'Coordination des mains', 'Styles multiples', 'Intégration en groupe' ),
            ),
            array(
                'name'     => 'Cours de chant',
                'slug'     => 'cours-de-chant',
                'en_slug'  => 'voice-lessons',
                'icon'     => "\xF0\x9F\x8E\xA4",
                'desc'     => 'Libérez votre potentiel vocal avec une formation en contrôle de la respiration, expansion de la tessiture et techniques de performance.',
                'features' => array( 'Contrôle de la respiration', 'Expansion de la tessiture', 'Coaching de performance', 'Genres multiples' ),
            ),
            array(
                'name'     => 'Cours de violon',
                'slug'     => 'cours-de-violon',
                'en_slug'  => 'violin-lessons',
                'icon'     => "\xF0\x9F\x8E\xBB",
                'desc'     => 'Embrassez l\'élégance de la musique à cordes avec un enseignement patient et progressif du violon, pour débutants à avancés.',
                'features' => array( 'Formation classique', 'Technique appropriée', 'Préparation orchestrale', 'Performance solo' ),
            ),
        );

        foreach ( $fr_programs as $prog ) {
            $post_id = wp_insert_post( array(
                'post_title'   => $prog['name'],
                'post_name'    => $prog['slug'],
                'post_content' => '<p>' . $prog['desc'] . '</p>',
                'post_status'  => 'publish',
                'post_type'    => 'wicm_program',
            ) );

            if ( ! is_wp_error( $post_id ) ) {
                update_post_meta( $post_id, '_wicm_icon', $prog['icon'] );
                update_post_meta( $post_id, '_wicm_features', $prog['features'] );
                $fr_program_ids[ $prog['en_slug'] ] = $post_id;

                if ( $has_polylang ) {
                    pll_set_post_language( $post_id, 'fr' );
                    if ( ! empty( $en_program_ids[ $prog['en_slug'] ] ) ) {
                        pll_save_post_translations( array(
                            'en' => $en_program_ids[ $prog['en_slug'] ],
                            'fr' => $post_id,
                        ) );
                    }
                }

                // Share the same featured image as the EN version
                if ( ! empty( $en_program_ids[ $prog['en_slug'] ] ) ) {
                    $thumb_id = get_post_thumbnail_id( $en_program_ids[ $prog['en_slug'] ] );
                    if ( $thumb_id ) {
                        set_post_thumbnail( $post_id, $thumb_id );
                    }
                }

                $results[] = array( 'success' => true, 'message' => "Created FR program: {$prog['name']}" );
            }
        }

        // --- French Testimonials ---
        $fr_testimonials = array(
            array( 'name' => 'Amanda Walsh', 'role' => 'Étudiante en chant', 'quote' => "L'environnement chaleureux et les professeurs bienveillants m'ont aidée à développer une confiance que je ne savais pas avoir. Ma tessiture vocale s'est énormément élargie!" ),
            array( 'name' => 'Donna Burgess', 'role' => 'Parent', 'quote' => "Qualité d'enseignement de premier ordre. Nous sommes clients depuis 15 ans et mes deux enfants ont épanoui sous leur guidance." ),
            array( 'name' => 'Damien Holtz', 'role' => 'Parent', 'quote' => "Les compétences et la joie durable que ma fille a développées grâce à ses cours de piano ici sont inestimables. Hautement recommandé!" ),
            array( 'name' => 'John McGuinness', 'role' => 'Parent', 'quote' => "Mon fils a été sélectionné pour le Blues Camp du Festival de Jazz de Montréal grâce à la formation exceptionnelle qu'il a reçue ici." ),
        );

        $en_testimonials = get_posts( array(
            'post_type'   => 'wicm_testimonial',
            'numberposts' => 20,
            'orderby'     => 'date',
            'order'       => 'ASC',
        ) );
        // Map EN testimonials by name for linking
        $en_test_map = array();
        foreach ( $en_testimonials as $et ) {
            $en_test_map[ $et->post_title ] = $et->ID;
        }

        $fr_test_count = 0;
        foreach ( $fr_testimonials as $t ) {
            $post_id = wp_insert_post( array(
                'post_title'  => $t['name'],
                'post_status' => 'publish',
                'post_type'   => 'wicm_testimonial',
            ) );
            if ( ! is_wp_error( $post_id ) ) {
                update_post_meta( $post_id, '_wicm_quote', $t['quote'] );
                update_post_meta( $post_id, '_wicm_role', $t['role'] );
                update_post_meta( $post_id, '_wicm_rating', 5 );
                $fr_test_count++;

                if ( $has_polylang ) {
                    pll_set_post_language( $post_id, 'fr' );
                    if ( ! empty( $en_test_map[ $t['name'] ] ) ) {
                        pll_save_post_translations( array(
                            'en' => $en_test_map[ $t['name'] ],
                            'fr' => $post_id,
                        ) );
                    }
                }
            }
        }
        $results[] = array( 'success' => $fr_test_count > 0, 'message' => "Created {$fr_test_count} FR testimonials" );

        // --- French Navigation Menu ---
        $existing_fr_menu = wp_get_nav_menu_object( 'Menu principal' );
        if ( $existing_fr_menu ) {
            wp_delete_nav_menu( $existing_fr_menu->term_id );
        }

        $fr_menu_id = wp_create_nav_menu( 'Menu principal' );
        if ( ! is_wp_error( $fr_menu_id ) ) {
            $fr_menu_items = array(
                array( 'key' => 'home', 'title' => 'Accueil', 'order' => 1 ),
                array( 'key' => 'programs', 'title' => 'Programmes', 'order' => 2 ),
                array( 'key' => 'about', 'title' => 'À propos', 'order' => 3 ),
                array( 'key' => 'contact', 'title' => 'Contact', 'order' => 5 ),
            );

            $fr_added = 0;
            foreach ( $fr_menu_items as $item ) {
                if ( empty( $fr_page_ids[ $item['key'] ] ) ) continue;
                $r = wp_update_nav_menu_item( $fr_menu_id, 0, array(
                    'menu-item-title'     => $item['title'],
                    'menu-item-object'    => 'page',
                    'menu-item-object-id' => $fr_page_ids[ $item['key'] ],
                    'menu-item-type'      => 'post_type',
                    'menu-item-status'    => 'publish',
                    'menu-item-position'  => $item['order'],
                ) );
                if ( ! is_wp_error( $r ) ) $fr_added++;
            }

            // Polylang: link FR menu to the FR language
            if ( $has_polylang && function_exists( 'pll_set_term_language' ) ) {
                pll_set_term_language( $fr_menu_id, 'fr' );
                // Also set EN menu language
                $en_menu = wp_get_nav_menu_object( 'Primary Menu' );
                if ( $en_menu ) {
                    pll_set_term_language( $en_menu->term_id, 'en' );
                }
            }

            $results[] = array( 'success' => true, 'message' => "Created FR menu 'Menu principal' with {$fr_added} items" );
        }

        if ( $has_polylang ) {
            $results[] = array( 'success' => true, 'message' => 'Polylang: All EN/FR translations linked' );
        } else {
            $results[] = array( 'success' => true, 'message' => 'French content created (activate Polylang to link translations)' );
        }

        return $results;
    }

    // =========================================================================
    // SHORTCODES
    // =========================================================================

    public function programs_shortcode() {
        $programs = get_posts( array( 'post_type' => 'wicm_program', 'numberposts' => 10, 'orderby' => 'date', 'order' => 'ASC', 'suppress_filters' => false ) );
        if ( empty( $programs ) ) return '';

        $html = '<div class="programs-grid">';
        foreach ( $programs as $prog ) {
            $icon     = get_post_meta( $prog->ID, '_wicm_icon', true );
            $features = get_post_meta( $prog->ID, '_wicm_features', true );
            $thumb    = get_the_post_thumbnail_url( $prog->ID, 'program-card' );

            $html .= '<div class="program-card">';
            if ( $thumb ) {
                $html .= '<img src="' . esc_url( $thumb ) . '" alt="' . esc_attr( $prog->post_title ) . '" class="program-card-image" loading="lazy">';
            }
            $html .= '<div class="program-card-body">';
            $html .= '<div class="program-card-icon">' . esc_html( $icon ) . '</div>';
            $html .= '<h3>' . esc_html( $prog->post_title ) . '</h3>';
            $html .= '<p>' . esc_html( wp_strip_all_tags( $prog->post_content ) ) . '</p>';
            if ( is_array( $features ) ) {
                $html .= '<ul class="program-features">';
                foreach ( $features as $f ) {
                    $html .= '<li><svg class="check-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>' . esc_html( $f ) . '</li>';
                }
                $html .= '</ul>';
            }
            $learn_more = ( function_exists( 'pll_current_language' ) && pll_current_language() === 'fr' ) ? 'En savoir plus' : 'Learn More';
            $html .= '<a href="' . esc_url( get_permalink( $prog->ID ) ) . '" class="btn btn-outline-dark btn-sm">' . esc_html( $learn_more ) . '</a>';
            $html .= '</div></div>';
        }
        $html .= '</div>';
        return $html;
    }

    public function testimonials_shortcode() {
        $items = get_posts( array( 'post_type' => 'wicm_testimonial', 'numberposts' => 10, 'suppress_filters' => false ) );
        if ( empty( $items ) ) return '';

        $html = '<div class="testimonials-grid">';
        foreach ( $items as $item ) {
            $quote  = get_post_meta( $item->ID, '_wicm_quote', true );
            $role   = get_post_meta( $item->ID, '_wicm_role', true );
            $rating = (int) get_post_meta( $item->ID, '_wicm_rating', true );

            $html .= '<div class="testimonial-card">';
            $html .= '<div class="testimonial-stars">';
            for ( $i = 0; $i < $rating; $i++ ) {
                $html .= '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>';
            }
            $html .= '</div>';
            $html .= '<p class="testimonial-quote">&ldquo;' . esc_html( $quote ) . '&rdquo;</p>';
            $html .= '<div class="testimonial-author">' . esc_html( $item->post_title ) . '</div>';
            $html .= '<div class="testimonial-role">' . esc_html( $role ) . '</div>';
            $html .= '</div>';
        }
        $html .= '</div>';
        return $html;
    }

    // =========================================================================
    // SEO OUTPUT
    // =========================================================================

    public function output_seo_meta() {
        if ( ! is_page() && ! is_single() ) return;
        $desc = get_post_meta( get_the_ID(), '_wicm_meta_description', true );
        if ( $desc ) {
            echo '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
        }
        echo '<meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large">' . "\n";
    }

    public function output_schema_markup() {
        $s = $this->load_json( 'site-settings.json' );
        if ( ! $s ) return;

        $schema = array(
            '@context'    => 'https://schema.org',
            '@type'       => 'MusicSchool',
            'name'        => $s['site_title'],
            'description' => 'Professional music lessons in West Island, Montreal. Piano, guitar, voice, drums and violin for all ages and skill levels.',
            'url'         => home_url( '/' ),
            'email'       => $s['contact']['email'],
            'address'     => array(
                '@type'           => 'PostalAddress',
                'addressLocality' => $s['contact']['address']['city'],
                'addressRegion'   => $s['contact']['address']['province'],
                'addressCountry'  => $s['contact']['address']['country'],
            ),
            'geo' => array( '@type' => 'GeoCoordinates', 'latitude' => 45.4469, 'longitude' => -73.8167 ),
            'openingHoursSpecification' => array(
                array( '@type' => 'OpeningHoursSpecification', 'dayOfWeek' => array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday' ), 'opens' => '09:00', 'closes' => '21:00' ),
                array( '@type' => 'OpeningHoursSpecification', 'dayOfWeek' => 'Saturday', 'opens' => '09:00', 'closes' => '17:00' ),
            ),
            'areaServed'      => array_map( function( $a ) { return array( '@type' => 'City', 'name' => $a ); }, $s['service_areas'] ),
            'paymentAccepted' => implode( ', ', $s['payment_methods'] ),
            'priceRange'      => '$$',
            'sameAs'          => array( $s['social_media']['facebook'], $s['social_media']['instagram'] ),
        );

        echo '<script type="application/ld+json">' . "\n" . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . "\n</script>\n";
    }

    public function output_open_graph() {
        if ( ! is_page() && ! is_single() ) return;
        $title = get_post_meta( get_the_ID(), '_wicm_meta_title', true );
        $desc  = get_post_meta( get_the_ID(), '_wicm_meta_description', true );
        if ( ! $title ) $title = get_the_title() . ' | ' . get_bloginfo( 'name' );
        echo '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
        if ( $desc ) echo '<meta property="og:description" content="' . esc_attr( $desc ) . '">' . "\n";
        echo '<meta property="og:url" content="' . esc_url( get_permalink() ) . '">' . "\n";
        echo '<meta property="og:site_name" content="' . esc_attr( get_bloginfo( 'name' ) ) . '">' . "\n";
        echo '<meta property="og:locale" content="en_CA">' . "\n";
        if ( has_post_thumbnail() ) {
            echo '<meta property="og:image" content="' . esc_url( get_the_post_thumbnail_url( get_the_ID(), 'large' ) ) . '">' . "\n";
        }
    }

    public function output_canonical_url() {
        if ( is_page() || is_single() ) {
            echo '<link rel="canonical" href="' . esc_url( get_permalink() ) . '">' . "\n";
        }
    }

    public function custom_page_title( $title ) {
        if ( ! is_page() && ! is_single() ) return $title;
        $meta = get_post_meta( get_the_ID(), '_wicm_meta_title', true );
        return $meta ? $meta : $title;
    }

    // =========================================================================
    // GUTENBERG BLOCK CONVERTER
    // =========================================================================

    /**
     * Convert raw HTML into individual Gutenberg blocks.
     *
     * Uses DOMDocument to split top-level elements into separate blocks.
     * <section> and wrapper <div> elements become wp:html blocks.
     * Simple elements (headings, paragraphs) become native Gutenberg blocks.
     * Shortcodes become wp:shortcode blocks.
     */
    private function html_to_gutenberg_blocks( $html ) {
        $html = trim( $html );
        if ( empty( $html ) ) {
            return '';
        }

        // Use DOMDocument to reliably parse top-level elements
        $doc = new DOMDocument();
        libxml_use_internal_errors( true );
        $doc->loadHTML(
            '<html><body>' . mb_convert_encoding( $html, 'HTML-ENTITIES', 'UTF-8' ) . '</body></html>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();

        $body   = $doc->getElementsByTagName( 'body' )->item( 0 );
        $blocks = array();

        if ( ! $body ) {
            // Fallback: wrap entire content in a single HTML block
            return "<!-- wp:html -->\n" . $html . "\n<!-- /wp:html -->";
        }

        foreach ( $body->childNodes as $node ) {
            if ( $node->nodeType === XML_TEXT_NODE ) {
                $text = trim( $node->textContent );
                if ( empty( $text ) ) {
                    continue;
                }
                // Check for shortcodes in text nodes
                if ( preg_match( '/\[[\w_-]+/', $text ) ) {
                    // Split shortcodes out
                    $parts = preg_split( '/(\[[^\]]+\])/', $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );
                    foreach ( $parts as $p ) {
                        $p = trim( $p );
                        if ( empty( $p ) ) continue;
                        if ( preg_match( '/^\[/', $p ) ) {
                            $blocks[] = "<!-- wp:shortcode -->\n" . $p . "\n<!-- /wp:shortcode -->";
                        } else {
                            $blocks[] = "<!-- wp:paragraph -->\n<p>" . $p . "</p>\n<!-- /wp:paragraph -->";
                        }
                    }
                    continue;
                }
                $blocks[] = "<!-- wp:paragraph -->\n<p>" . $text . "</p>\n<!-- /wp:paragraph -->";
                continue;
            }

            if ( $node->nodeType !== XML_ELEMENT_NODE ) {
                continue;
            }

            $tag       = strtolower( $node->nodeName );
            $inner_html = $this->dom_inner_html( $doc, $node );
            $outer_html = $doc->saveHTML( $node );

            // Sections: each becomes its own wp:html block
            if ( $tag === 'section' ) {
                $blocks[] = "<!-- wp:html -->\n" . $outer_html . "\n<!-- /wp:html -->";
                continue;
            }

            // page-content wrapper div — extract children as individual blocks
            if ( $tag === 'div' && strpos( $node->getAttribute( 'class' ), 'page-content' ) !== false ) {
                $blocks[] = $this->convert_page_content_div( $doc, $node );
                continue;
            }

            // Headings → native heading blocks
            if ( preg_match( '/^h([1-6])$/', $tag, $m ) ) {
                $level = (int) $m[1];
                $blocks[] = '<!-- wp:heading {"level":' . $level . '} -->' . "\n"
                    . '<' . $tag . ' class="wp-block-heading">' . $inner_html . '</' . $tag . '>' . "\n"
                    . '<!-- /wp:heading -->';
                continue;
            }

            // Paragraphs → native paragraph blocks
            if ( $tag === 'p' ) {
                // Check if paragraph contains only a shortcode
                $trimmed_inner = trim( strip_tags( $inner_html ) );
                if ( preg_match( '/^\[[\w_-]+/', $trimmed_inner ) ) {
                    $blocks[] = "<!-- wp:shortcode -->\n" . $trimmed_inner . "\n<!-- /wp:shortcode -->";
                } else {
                    $blocks[] = "<!-- wp:paragraph -->\n" . $outer_html . "\n<!-- /wp:paragraph -->";
                }
                continue;
            }

            // Ordered lists → native list block
            if ( $tag === 'ol' ) {
                $blocks[] = '<!-- wp:list {"ordered":true} -->' . "\n" . $outer_html . "\n" . '<!-- /wp:list -->';
                continue;
            }

            // Unordered lists → native list block
            if ( $tag === 'ul' ) {
                $blocks[] = "<!-- wp:list -->\n" . $outer_html . "\n<!-- /wp:list -->";
                continue;
            }

            // Everything else → wp:html
            $blocks[] = "<!-- wp:html -->\n" . $outer_html . "\n<!-- /wp:html -->";
        }

        return implode( "\n\n", array_filter( $blocks ) );
    }

    /**
     * Extract children of a page-content wrapper into individual blocks.
     */
    private function convert_page_content_div( $doc, $wrapper_node ) {
        $blocks = array();

        // Find the wicm-container inside
        $container = $wrapper_node;
        foreach ( $wrapper_node->childNodes as $child ) {
            if ( $child->nodeType === XML_ELEMENT_NODE
                 && strpos( $child->getAttribute( 'class' ), 'wicm-container' ) !== false ) {
                $container = $child;
                break;
            }
        }

        foreach ( $container->childNodes as $node ) {
            if ( $node->nodeType === XML_TEXT_NODE ) {
                $text = trim( $node->textContent );
                if ( empty( $text ) ) continue;
                // Shortcodes
                if ( preg_match( '/\[[\w_-]+/', $text ) ) {
                    $parts = preg_split( '/(\[[^\]]+\])/', $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );
                    foreach ( $parts as $p ) {
                        $p = trim( $p );
                        if ( empty( $p ) ) continue;
                        if ( preg_match( '/^\[/', $p ) ) {
                            $blocks[] = "<!-- wp:shortcode -->\n" . $p . "\n<!-- /wp:shortcode -->";
                        }
                    }
                    continue;
                }
                continue;
            }
            if ( $node->nodeType !== XML_ELEMENT_NODE ) continue;

            $tag        = strtolower( $node->nodeName );
            $inner_html = $this->dom_inner_html( $doc, $node );
            $outer_html = $doc->saveHTML( $node );

            if ( preg_match( '/^h([1-6])$/', $tag, $m ) ) {
                $level = (int) $m[1];
                $blocks[] = '<!-- wp:heading {"level":' . $level . '} -->' . "\n"
                    . '<' . $tag . ' class="wp-block-heading">' . $inner_html . '</' . $tag . '>' . "\n"
                    . '<!-- /wp:heading -->';
            } elseif ( $tag === 'p' ) {
                $trimmed_inner = trim( strip_tags( $inner_html ) );
                if ( preg_match( '/^\[[\w_-]+/', $trimmed_inner ) ) {
                    $blocks[] = "<!-- wp:shortcode -->\n" . $trimmed_inner . "\n<!-- /wp:shortcode -->";
                } else {
                    $blocks[] = "<!-- wp:paragraph -->\n" . $outer_html . "\n<!-- /wp:paragraph -->";
                }
            } elseif ( $tag === 'ol' ) {
                $blocks[] = '<!-- wp:list {"ordered":true} -->' . "\n" . $outer_html . "\n" . '<!-- /wp:list -->';
            } elseif ( $tag === 'ul' ) {
                $blocks[] = "<!-- wp:list -->\n" . $outer_html . "\n<!-- /wp:list -->";
            } elseif ( $tag === 'div' ) {
                $blocks[] = "<!-- wp:html -->\n" . $outer_html . "\n<!-- /wp:html -->";
            } else {
                $blocks[] = "<!-- wp:html -->\n" . $outer_html . "\n<!-- /wp:html -->";
            }
        }

        return implode( "\n\n", $blocks );
    }

    /**
     * Get inner HTML of a DOMNode.
     */
    private function dom_inner_html( $doc, $node ) {
        $inner = '';
        foreach ( $node->childNodes as $child ) {
            $inner .= $doc->saveHTML( $child );
        }
        return $inner;
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    private function load_json( $file ) {
        $path = $this->content_dir . $file;
        if ( ! file_exists( $path ) ) return false;
        $data = json_decode( file_get_contents( $path ), true );
        return is_array( $data ) ? $data : false;
    }
}

new WICM_Developer_Importer();
