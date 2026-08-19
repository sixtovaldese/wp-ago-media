<?php

namespace AgoLab\Media;

defined( 'ABSPATH' ) || exit;

class Audit {

    /**
     * Get images without ALT text.
     *
     * @return array<int, array{id: int, title: string, url: string, thumbnail_url: string, edit_url: string}>
     */
    public function get_missing_alt(): array {
        global $wpdb;

        // Get all image attachments that have no _wp_attachment_image_alt meta
        // or where the meta value is empty.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $ids = $wpdb->get_col(
            "SELECT p.ID
             FROM {$wpdb->posts} p
             LEFT JOIN {$wpdb->postmeta} pm
                 ON p.ID = pm.post_id AND pm.meta_key = '_wp_attachment_image_alt'
             WHERE p.post_type = 'attachment'
               AND p.post_mime_type LIKE 'image/%'
               AND p.post_status = 'inherit'
               AND (pm.meta_value IS NULL OR pm.meta_value = '')
             ORDER BY p.ID DESC
             LIMIT 200"
        );

        return $this->format_results( $ids );
    }

    /**
     * Get orphaned media (not attached to any post).
     *
     * @return array<int, array{id: int, title: string, url: string, thumbnail_url: string, edit_url: string}>
     */
    public function get_orphaned(): array {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $ids = $wpdb->get_col(
            "SELECT ID
             FROM {$wpdb->posts}
             WHERE post_type = 'attachment'
               AND post_mime_type LIKE 'image/%'
               AND post_status = 'inherit'
               AND post_parent = 0
             ORDER BY ID DESC
             LIMIT 200"
        );

        return $this->format_results( $ids );
    }

    /**
     * Get duplicate images (same filename, 2+ matches).
     *
     * @return array<string, array<int, array{id: int, title: string, url: string, thumbnail_url: string, edit_url: string}>>
     */
    public function get_duplicates(): array {
        global $wpdb;

        // Find filenames that appear more than once.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $rows = $wpdb->get_results(
            "SELECT pm.meta_value AS file_path, GROUP_CONCAT(pm.post_id ORDER BY pm.post_id) AS ids
             FROM {$wpdb->postmeta} pm
             INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
             WHERE pm.meta_key = '_wp_attached_file'
               AND p.post_type = 'attachment'
               AND p.post_mime_type LIKE 'image/%'
               AND p.post_status = 'inherit'
             GROUP BY SUBSTRING_INDEX(pm.meta_value, '/', -1)
             HAVING COUNT(*) > 1
             ORDER BY COUNT(*) DESC
             LIMIT 50"
        );

        $groups = [];
        foreach ( $rows as $row ) {
            $filename = basename( $row->file_path );
            $ids      = array_map( 'intval', explode( ',', $row->ids ) );
            $groups[ $filename ] = $this->format_results( $ids );
        }

        return $groups;
    }

    /* ───── Helpers ───── */

    /**
     * Format attachment IDs into structured result arrays.
     *
     * @param  array<int|string> $ids
     * @return array<int, array{id: int, title: string, url: string, thumbnail_url: string, edit_url: string}>
     */
    private function format_results( array $ids ): array {
        $results = [];

        foreach ( $ids as $id ) {
            $id = (int) $id;
            if ( ! $id ) {
                continue;
            }

            $url       = wp_get_attachment_url( $id );
            $thumb     = wp_get_attachment_image_url( $id, 'thumbnail' );
            $title     = get_the_title( $id );
            $edit_url  = get_edit_post_link( $id, 'raw' );

            $results[] = [
                'id'            => $id,
                'title'         => $title ?: '(' . __( 'untitled', 'ago-media' ) . ')',
                'url'           => $url ?: '',
                'thumbnail_url' => $thumb ?: '',
                'edit_url'      => $edit_url ?: admin_url( 'post.php?post=' . $id . '&action=edit' ),
            ];
        }

        return $results;
    }
}
