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
 * Enqueue scripts and styles.
 */
function wicm_enqueue_assets() {
    wp_enqueue_style(
        'wicm-fonts',
        'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap',
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

    // ===========================
    // FOOTER SECTION
    // ===========================
    $wp_customize->add_section( 'wicm_footer', array(
        'title'    => __( 'Footer Settings', 'wicm-developer' ),
        'priority' => 150,
    ) );

    // Footer tagline
    $wp_customize->add_setting( 'wicm_footer_tagline', array(
        'default'           => 'Making music magical since 1999',
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
        'default'           => 'Pointe-Claire, QC, Canada',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'wicm_contact_address', array(
        'label'   => __( 'Contact Address', 'wicm-developer' ),
        'section' => 'wicm_footer',
        'type'    => 'text',
    ) );

    // Contact - Email
    $wp_customize->add_setting( 'wicm_contact_email', array(
        'default'           => 'musiconlinewestisland@gmail.com',
        'sanitize_callback' => 'sanitize_email',
    ) );
    $wp_customize->add_control( 'wicm_contact_email', array(
        'label'   => __( 'Contact Email', 'wicm-developer' ),
        'section' => 'wicm_footer',
        'type'    => 'email',
    ) );

    // Contact - Hours Line 1
    $wp_customize->add_setting( 'wicm_hours_weekday', array(
        'default'           => 'Mon-Fri: 9:00 AM - 9:00 PM',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'wicm_hours_weekday', array(
        'label'   => __( 'Business Hours (Weekdays)', 'wicm-developer' ),
        'section' => 'wicm_footer',
        'type'    => 'text',
    ) );

    // Contact - Hours Line 2
    $wp_customize->add_setting( 'wicm_hours_weekend', array(
        'default'           => 'Saturday: 9:00 AM - 5:00 PM',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'wicm_hours_weekend', array(
        'label'   => __( 'Business Hours (Weekend)', 'wicm-developer' ),
        'section' => 'wicm_footer',
        'type'    => 'text',
    ) );

    // Copyright text
    $wp_customize->add_setting( 'wicm_copyright', array(
        'default'           => 'West Island Music School. All rights reserved.',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'wicm_copyright', array(
        'label'       => __( 'Copyright Text (year is auto-added)', 'wicm-developer' ),
        'section'     => 'wicm_footer',
        'type'        => 'text',
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
}
add_action( 'customize_register', 'wicm_customize_register' );

/**
 * Sanitize checkbox.
 */
function wicm_sanitize_checkbox( $value ) {
    return ( isset( $value ) && true == $value ) ? true : false;
}
