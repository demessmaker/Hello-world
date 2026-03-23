<?php
/**
 * WICM Developer Theme Functions
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Theme setup.
 */
function wicm_theme_setup() {
    load_theme_textdomain( 'wicm-developer', get_template_directory() . '/languages' );

    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'custom-logo', array(
        'height'      => 48,
        'width'       => 200,
        'flex-height' => true,
        'flex-width'  => true,
    ) );
    add_theme_support( 'html5', array(
        'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script',
    ) );
    add_theme_support( 'customize-selective-refresh-widgets' );

    register_nav_menus( array(
        'primary' => __( 'Primary Menu', 'wicm-developer' ),
        'footer'  => __( 'Footer Menu', 'wicm-developer' ),
    ) );

    add_image_size( 'program-card', 800, 500, true );
    add_image_size( 'hero-banner', 1920, 800, true );
}
add_action( 'after_setup_theme', 'wicm_theme_setup' );

/**
 * Register widget areas.
 */
function wicm_widgets_init() {
    register_sidebar( array(
        'name'          => __( 'Footer Column 1', 'wicm-developer' ),
        'id'            => 'footer-1',
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4>',
        'after_title'   => '</h4>',
    ) );
    register_sidebar( array(
        'name'          => __( 'Footer Column 2', 'wicm-developer' ),
        'id'            => 'footer-2',
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4>',
        'after_title'   => '</h4>',
    ) );
    register_sidebar( array(
        'name'          => __( 'Footer Column 3', 'wicm-developer' ),
        'id'            => 'footer-3',
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4>',
        'after_title'   => '</h4>',
    ) );
}
add_action( 'widgets_init', 'wicm_widgets_init' );

/**
 * Add resource hints for external domains (preconnect, dns-prefetch).
 */
function wicm_resource_hints() {
    echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
    echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
    echo '<link rel="dns-prefetch" href="//fonts.googleapis.com">' . "\n";
    echo '<link rel="dns-prefetch" href="//fonts.gstatic.com">' . "\n";

    // Preload hero image on front page — critical for LCP on mobile
    if ( is_front_page() || is_page( array( 'home', 'accueil' ) ) ) {
        echo '<link rel="preload" as="image" href="https://images.unsplash.com/photo-1511379938547-c1f69419868d?w=768&q=75&fit=crop" media="(max-width: 768px)">' . "\n";
        echo '<link rel="preload" as="image" href="https://images.unsplash.com/photo-1511379938547-c1f69419868d?w=1280&q=80&fit=crop" media="(min-width: 769px) and (max-width: 1280px)">' . "\n";
        echo '<link rel="preload" as="image" href="https://images.unsplash.com/photo-1511379938547-c1f69419868d?w=1920&q=80&fit=crop" media="(min-width: 1281px)">' . "\n";
    }
}
add_action( 'wp_head', 'wicm_resource_hints', 1 );

/**
 * Enqueue scripts and styles.
 */
function wicm_enqueue_assets() {
    wp_enqueue_style(
        'wicm-fonts',
        'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
        array(),
        null
    );

    wp_enqueue_style(
        'wicm-style',
        get_stylesheet_uri(),
        array( 'wicm-fonts' ),
        '2.0.0'
    );

    wp_enqueue_script(
        'wicm-theme-js',
        get_template_directory_uri() . '/assets/js/theme.js',
        array(),
        '2.0.0',
        true
    );
}
add_action( 'wp_enqueue_scripts', 'wicm_enqueue_assets' );

/**
 * Remove WordPress default bloat on the frontend.
 * Block library CSS (~15KB), global styles (~20KB), and classic theme styles
 * are unused since this theme has its own complete design system.
 */
function wicm_remove_wp_bloat() {
    // Remove Gutenberg block library CSS (not used on frontend)
    wp_dequeue_style( 'wp-block-library' );
    wp_dequeue_style( 'wp-block-library-theme' );

    // Remove global styles (CSS variables/presets for blocks)
    wp_dequeue_style( 'global-styles' );

    // Remove classic theme styles
    wp_dequeue_style( 'classic-theme-styles' );

    // Remove WP embed script (oEmbed)
    wp_dequeue_script( 'wp-embed' );
}
add_action( 'wp_enqueue_scripts', 'wicm_remove_wp_bloat', 100 );

/**
 * Remove Google Site Kit and Jetpack frontend scripts on non-admin pages.
 * These add ~30-50KB of tracking JS that slows down mobile performance.
 */
