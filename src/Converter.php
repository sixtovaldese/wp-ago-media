<?php

namespace AgoLab\Media;

defined( 'ABSPATH' ) || exit;

class Converter {

    private int $quality;

    /** MIME types eligible for WebP conversion. */
    private const CONVERTIBLE = [
        'image/jpeg',
        'image/png',
    ];

    public function __construct( int $quality = 82 ) {
        $this->quality = $quality;
    }

    /* ───── Capability check ───── */

    public static function is_webp_supported(): bool {
        // Imagick check.
        if ( class_exists( 'Imagick' ) ) {
            $formats = \Imagick::queryFormats( 'WEBP' );
            if ( ! empty( $formats ) ) {
                return true;
            }
        }

        // GD check.
        if ( function_exists( 'imagewebp' ) && function_exists( 'gd_info' ) ) {
            $info = gd_info();
            if ( ! empty( $info['WebP Support'] ) ) {
                return true;
            }
        }

        return false;
    }

    /* ───── Convert on upload (wp_handle_upload filter) ───── */

    public function convert_on_upload( array $upload ): array {
        if ( ! $this->is_convertible( $upload['type'] ) ) {
            return $upload;
        }

        $source   = $upload['file'];
        $original = filesize( $source );

        $webp_path = $this->to_webp( $source, $upload['type'] );
        if ( ! $webp_path ) {
            return $upload;
        }

        $new_size = filesize( $webp_path );

        // Only keep WebP if it's actually smaller.
        if ( $new_size >= $original ) {
            wp_delete_file( $webp_path );
            return $upload;
        }

        // Replace original file.
        wp_delete_file( $source );

        $upload['file'] = $webp_path;
        $upload['url']  = substr_replace(
            $upload['url'],
            '.webp',
            strrpos( $upload['url'], '.' )
        );
        $upload['type'] = 'image/webp';

        // Track stats.
        $this->track_conversion( $original - $new_size );

        return $upload;
    }

    /* ───── Convert thumbnails (wp_generate_attachment_metadata filter) ───── */

    public function convert_thumbnails( array $metadata, int $attachment_id ): array {
        if ( empty( $metadata['sizes'] ) ) {
            return $metadata;
        }

        $upload_dir = wp_get_upload_dir();
        $base_dir   = trailingslashit( $upload_dir['basedir'] );

        // Get subdirectory from the main file path.
        $file_dir = '';
        if ( ! empty( $metadata['file'] ) ) {
            $file_dir = trailingslashit( dirname( $metadata['file'] ) );
        }

        foreach ( $metadata['sizes'] as $size_key => &$size_data ) {
            $thumb_file = $base_dir . $file_dir . $size_data['file'];

            if ( ! file_exists( $thumb_file ) ) {
                continue;
            }

            $mime = $size_data['mime-type'] ?? '';
            if ( ! $this->is_convertible( $mime ) ) {
                continue;
            }

            $original_size = filesize( $thumb_file );
            $webp_path     = $this->to_webp( $thumb_file, $mime );

            if ( ! $webp_path ) {
                continue;
            }

            $new_size = filesize( $webp_path );

            if ( $new_size >= $original_size ) {
                wp_delete_file( $webp_path );
                continue;
            }

            wp_delete_file( $thumb_file );

            $size_data['file']      = basename( $webp_path );
            $size_data['mime-type'] = 'image/webp';

            $this->track_conversion( $original_size - $new_size );
        }
        unset( $size_data );

        return $metadata;
    }

    /* ───── Core conversion ───── */

    private function to_webp( string $source, string $mime ): ?string {
        $webp_path = preg_replace( '/\.[^.]+$/', '.webp', $source );

        // Try Imagick first.
        if ( class_exists( 'Imagick' ) ) {
            try {
                $img = new \Imagick( $source );
                $img->setImageFormat( 'webp' );
                $img->setImageCompressionQuality( $this->quality );

                // For PNG with alpha, set lossless for better quality.
                if ( $mime === 'image/png' ) {
                    $img->setOption( 'webp:lossless', 'false' );
                    $img->setOption( 'webp:alpha-quality', (string) $this->quality );
                }

                $img->writeImage( $webp_path );
                $img->clear();
                $img->destroy();

                return file_exists( $webp_path ) ? $webp_path : null;
            } catch ( \Exception $e ) {
                // Fall through to GD.
            }
        }

        // GD fallback.
        if ( ! function_exists( 'imagewebp' ) ) {
            return null;
        }

        $img = null;
        switch ( $mime ) {
            case 'image/jpeg':
                if ( function_exists( 'imagecreatefromjpeg' ) ) {
                    $img = @imagecreatefromjpeg( $source );
                }
                break;
            case 'image/png':
                if ( function_exists( 'imagecreatefrompng' ) ) {
                    $img = @imagecreatefrompng( $source );
                    if ( $img ) {
                        imagepalettetotruecolor( $img );
                        imagealphablending( $img, true );
                        imagesavealpha( $img, true );
                    }
                }
                break;
        }

        if ( ! $img ) {
            return null;
        }

        $result = imagewebp( $img, $webp_path, $this->quality );
        imagedestroy( $img );

        if ( ! $result || ! file_exists( $webp_path ) ) {
            return null;
        }

        return $webp_path;
    }

    /* ───── Helpers ───── */

    private function is_convertible( string $mime ): bool {
        return in_array( $mime, self::CONVERTIBLE, true );
    }

    private function track_conversion( int $bytes_saved ): void {
        $stats = get_option( 'agomedia_stats', [ 'converted' => 0, 'bytes_saved' => 0 ] );
        $stats['converted']++;
        $stats['bytes_saved'] += max( 0, $bytes_saved );
        update_option( 'agomedia_stats', $stats );
    }
}
