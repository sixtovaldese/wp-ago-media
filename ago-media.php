<?php
/**
 * Plugin Name: aGo Media
 * Plugin URI:  https://ago.cl/herramientas/
 * Description: Image optimization on upload (WebP conversion, auto-resize, EXIF stripping) and media auditing (missing ALT, orphaned, duplicates).
 * Version:     1.0.0
 * Requires PHP: 8.1
 * Author:      aGo Lab
 * Author URI:  https://ago.cl/
 * License:     GPL-2.0-or-later
 * Text Domain: ago-media
 */

defined( 'ABSPATH' ) || exit;

define( 'AGOMEDIA_VERSION', '1.0.0' );
define( 'AGOMEDIA_FILE', __FILE__ );
define( 'AGOMEDIA_PATH', plugin_dir_path( __FILE__ ) );
define( 'AGOMEDIA_URL', plugin_dir_url( __FILE__ ) );

// PSR-4 Autoloader
spl_autoload_register( function ( string $class ): void {
    $prefix = 'AgoLab\\Media\\';
    if ( strncmp( $class, $prefix, strlen( $prefix ) ) !== 0 ) {
        return;
    }
    $relative = substr( $class, strlen( $prefix ) );
    $file     = AGOMEDIA_PATH . 'src/' . str_replace( '\\', '/', $relative ) . '.php';
    if ( file_exists( $file ) ) {
        require_once $file;
    }
} );

// Boot
add_action( 'plugins_loaded', [ AgoLab\Media\Plugin::class, 'instance' ] );
