<?php

namespace AgoLab\Media;

defined( 'ABSPATH' ) || exit;

class Plugin {

    private static ?self $instance = null;

    public static function instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_menu', [ $this, 'register_admin_menu' ] );
        add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );

        // Init upload hooks.
        $this->init_upload_hooks();
    }

    /* ───── Admin menu (smart pattern) ───── */

    public function register_admin_menu(): void {
        if ( empty( $GLOBALS['admin_page_hooks']['agolab-tools'] ) ) {
            add_menu_page(
                __( 'aGo Tools', 'ago-media' ),
                __( 'aGo Tools', 'ago-media' ),
                'manage_options',
                'agolab-tools',
                '__return_null',
                'dashicons-hammer',
                81
            );
        }

        add_submenu_page(
            'agolab-tools',
            __( 'aGo Media', 'ago-media' ),
            __( 'Media', 'ago-media' ),
            'manage_options',
            'agomedia',
            [ Admin\Page::class, 'render' ]
        );

        remove_submenu_page( 'agolab-tools', 'agolab-tools' );
    }

    /* ───── REST routes ───── */

    public function register_rest_routes(): void {
        $admin_check = function () {
            return current_user_can( 'manage_options' );
        };

        // Settings
        register_rest_route( 'agomedia/v1', '/settings', [
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'handle_get_settings' ],
                'permission_callback' => $admin_check,
            ],
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'handle_save_settings' ],
                'permission_callback' => $admin_check,
            ],
        ] );

        // Stats
        register_rest_route( 'agomedia/v1', '/stats', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'handle_get_stats' ],
            'permission_callback' => $admin_check,
        ] );

        // Audits
        register_rest_route( 'agomedia/v1', '/audit/missing-alt', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'handle_audit_missing_alt' ],
            'permission_callback' => $admin_check,
        ] );

        register_rest_route( 'agomedia/v1', '/audit/orphaned', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'handle_audit_orphaned' ],
            'permission_callback' => $admin_check,
        ] );

        register_rest_route( 'agomedia/v1', '/audit/duplicates', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'handle_audit_duplicates' ],
            'permission_callback' => $admin_check,
        ] );

        // Optimize existing attachment(s).
        register_rest_route( 'agomedia/v1', '/optimize', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'handle_optimize' ],
            'permission_callback' => $admin_check,
        ] );

        // List non-WebP images eligible for optimization.
        register_rest_route( 'agomedia/v1', '/audit/non-webp', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'handle_audit_non_webp' ],
            'permission_callback' => $admin_check,
        ] );
    }

    /**
     * Optimize one or more existing attachments: resize + strip EXIF + convert to WebP.
     */
    public function handle_optimize( \WP_REST_Request $request ): \WP_REST_Response {
        $ids      = $request->get_param( 'ids' );
        $settings = $this->get_settings();

        if ( ! is_array( $ids ) || empty( $ids ) ) {
            return new \WP_REST_Response( [ 'error' => 'No attachment IDs provided.' ], 400 );
        }

        $results = [];
        $converter = new Converter( $settings['webp_quality'] ?? 82 );
        $resizer   = new Resizer( $settings['max_dimension'] ?? 2560 );

        foreach ( array_map( 'absint', $ids ) as $id ) {
            $file = get_attached_file( $id );
            if ( ! $file || ! file_exists( $file ) ) {
                $results[] = [ 'id' => $id, 'ok' => false, 'msg' => __( 'File not found', 'ago-media' ) ];
                continue;
            }

            $mime     = get_post_mime_type( $id );
            $original = filesize( $file );
            $upload   = [ 'file' => $file, 'url' => wp_get_attachment_url( $id ), 'type' => $mime ];

            // 1. Resize if enabled.
            if ( ! empty( $settings['enable_resize'] ) ) {
                $upload = $resizer->maybe_resize( $upload );
            }

            // 2. Strip EXIF if enabled.
            if ( ! empty( $settings['strip_exif'] ) ) {
                $upload = self::strip_exif( $upload );
            }

            // 3. Convert to WebP if enabled and applicable.
            if ( ! empty( $settings['enable_webp'] ) && Converter::is_webp_supported() ) {
                $upload = $converter->convert_on_upload( $upload );
            }

            // Update attachment if file changed.
            if ( $upload['file'] !== $file ) {
                update_attached_file( $id, $upload['file'] );
                wp_update_post( [
                    'ID'             => $id,
                    'post_mime_type' => $upload['type'],
                ] );

                // Regenerate metadata.
                $metadata = wp_generate_attachment_metadata( $id, $upload['file'] );
                wp_update_attachment_metadata( $id, $metadata );
            }

            $new_size = file_exists( $upload['file'] ) ? filesize( $upload['file'] ) : $original;
            $saved    = $original - $new_size;

            $results[] = [
                'id'    => $id,
                'ok'    => true,
                'saved' => $saved,
                'msg'   => $saved > 0
                    /* translators: %s is a file size, such as 120 KB. */
                    ? sprintf( __( 'Saved %s', 'ago-media' ), size_format( $saved ) )
                    : __( 'Already optimized', 'ago-media' ),
            ];
        }

        return new \WP_REST_Response( [ 'results' => $results ] );
    }

    /**
     * List non-WebP images that could be optimized.
     */
    public function handle_audit_non_webp(): \WP_REST_Response {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $rows = $wpdb->get_results(
            "SELECT ID, post_title, post_mime_type, guid
             FROM {$wpdb->posts}
             WHERE post_type = 'attachment'
               AND post_status = 'inherit'
               AND post_mime_type IN ('image/jpeg', 'image/png')
             ORDER BY ID DESC
             LIMIT 200",
            ARRAY_A
        );

        $items = [];
        foreach ( $rows as $row ) {
            $file = get_attached_file( $row['ID'] );

            /*
             * A record whose file is gone cannot be converted, so it is left out
             * of this list instead of offering an action that would fail. The
             * ALT and orphaned audits do show those records, where knowing the
             * file is missing is the useful part.
             */
            if ( ! $file || ! file_exists( $file ) ) {
                continue;
            }

            $items[] = [
                'id'        => (int) $row['ID'],
                'title'     => $row['post_title'],
                'mime'      => $row['post_mime_type'],
                'size'      => filesize( $file ),
                'size_human' => size_format( filesize( $file ) ),
                'thumbnail' => wp_get_attachment_image_url( $row['ID'], 'thumbnail' ) ?: '',
            ];
        }

        return new \WP_REST_Response( [ 'items' => $items, 'total' => count( $items ) ] );
    }

    public function handle_get_settings(): \WP_REST_Response {
        return new \WP_REST_Response( $this->get_settings() );
    }

    public function handle_save_settings( \WP_REST_Request $request ): \WP_REST_Response {
        $input    = $request->get_json_params();
        $defaults = self::defaults();
        $settings = [];

        $settings['enable_webp']   = ! empty( $input['enable_webp'] );
        $settings['enable_resize'] = ! empty( $input['enable_resize'] );
        $settings['strip_exif']    = ! empty( $input['strip_exif'] );
        $settings['max_dimension'] = isset( $input['max_dimension'] )
            ? absint( $input['max_dimension'] )
            : $defaults['max_dimension'];
        $settings['webp_quality']  = isset( $input['webp_quality'] )
            ? min( 100, max( 1, absint( $input['webp_quality'] ) ) )
            : $defaults['webp_quality'];

        // Clamp max_dimension.
        if ( $settings['max_dimension'] < 800 ) {
            $settings['max_dimension'] = 800;
        }
        if ( $settings['max_dimension'] > 10000 ) {
            $settings['max_dimension'] = 10000;
        }

        update_option( 'agomedia_settings', $settings );

        return new \WP_REST_Response( [ 'saved' => true, 'settings' => $settings ] );
    }

    public function handle_get_stats(): \WP_REST_Response {
        $stats = get_option( 'agomedia_stats', [ 'converted' => 0, 'bytes_saved' => 0 ] );
        return new \WP_REST_Response( $stats );
    }

    public function handle_audit_missing_alt(): \WP_REST_Response {
        $audit = new Audit();
        return new \WP_REST_Response( $audit->get_missing_alt() );
    }

    public function handle_audit_orphaned(): \WP_REST_Response {
        $audit = new Audit();
        return new \WP_REST_Response( $audit->get_orphaned() );
    }

    public function handle_audit_duplicates(): \WP_REST_Response {
        $audit = new Audit();
        return new \WP_REST_Response( $audit->get_duplicates() );
    }

    /* ───── Assets ───── */

    public function enqueue_assets( string $hook ): void {
        if ( ! str_ends_with( $hook, '_page_agomedia' ) ) {
            return;
        }

        wp_enqueue_style(
            'agomedia-admin',
            AGOMEDIA_URL . 'assets/css/admin.css',
            [],
            AGOMEDIA_VERSION
        );

        wp_enqueue_script(
            'agomedia-admin',
            AGOMEDIA_URL . 'assets/js/admin.js',
            [],
            AGOMEDIA_VERSION,
            true
        );

        wp_localize_script( 'agomedia-admin', 'agomediaMedia', [
            'restUrl'  => rest_url( 'agomedia/v1' ),
            'nonce'    => wp_create_nonce( 'wp_rest' ),
            'settings' => $this->get_settings(),
            'webpSupported' => Converter::is_webp_supported(),
            'i18n'     => [
                'saving'         => __( 'Saving...', 'ago-media' ),
                'saveBtn'        => __( 'Save Settings', 'ago-media' ),
                'saved'          => __( 'Settings saved successfully.', 'ago-media' ),
                'saveFailed'     => __( 'The settings could not be saved.', 'ago-media' ),
                'errorLabel'     => __( 'Error', 'ago-media' ),
                'loadFailed'     => __( 'The data could not be loaded.', 'ago-media' ),
                'fileMissing'    => __( 'The file of this record is missing from the uploads folder.', 'ago-media' ),
                'optimizing'     => __( 'Optimizing...', 'ago-media' ),
                'optimizeBtn'    => __( 'Optimize Selected', 'ago-media' ),
                /* translators: %s is a file size, such as 1.2 MB. */
                'optimizeDone'   => __( 'Done. %s saved in total.', 'ago-media' ),
            ],
        ] );
    }

    /* ───── Upload hooks ───── */

    private function init_upload_hooks(): void {
        $settings = $this->get_settings();

        // Resizer runs first (priority 10).
        if ( ! empty( $settings['enable_resize'] ) ) {
            $resizer = new Resizer( $settings['max_dimension'] );
            add_filter( 'wp_handle_upload', [ $resizer, 'maybe_resize' ], 10 );
        }

        // EXIF stripping (priority 15).
        if ( ! empty( $settings['strip_exif'] ) ) {
            add_filter( 'wp_handle_upload', [ self::class, 'strip_exif' ], 15 );
        }

        // WebP conversion (priority 20).
        if ( ! empty( $settings['enable_webp'] ) && Converter::is_webp_supported() ) {
            $converter = new Converter( $settings['webp_quality'] );
            add_filter( 'wp_handle_upload', [ $converter, 'convert_on_upload' ], 20 );
            add_filter( 'wp_generate_attachment_metadata', [ $converter, 'convert_thumbnails' ], 10, 2 );
        }
    }

    /* ───── EXIF stripping ───── */

    public static function strip_exif( array $upload ): array {
        $image_types = [ 'image/jpeg', 'image/png', 'image/webp' ];
        if ( ! in_array( $upload['type'], $image_types, true ) ) {
            return $upload;
        }

        $file = $upload['file'];

        // Try Imagick first.
        if ( class_exists( 'Imagick' ) ) {
            try {
                $img = new \Imagick( $file );
                $img->stripImage();
                $img->writeImage( $file );
                $img->clear();
                $img->destroy();
                return $upload;
            } catch ( \Exception $e ) {
                // Fall through to GD.
            }
        }

        // GD fallback, only works for JPEG.
        if ( $upload['type'] === 'image/jpeg' && function_exists( 'imagecreatefromjpeg' ) ) {
            $img = @imagecreatefromjpeg( $file );
            if ( $img ) {
                imagejpeg( $img, $file, 100 );
                imagedestroy( $img );
            }
        }

        return $upload;
    }

    /* ───── Settings helpers ───── */

    /** @return array<string, mixed> */
    public static function defaults(): array {
        return [
            'enable_webp'   => true,
            'enable_resize' => true,
            'max_dimension' => 2560,
            'strip_exif'    => true,
            'webp_quality'  => 82,
        ];
    }

    /** @return array<string, mixed> */
    private function get_settings(): array {
        return wp_parse_args(
            get_option( 'agomedia_settings', [] ),
            self::defaults()
        );
    }
}
