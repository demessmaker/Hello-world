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
 * Custom nav walker to output clean list items.
 */
function wicm_fallback_menu() {
    echo '<a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Home', 'wicm-developer' ) . '</a>';
    echo '<a href="' . esc_url( home_url( '/programs/' ) ) . '">' . esc_html__( 'Programs', 'wicm-developer' ) . '</a>';
    echo '<a href="' . esc_url( home_url( '/about-us/' ) ) . '">' . esc_html__( 'About Us', 'wicm-developer' ) . '</a>';
    echo '<a href="' . esc_url( home_url( '/testimonials/' ) ) . '">' . esc_html__( 'Testimonials', 'wicm-developer' ) . '</a>';
    echo '<a href="' . esc_url( home_url( '/contact-us/' ) ) . '">' . esc_html__( 'Contact', 'wicm-developer' ) . '</a>';
}
