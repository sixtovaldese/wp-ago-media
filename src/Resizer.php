<?php

namespace AgoLab\Media;

defined( 'ABSPATH' ) || exit;

class Resizer {

    private int $max_dimension;

    public function __construct( int $max_dimension = 2560 ) {
        $this->max_dimension = $max_dimension;
    }

    /**
     * Resize image if it exceeds max dimension (wp_handle_upload filter).
     */
    public function maybe_resize( array $upload ): array {
        $image_types = [ 'image/jpeg', 'image/png', 'image/webp' ];
        if ( ! in_array( $upload['type'], $image_types, true ) ) {
            return $upload;
        }

        $file = $upload['file'];
        $size = @getimagesize( $file );

        if ( ! $size ) {
            return $upload;
        }

        $width  = $size[0];
        $height = $size[1];

        // No resize needed.
        if ( $width <= $this->max_dimension && $height <= $this->max_dimension ) {
            return $upload;
        }

        $editor = wp_get_image_editor( $file );

        if ( is_wp_error( $editor ) ) {
            return $upload;
        }

        $editor->resize( $this->max_dimension, $this->max_dimension, false );

        $saved = $editor->save( $file );

        if ( is_wp_error( $saved ) ) {
            return $upload;
        }

        // Update file path if extension changed.
        if ( $saved['path'] !== $file ) {
            $upload['file'] = $saved['path'];
        }

        return $upload;
    }
}