function wicm_remove_tracking_bloat() {
    // Remove Jetpack stats/tracking
    wp_dequeue_script( 'jetpack-stats' );
    wp_dequeue_script( 'jetpack_tracks' );

    // Remove Google Site Kit frontend scripts
    wp_dequeue_script( 'google_gtagjs' );
    wp_dequeue_script( 'googlesitekit-gtag-data' );
}
add_action( 'wp_enqueue_scripts', 'wicm_remove_tracking_bloat', 100 );

/**
 * Remove inline global styles from wp_head.
 */
function wicm_remove_global_styles_inline() {
    remove_action( 'wp_enqueue_scripts', 'wp_enqueue_global_styles' );
    remove_action( 'wp_body_open', 'wp_global_styles_render_svg_filters' );
    remove_action( 'wp_footer', 'wp_enqueue_global_styles', 1 );
}
add_action( 'init', 'wicm_remove_global_styles_inline' );

/**
 * Disable WordPress emoji scripts and styles.
 * These add ~20KB of unused JS/CSS on every page load.
 */
function wicm_disable_emojis() {
    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
    remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
    remove_action( 'wp_print_styles', 'print_emoji_styles' );
    remove_action( 'admin_print_styles', 'print_emoji_styles' );
    remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
    remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
    remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
    add_filter( 'tiny_mce_plugins', 'wicm_disable_emojis_tinymce' );
    add_filter( 'wp_resource_hints', 'wicm_disable_emojis_dns_prefetch', 10, 2 );
}
add_action( 'init', 'wicm_disable_emojis' );

function wicm_disable_emojis_tinymce( $plugins ) {
    if ( is_array( $plugins ) ) {
        return array_diff( $plugins, array( 'wpemoji' ) );
    }
    return array();
}

function wicm_disable_emojis_dns_prefetch( $urls, $relation_type ) {
    if ( 'dns-prefetch' === $relation_type ) {
        $urls = array_filter( $urls, function( $url ) {
            return false === strpos( $url, 'https://s.w.org/images/core/emoji/' );
        });
    }
    return $urls;
}

/**
 * Clear footer programs transient when programs are created/updated/deleted.
 */
function wicm_clear_programs_cache( $post_id ) {
    if ( 'wicm_program' === get_post_type( $post_id ) ) {
        delete_transient( 'wicm_footer_programs_en' );
        delete_transient( 'wicm_footer_programs_fr' );
    }
}
add_action( 'save_post', 'wicm_clear_programs_cache' );
add_action( 'delete_post', 'wicm_clear_programs_cache' );

/**
 * Conditionally load Contact Form 7 assets only on pages that use forms.
 * Prevents loading ~30KB of unused JS/CSS on every page.
 */
function wicm_conditional_cf7_assets() {
    // Pages that contain [contact-form-7] shortcode
    $cf7_pages = array( 'home', 'accueil', 'contact', 'book-a-trial', 'contactez-nous', 'reserver-un-essai' );
    if ( is_front_page() || is_page( $cf7_pages ) ) {
        return;
    }
    add_filter( 'wpcf7_load_js', '__return_false' );
    add_filter( 'wpcf7_load_css', '__return_false' );
}
add_action( 'wp', 'wicm_conditional_cf7_assets' );

/**
 * Register additional nav menus for footer.
 */
function wicm_register_footer_menus() {
    register_nav_menus( array(
        'footer-links' => __( 'Footer Quick Links', 'wicm-developer' ),
    ) );
}
add_action( 'after_setup_theme', 'wicm_register_footer_menus', 20 );

/**
 * Custom nav walker to output clean list items.
 */
function wicm_fallback_menu() {
    $base = esc_url( home_url( '/' ) );
    echo '<a href="' . $base . '">' . esc_html__( 'Home', 'wicm-developer' ) . '</a>';
    echo '<a href="' . $base . '#programs">' . esc_html__( 'Programs', 'wicm-developer' ) . '</a>';
    echo '<a href="' . $base . '#about">' . esc_html__( 'About Us', 'wicm-developer' ) . '</a>';
    echo '<a href="' . $base . '#testimonials">' . esc_html__( 'Testimonials', 'wicm-developer' ) . '</a>';
    echo '<a href="' . $base . '#contact">' . esc_html__( 'Contact', 'wicm-developer' ) . '</a>';
}

/**
 * Customizer settings for Header & Footer.
 */
