<?php
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'agomedia_settings' );
delete_option( 'agomedia_stats' );
delete_transient( 'agomedia_audit_missing_alt' );
delete_transient( 'agomedia_audit_orphaned' );
delete_transient( 'agomedia_audit_duplicates' );
