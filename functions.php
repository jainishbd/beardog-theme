<?php
/**
 * Beardog functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Beardog
 */

if ( ! defined( '_BD_VERSION' ) ) {
	// Replace the version number of the theme on each release.
	define( '_BD_VERSION', '1.0.0' );
}

/**
 * Sets up theme defaults and registers support for various WordPress features.
 *
 * Note that this function is hooked into the after_setup_theme hook, which
 * runs before the init hook. The init hook is too late for some features, such
 * as indicating support for post thumbnails.
 */
function beardog_setup() {

	// Add default posts and comments RSS feed links to head.
	add_theme_support( 'automatic-feed-links' );

	/*
		* Let WordPress manage the document title.
		* By adding theme support, we declare that this theme does not use a
		* hard-coded <title> tag in the document head, and expect WordPress to
		* provide it for us.
		*/
	add_theme_support( 'title-tag' );

	/*
		* Enable support for Post Thumbnails on posts and pages.
		*
		* @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
		*/
	add_theme_support( 'post-thumbnails' );

	// This theme uses wp_nav_menu() in one location.
	register_nav_menus(
		array(
			'menu-1' => esc_html__( 'Primary', 'beardog' ),
		)
	);

	/*
		* Switch default core markup for search form, comment form, and comments
		* to output valid HTML5.
		*/
	add_theme_support(
		'html5',
		array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
			'style',
			'script',
		)
	);

	// Set up the WordPress core custom background feature.
	add_theme_support(
		'custom-background',
		apply_filters(
			'beardog_custom_background_args',
			array(
				'default-color' => 'ffffff',
				'default-image' => '',
			)
		)
	);

	// Add theme support for selective refresh for widgets.
	add_theme_support( 'customize-selective-refresh-widgets' );

	/**
	 * Add support for core custom logo.
	 *
	 * @link https://codex.wordpress.org/Theme_Logo
	 */
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 250,
			'width'       => 250,
			'flex-width'  => true,
			'flex-height' => true,
		)
	);
}
add_action( 'after_setup_theme', 'beardog_setup' );

/**
 * Set the content width in pixels, based on the theme's design and stylesheet.
 *
 * Priority 0 to make it available to lower priority callbacks.
 *
 * @global int $content_width
 */
function beardog_content_width() {
	$GLOBALS['content_width'] = apply_filters( 'beardog_content_width', 640 );
}
add_action( 'after_setup_theme', 'beardog_content_width', 0 );

/**
 * Register widget area.
 *
 * @link https://developer.wordpress.org/themes/functionality/sidebars/#registering-a-sidebar
 */
function beardog_widgets_init() {
	register_sidebar(
		array(
			'name'          => esc_html__( 'Sidebar', 'beardog' ),
			'id'            => 'sidebar-1',
			'description'   => esc_html__( 'Add widgets here.', 'beardog' ),
			'before_widget' => '<section id="%1$s" class="widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h2 class="widget-title">',
			'after_title'   => '</h2>',
		)
	);
}
add_action( 'widgets_init', 'beardog_widgets_init' );

/**
 * Enqueue scripts and styles.
 */
function beardog_scripts() {
	wp_enqueue_style( 'beardog-style', get_stylesheet_uri(), array(), _BD_VERSION );

	wp_enqueue_script( 'beardog-navigation', get_template_directory_uri() . '/js/navigation.js', array(), _BD_VERSION, true );
}
add_action( 'wp_enqueue_scripts', 'beardog_scripts' );

/**
 * Add support for SVG and WebP images.
 */ 
function beardogdigital_mime_types( $mimes ) {
    $mimes['svg'] = 'image/svg+xml';
    $mimes['webp'] = 'image/webp';
    return $mimes;
}
add_filter( 'upload_mimes', 'beardogdigital_mime_types' );

/**
 * Disable the emoji's
 */
function disable_emojis() {
 remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
 remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
 remove_action( 'wp_print_styles', 'print_emoji_styles' );
 remove_action( 'admin_print_styles', 'print_emoji_styles' ); 
 remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
 remove_filter( 'comment_text_rss', 'wp_staticize_emoji' ); 
 remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
}
add_action( 'init', 'disable_emojis' );

/**
 * Remove image metadata on upload
 */