function wicm_customize_register( $wp_customize ) {

    // ===========================
    // HEADER SECTION
    // ===========================
    $wp_customize->add_section( 'wicm_header', array(
        'title'    => __( 'Header Settings', 'wicm-developer' ),
        'priority' => 30,
    ) );

    // Logo text (fallback when no logo image)
    $wp_customize->add_setting( 'wicm_logo_text', array(
        'default'           => 'West Island Music School',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( 'wicm_logo_text', array(
        'label'   => __( 'Logo Text (when no logo image)', 'wicm-developer' ),
        'section' => 'wicm_header',
        'type'    => 'text',
    ) );

    // Logo accent word(s) — displayed in red
    $wp_customize->add_setting( 'wicm_logo_accent', array(
        'default'           => 'West Island',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( 'wicm_logo_accent', array(
        'label'       => __( 'Logo Accent Words (shown in red)', 'wicm-developer' ),
        'section'     => 'wicm_header',
        'type'        => 'text',
    ) );

    // CTA button text
    $wp_customize->add_setting( 'wicm_header_cta_text', array(
        'default'           => 'Book a Trial',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( 'wicm_header_cta_text', array(
        'label'   => __( 'Header Button Text', 'wicm-developer' ),
        'section' => 'wicm_header',
        'type'    => 'text',
    ) );

    // CTA button URL
    $wp_customize->add_setting( 'wicm_header_cta_url', array(
        'default'           => '/book-a-trial/',
        'sanitize_callback' => 'esc_url_raw',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( 'wicm_header_cta_url', array(
        'label'   => __( 'Header Button URL', 'wicm-developer' ),
        'section' => 'wicm_header',
        'type'    => 'url',
    ) );

    // CTA button text (French)
    $wp_customize->add_setting( 'wicm_header_cta_text_fr', array(
        'default'           => 'Réserver un essai',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'wicm_header_cta_text_fr', array(
        'label'   => __( 'Header Button Text (French)', 'wicm-developer' ),
        'section' => 'wicm_header',
        'type'    => 'text',
    ) );

    // CTA button URL (French)
    $wp_customize->add_setting( 'wicm_header_cta_url_fr', array(
        'default'           => '/fr/reserver-un-essai/',
        'sanitize_callback' => 'esc_url_raw',
    ) );
    $wp_customize->add_control( 'wicm_header_cta_url_fr', array(
        'label'   => __( 'Header Button URL (French)', 'wicm-developer' ),
        'section' => 'wicm_header',
        'type'    => 'url',
    ) );

    // ===========================
    // FOOTER SECTION
    // ===========================
    $wp_customize->add_section( 'wicm_footer', array(
        'title'    => __( 'Footer Settings', 'wicm-developer' ),
        'priority' => 150,
    ) );

    // Footer tagline
    $wp_customize->add_setting( 'wicm_footer_tagline', array(
        'default'           => 'Making music magical since 1997',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( 'wicm_footer_tagline', array(
        'label'   => __( 'Footer Tagline', 'wicm-developer' ),
        'section' => 'wicm_footer',
        'type'    => 'text',
    ) );

    // Footer description
    $wp_customize->add_setting( 'wicm_footer_description', array(
        'default'           => 'Professional music education for all ages in West Island, Montreal. Piano, guitar, voice, drums, violin and more.',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( 'wicm_footer_description', array(
        'label'   => __( 'Footer Description', 'wicm-developer' ),
        'section' => 'wicm_footer',
        'type'    => 'textarea',
    ) );

    // Facebook URL
    $wp_customize->add_setting( 'wicm_facebook_url', array(
        'default'           => 'https://www.facebook.com/westislandmusicschool',
        'sanitize_callback' => 'esc_url_raw',
    ) );
    $wp_customize->add_control( 'wicm_facebook_url', array(
        'label'   => __( 'Facebook URL', 'wicm-developer' ),
        'section' => 'wicm_footer',
        'type'    => 'url',
    ) );

    // Instagram URL
    $wp_customize->add_setting( 'wicm_instagram_url', array(
        'default'           => 'https://www.instagram.com/westislandmusicschool',
        'sanitize_callback' => 'esc_url_raw',
    ) );
    $wp_customize->add_control( 'wicm_instagram_url', array(
        'label'   => __( 'Instagram URL', 'wicm-developer' ),
        'section' => 'wicm_footer',
        'type'    => 'url',
    ) );

    // Contact - Address
    $wp_customize->add_setting( 'wicm_contact_address', array(
        'default'           => 'Bb-245 Blvd St-Jean, Pointe-Claire, QC, H9R-3J1',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'wicm_contact_address', array(
        'label'   => __( 'Contact Address', 'wicm-developer' ),
        'section' => 'wicm_footer',
        'type'    => 'text',
    ) );

    // Contact - Email
    $wp_customize->add_setting( 'wicm_contact_email', array(
        'default'           => 'info@westisland.music',
        'sanitize_callback' => 'sanitize_email',
    ) );
    $wp_customize->add_control( 'wicm_contact_email', array(
        'label'   => __( 'Contact Email', 'wicm-developer' ),
        'section' => 'wicm_footer',
        'type'    => 'email',
    ) );

    // Contact - Hours Line 1
    $wp_customize->add_setting( 'wicm_hours_weekday', array(
        'default'           => 'Tue-Fri: 12:00 PM - 6:00 PM',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'wicm_hours_weekday', array(
        'label'   => __( 'Business Hours (Weekdays)', 'wicm-developer' ),
        'section' => 'wicm_footer',
        'type'    => 'text',
    ) );

    // Contact - Hours Line 2
    $wp_customize->add_setting( 'wicm_hours_weekend', array(
        'default'           => 'Saturday: 10:00 AM - 4:00 PM',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'wicm_hours_weekend', array(
        'label'   => __( 'Business Hours (Weekend)', 'wicm-developer' ),
        'section' => 'wicm_footer',
        'type'    => 'text',
    ) );

    // Copyright text
    $wp_customize->add_setting( 'wicm_copyright', array(
        'default'           => 'West Island Conservatory of Music. All rights reserved.',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'wicm_copyright', array(
        'label'       => __( 'Copyright Text (year is auto-added)', 'wicm-developer' ),
        'section'     => 'wicm_footer',
        'type'        => 'text',
    ) );

    // --- French Footer Translations ---
    $wp_customize->add_setting( 'wicm_footer_tagline_fr', array(
        'default'           => 'La magie de la musique depuis 1997',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'wicm_footer_tagline_fr', array(
        'label'   => __( 'Footer Tagline (French)', 'wicm-developer' ),
        'section' => 'wicm_footer',
        'type'    => 'text',
    ) );

    $wp_customize->add_setting( 'wicm_footer_description_fr', array(
        'default'           => 'Éducation musicale professionnelle pour tous les âges dans l\'Ouest-de-l\'Île de Montréal. Piano, guitare, chant, batterie, violon et plus.',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'wicm_footer_description_fr', array(
        'label'   => __( 'Footer Description (French)', 'wicm-developer' ),
        'section' => 'wicm_footer',
        'type'    => 'textarea',
    ) );

    $wp_customize->add_setting( 'wicm_copyright_fr', array(
        'default'           => 'Conservatoire de musique West Island. Tous droits réservés.',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'wicm_copyright_fr', array(
        'label'   => __( 'Copyright Text - French', 'wicm-developer' ),
        'section' => 'wicm_footer',
        'type'    => 'text',
    ) );

    $wp_customize->add_setting( 'wicm_hours_weekday_fr', array(
        'default'           => 'Mar-Ven: 12h00 - 18h00',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'wicm_hours_weekday_fr', array(
        'label'   => __( 'Business Hours Weekdays (French)', 'wicm-developer' ),
        'section' => 'wicm_footer',
        'type'    => 'text',
    ) );

    $wp_customize->add_setting( 'wicm_hours_weekend_fr', array(
        'default'           => 'Samedi: 10h00 - 16h00',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'wicm_hours_weekend_fr', array(
        'label'   => __( 'Business Hours Weekend (French)', 'wicm-developer' ),
        'section' => 'wicm_footer',
        'type'    => 'text',
    ) );

    // Show Programs in Footer
    $wp_customize->add_setting( 'wicm_footer_show_programs', array(
        'default'           => true,
        'sanitize_callback' => 'wicm_sanitize_checkbox',
    ) );
    $wp_customize->add_control( 'wicm_footer_show_programs', array(
        'label'   => __( 'Show Programs column in footer', 'wicm-developer' ),
        'section' => 'wicm_footer',
        'type'    => 'checkbox',
    ) );

    // ===========================
    // SMTP / EMAIL SECTION
    // ===========================
    $wp_customize->add_section( 'wicm_smtp', array(
        'title'    => __( 'Email / SMTP Settings', 'wicm-developer' ),
        'priority' => 160,
    ) );

    $wp_customize->add_setting( 'wicm_smtp_enabled', array(
        'default'           => false,
        'sanitize_callback' => 'wicm_sanitize_checkbox',
    ) );
    $wp_customize->add_control( 'wicm_smtp_enabled', array(
        'label'   => __( 'Enable SMTP', 'wicm-developer' ),
        'section' => 'wicm_smtp',
        'type'    => 'checkbox',
    ) );

    $wp_customize->add_setting( 'wicm_smtp_host', array(
        'default'           => 'mail.westisland.music',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'wicm_smtp_host', array(
        'label'   => __( 'SMTP Host', 'wicm-developer' ),
        'section' => 'wicm_smtp',
        'type'    => 'text',
    ) );

    $wp_customize->add_setting( 'wicm_smtp_port', array(
        'default'           => '465',
        'sanitize_callback' => 'absint',
    ) );
    $wp_customize->add_control( 'wicm_smtp_port', array(
        'label'   => __( 'SMTP Port', 'wicm-developer' ),
        'section' => 'wicm_smtp',
        'type'    => 'number',
    ) );

    $wp_customize->add_setting( 'wicm_smtp_encryption', array(
        'default'           => 'ssl',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'wicm_smtp_encryption', array(
        'label'   => __( 'Encryption', 'wicm-developer' ),
        'section' => 'wicm_smtp',
        'type'    => 'select',
        'choices' => array(
            'tls'  => 'TLS',
            'ssl'  => 'SSL',
            'none' => 'None',
        ),
    ) );

    $wp_customize->add_setting( 'wicm_smtp_username', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'wicm_smtp_username', array(
        'label'   => __( 'SMTP Username', 'wicm-developer' ),
        'section' => 'wicm_smtp',
        'type'    => 'text',
    ) );

    $wp_customize->add_setting( 'wicm_smtp_password', array(
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'wicm_smtp_password', array(
        'label'       => __( 'SMTP Password', 'wicm-developer' ),
        'description' => __( 'Enter the password for your email account.', 'wicm-developer' ),
        'section'     => 'wicm_smtp',
        'type'        => 'password',
    ) );

    $wp_customize->add_setting( 'wicm_smtp_from_email', array(
        'default'           => 'info@westisland.music',
        'sanitize_callback' => 'sanitize_email',
    ) );
    $wp_customize->add_control( 'wicm_smtp_from_email', array(
        'label'   => __( 'From Email Address', 'wicm-developer' ),
        'section' => 'wicm_smtp',
        'type'    => 'email',
    ) );

    $wp_customize->add_setting( 'wicm_smtp_from_name', array(
        'default'           => 'West Island Conservatory of Music',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'wicm_smtp_from_name', array(
        'label'   => __( 'From Name', 'wicm-developer' ),
        'section' => 'wicm_smtp',
        'type'    => 'text',
    ) );
}
add_action( 'customize_register', 'wicm_customize_register' );

/**
 * Sanitize checkbox.
 */
function wicm_sanitize_checkbox( $value ) {
    return ( isset( $value ) && true == $value ) ? true : false;
}

/**
 * Configure wp_mail to use SMTP when enabled in Customizer.
 *
 * WordPress uses PHP mail() by default which most hosts block or
 * routes to spam. This hooks into PHPMailer to use authenticated
 * SMTP instead, ensuring Contact Form 7 emails are delivered.
 */
function wicm_smtp_setup( $phpmailer ) {
    if ( ! get_theme_mod( 'wicm_smtp_enabled', false ) ) {
        return;
    }

    $host     = get_theme_mod( 'wicm_smtp_host', 'mail.westisland.music' );
    $port     = (int) get_theme_mod( 'wicm_smtp_port', 465 );
    $encrypt  = get_theme_mod( 'wicm_smtp_encryption', 'ssl' );
    $username = get_theme_mod( 'wicm_smtp_username', '' );
    $password = get_theme_mod( 'wicm_smtp_password', '' );

    if ( empty( $host ) || empty( $username ) || empty( $password ) ) {
        return;
    }

    $phpmailer->isSMTP();
    $phpmailer->Host       = $host;
    $phpmailer->Port       = $port;
    $phpmailer->SMTPSecure = ( 'none' === $encrypt ) ? '' : $encrypt;
    $phpmailer->SMTPAuth   = true;
    $phpmailer->Username   = $username;
    $phpmailer->Password   = $password;
}
add_action( 'phpmailer_init', 'wicm_smtp_setup' );

/**
 * Set the From email and name for all outgoing WordPress emails.
 */
function wicm_mail_from( $email ) {
    $custom = get_theme_mod( 'wicm_smtp_from_email', '' );
    return ! empty( $custom ) ? $custom : $email;
}

function wicm_mail_from_name( $name ) {
    $custom = get_theme_mod( 'wicm_smtp_from_name', '' );
    return ! empty( $custom ) ? $custom : $name;
}

if ( get_theme_mod( 'wicm_smtp_enabled', false ) ) {
    add_filter( 'wp_mail_from', 'wicm_mail_from' );
    add_filter( 'wp_mail_from_name', 'wicm_mail_from_name' );
}
