<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="site-header" id="site-header">
    <div class="header-inner">
        <!-- Logo -->
        <div class="header-logo">
            <?php if ( has_custom_logo() ) : ?>
                <?php the_custom_logo(); ?>
            <?php else : ?>
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>">
                    <span class="logo-text"><span><?php esc_html_e( 'West Island', 'wicm-developer' ); ?></span> <?php esc_html_e( 'Music School', 'wicm-developer' ); ?></span>
                </a>
            <?php endif; ?>
        </div>

        <!-- Primary Navigation -->
        <nav class="primary-nav" role="navigation" aria-label="<?php esc_attr_e( 'Primary Menu', 'wicm-developer' ); ?>">
            <?php
            wp_nav_menu( array(
                'theme_location' => 'primary',
                'container'      => false,
                'items_wrap'     => '%3$s',
                'fallback_cb'    => 'wicm_fallback_menu',
                'depth'          => 1,
            ) );
            ?>
        </nav>

        <!-- Header Actions -->
        <div class="header-actions">
            <?php if ( function_exists( 'pll_the_languages' ) ) : ?>
                <div class="lang-switcher">
                    <?php pll_the_languages( array( 'show_flags' => 1, 'show_names' => 1, 'hide_current' => 1 ) ); ?>
                </div>
            <?php endif; ?>
            <a href="<?php echo esc_url( home_url( '/book-a-trial/' ) ); ?>" class="btn btn-primary btn-sm"><?php esc_html_e( 'Book a Trial', 'wicm-developer' ); ?></a>

            <!-- Mobile Menu Button -->
            <button class="mobile-menu-btn" id="mobile-menu-btn" aria-label="<?php esc_attr_e( 'Toggle menu', 'wicm-developer' ); ?>" aria-expanded="false">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                </svg>
            </button>
        </div>
    </div>

    <!-- Mobile Menu -->
    <nav class="mobile-menu" id="mobile-menu" role="navigation" aria-label="<?php esc_attr_e( 'Mobile Menu', 'wicm-developer' ); ?>">
        <?php
        wp_nav_menu( array(
            'theme_location' => 'primary',
            'container'      => false,
            'items_wrap'     => '%3$s',
            'fallback_cb'    => 'wicm_fallback_menu',
            'depth'          => 1,
        ) );
        ?>
        <?php if ( function_exists( 'pll_the_languages' ) ) : ?>
            <div class="lang-switcher-mobile">
                <?php pll_the_languages( array( 'show_flags' => 1, 'show_names' => 1, 'hide_current' => 1 ) ); ?>
            </div>
        <?php endif; ?>
        <a href="<?php echo esc_url( home_url( '/book-a-trial/' ) ); ?>" class="btn btn-primary" style="margin: 1rem; display: block; text-align: center;"><?php esc_html_e( 'Book a Trial', 'wicm-developer' ); ?></a>
    </nav>
</header>