add_filter( 'wp_handle_upload', 'beardog_strip_metadata_from_images_on_upload' );

function beardog_strip_metadata_from_images_on_upload( array $upload ): array {
    if ( ! in_array( $upload['type'], array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml' ), true ) ) {
        return $upload;
    }

    try {
        beardog_strip_metadata_from_image( $upload['file'] );
    } catch ( \ImagickException $e ) {
        // Do nothing.
    }

    return $upload;
}

function beardog_strip_metadata_from_image( string $file, ?string $output = null ) {
    $image = new \Imagick( $file );

    // Check if we have an ICC profile, so we can restore it later.
    $profile = null;
    try {
        $profile = $image->getImageProfile( 'icc' );
    } catch ( \ImagickException $exception ) {
        // Raises an exception if no profile is found.
    }

    // Strip all image metadata.
    $image->stripImage();

    // Restore the ICC profile if we have one.
    if ( ! empty( $profile ) ) {
        $image->setImageProfile( 'icc', $profile );
    }

    if ( empty( $output ) ) {
        $output = $file;
    }

    $image->writeImage( $output );
    $image->clear();
    $image->destroy();
}

/**
 * Disable all comments from site. 
 */
add_action('admin_init', function () {
    // Redirect any user trying to access comments page
    global $pagenow;
     
    if ($pagenow === 'edit-comments.php') {
        wp_safe_redirect(admin_url());
        exit;
    }
 
    // Remove comments metabox from dashboard
    remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
 
    // Disable support for comments and trackbacks in post types
    foreach (get_post_types() as $post_type) {
        if (post_type_supports($post_type, 'comments')) {
            remove_post_type_support($post_type, 'comments');
            remove_post_type_support($post_type, 'trackbacks');
        }
    }
});
 
// Close comments on the front-end
add_filter('comments_open', '__return_false', 20, 2);
add_filter('pings_open', '__return_false', 20, 2);
 
// Hide existing comments
add_filter('comments_array', '__return_empty_array', 10, 2);
 
// Remove comments page in menu
add_action('admin_menu', function () {
    remove_menu_page('edit-comments.php');
});
 
// Remove comments links from admin bar
add_action('init', function () {
    if (is_admin_bar_showing()) {
        remove_action('admin_bar_menu', 'wp_admin_bar_comments_menu', 60);
    }
});

/**
 * Phone number validatio for contact form 7 plugin.
 */ 
function custom_phone_validation($result,$tag){
$type = $tag->type;
$name = $tag->name;
if($type == 'tel' || $type == 'tel*'){
	$phoneNumber = isset( $_POST[$name] ) ? trim( $_POST[$name] ) : '';
	$phoneNumber = preg_replace('/[() .+-]/', '', $phoneNumber);
		if (strlen((string)$phoneNumber) != 10) {
			$result->invalidate( $tag, 'Please enter a valid phone number.' );
		}
}
return $result;
}
add_filter('wpcf7_validate_tel','custom_phone_validation', 10, 2);
add_filter('wpcf7_validate_tel*', 'custom_phone_validation', 10, 2);

// Disable Gutenberg on the back end.
add_filter( 'use_block_editor_for_post', '__return_false' );
// Disable Gutenberg for widgets.
add_filter( 'use_widgets_block_editor', '__return_false' );
add_action( 'wp_enqueue_scripts', function() {
    // Remove CSS on the front end.
    wp_dequeue_style( 'wp-block-library' );
    // Remove Gutenberg theme.
    wp_dequeue_style( 'wp-block-library-theme' );
    // Remove inline global CSS on the front end.
    wp_dequeue_style( 'global-styles' );
    // Remove classic-themes CSS for backwards compatibility for button blocks.
    wp_dequeue_style( 'classic-theme-styles' );
}, 20 );

/**
 * Custom template tags for this theme.
 */
require get_template_directory() . '/inc/template-tags.php';

/**
 * Functions which enhance the theme by hooking into WordPress.
 */
require get_template_directory() . '/inc/template-functions.php';

/**
 * Customizer additions.
 */
require get_template_directory() . '/inc/customizer.php';

/**
 * Load WooCommerce compatibility file.
 */
if ( class_exists( 'WooCommerce' ) ) {
	require get_template_directory() . '/inc/woocommerce.php';
}

/******************************
 * Developer code starts here *
 ******************************/