<?php
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'ago_media_settings' );
delete_option( 'ago_media_stats' );
delete_transient( 'ago_media_audit_missing_alt' );
delete_transient( 'ago_media_audit_orphaned' );
delete_transient( 'ago_media_audit_duplicates' );
