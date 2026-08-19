<?php

namespace AgoLab\Media\Admin;

defined( 'ABSPATH' ) || exit;

class Page {

    public static function render(): void {
        ?>
        <div class="wrap">
            <h1>
                <img src="<?php echo esc_url( AGOMEDIA_URL . 'assets/img/agolab.webp' ); ?>" alt="aGo Lab" style="height:28px;width:auto;vertical-align:middle;margin-right:8px">
                <?php esc_html_e( 'aGo Media', 'ago-media' ); ?>
                <span style="font-size:12px;color:#999;margin-left:8px">v<?php echo esc_html( AGOMEDIA_VERSION ); ?></span>
            </h1>

            <div class="ago-layout">
                <div class="ago-main">

                    <div class="card ago-card">
                        <h2><?php esc_html_e( 'Optimization Settings', 'ago-media' ); ?></h2>
                        <p><?php esc_html_e( 'Configure image optimization for new uploads. Changes are saved immediately.', 'ago-media' ); ?></p>

                        <div id="ago-media-status" style="display:none"></div>

                        <div class="ago-section">
                            <h3><?php esc_html_e( 'Conversion', 'ago-media' ); ?></h3>

                            <label class="ago-toggle-row">
                                <span class="ago-toggle-label">
                                    <strong><?php esc_html_e( 'WebP Conversion', 'ago-media' ); ?></strong>
                                    <span class="ago-toggle-desc">
                                        <?php esc_html_e( 'Convert JPG/PNG to WebP on upload for smaller file sizes', 'ago-media' ); ?>
                                        <?php if ( ! \AgoLab\Media\Converter::is_webp_supported() ) : ?>
                                            <span class="ago-warning"><?php esc_html_e( '(WebP not supported by server)', 'ago-media' ); ?></span>
                                        <?php endif; ?>
                                    </span>
                                </span>
                                <span class="ago-switch">
                                    <input type="checkbox" data-key="enable_webp">
                                    <span class="ago-slider"></span>
                                </span>
                            </label>

                            <div class="ago-range-row" id="ago-quality-row">
                                <label for="ago-webp-quality">
                                    <strong><?php esc_html_e( 'WebP Quality', 'ago-media' ); ?></strong>
                                </label>
                                <div class="ago-range-control">
                                    <input type="range" id="ago-webp-quality" data-key="webp_quality" min="1" max="100" step="1">
                                    <span id="ago-quality-value" class="ago-range-value">82</span>
                                </div>
                            </div>
                        </div>

                        <div class="ago-section">
                            <h3><?php esc_html_e( 'Resize & Metadata', 'ago-media' ); ?></h3>

                            <label class="ago-toggle-row">
                                <span class="ago-toggle-label">
                                    <strong><?php esc_html_e( 'Auto-Resize', 'ago-media' ); ?></strong>
                                    <span class="ago-toggle-desc"><?php esc_html_e( 'Resize images exceeding max dimension (proportional)', 'ago-media' ); ?></span>
                                </span>
                                <span class="ago-switch">
                                    <input type="checkbox" data-key="enable_resize">
                                    <span class="ago-slider"></span>
                                </span>
                            </label>

                            <div class="ago-input-row" id="ago-dimension-row">
                                <label for="ago-max-dimension">
                                    <strong><?php esc_html_e( 'Max Dimension (px)', 'ago-media' ); ?></strong>
                                </label>
                                <input type="number" id="ago-max-dimension" data-key="max_dimension" min="800" max="10000" step="1" value="2560" class="small-text">
                            </div>

                            <label class="ago-toggle-row">
                                <span class="ago-toggle-label">
                                    <strong><?php esc_html_e( 'Strip EXIF', 'ago-media' ); ?></strong>
                                    <span class="ago-toggle-desc"><?php esc_html_e( 'Remove EXIF metadata (GPS, camera info) from uploaded images', 'ago-media' ); ?></span>
                                </span>
                                <span class="ago-switch">
                                    <input type="checkbox" data-key="strip_exif">
                                    <span class="ago-slider"></span>
                                </span>
                            </label>
                        </div>

                        <div class="ago-actions">
                            <button id="ago-save-settings" class="button button-primary" type="button">
                                <?php esc_html_e( 'Save Settings', 'ago-media' ); ?>
                            </button>
                        </div>
                    </div>

                    <div class="card ago-card ago-stats-card">
                        <h2><?php esc_html_e( 'Optimization Stats', 'ago-media' ); ?></h2>
                        <div class="ago-stats-grid">
                            <div class="ago-stat">
                                <span class="ago-stat-number" id="ago-stat-converted">-</span>
                                <span class="ago-stat-label"><?php esc_html_e( 'Images Converted', 'ago-media' ); ?></span>
                            </div>
                            <div class="ago-stat">
                                <span class="ago-stat-number" id="ago-stat-saved">-</span>
                                <span class="ago-stat-label"><?php esc_html_e( 'Space Saved', 'ago-media' ); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="card ago-card">
                        <h2><?php esc_html_e( 'Media Audit', 'ago-media' ); ?></h2>
                        <p><?php esc_html_e( 'Click a tab to scan your media library for issues.', 'ago-media' ); ?></p>

                        <div class="ago-tabs">
                            <button class="ago-tab active" data-tab="missing-alt">
                                <?php esc_html_e( 'Missing ALT', 'ago-media' ); ?>
                                <span class="ago-tab-count" id="ago-count-alt"></span>
                            </button>
                            <button class="ago-tab" data-tab="orphaned">
                                <?php esc_html_e( 'Orphaned', 'ago-media' ); ?>
                                <span class="ago-tab-count" id="ago-count-orphaned"></span>
                            </button>
                            <button class="ago-tab" data-tab="duplicates">
                                <?php esc_html_e( 'Duplicates', 'ago-media' ); ?>
                                <span class="ago-tab-count" id="ago-count-duplicates"></span>
                            </button>
                            <button class="ago-tab" data-tab="non-webp">
                                <?php esc_html_e( 'Optimize to WebP', 'ago-media' ); ?>
                                <span class="ago-tab-count" id="ago-count-nonwebp"></span>
                            </button>
                        </div>

                        <div class="ago-tab-content active" id="ago-panel-missing-alt">
                            <div class="ago-audit-loading"><?php esc_html_e( 'Loading...', 'ago-media' ); ?></div>
                            <table class="widefat ago-audit-table" style="display:none">
                                <thead>
                                    <tr>
                                        <th style="width:60px"></th>
                                        <th><?php esc_html_e( 'Title', 'ago-media' ); ?></th>
                                        <th><?php esc_html_e( 'ID', 'ago-media' ); ?></th>
                                        <th><?php esc_html_e( 'Actions', 'ago-media' ); ?></th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                            <div class="ago-audit-empty" style="display:none">
                                <?php esc_html_e( 'No issues found.', 'ago-media' ); ?>
                            </div>
                        </div>

                        <div class="ago-tab-content" id="ago-panel-orphaned">
                            <div class="ago-audit-loading"><?php esc_html_e( 'Loading...', 'ago-media' ); ?></div>
                            <table class="widefat ago-audit-table" style="display:none">
                                <thead>
                                    <tr>
                                        <th style="width:60px"></th>
                                        <th><?php esc_html_e( 'Title', 'ago-media' ); ?></th>
                                        <th><?php esc_html_e( 'ID', 'ago-media' ); ?></th>
                                        <th><?php esc_html_e( 'Actions', 'ago-media' ); ?></th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                            <div class="ago-audit-empty" style="display:none">
                                <?php esc_html_e( 'No issues found.', 'ago-media' ); ?>
                            </div>
                        </div>

                        <div class="ago-tab-content" id="ago-panel-duplicates">
                            <div class="ago-audit-loading"><?php esc_html_e( 'Loading...', 'ago-media' ); ?></div>
                            <div class="ago-duplicates-list" style="display:none"></div>
                            <div class="ago-audit-empty" style="display:none">
                                <?php esc_html_e( 'No issues found.', 'ago-media' ); ?>
                            </div>
                        </div>

                        <div class="ago-tab-content" id="ago-panel-non-webp">
                            <p style="font-size:13px;color:#666;margin-top:0">
                                <?php esc_html_e( 'JPG/PNG images that can be converted to WebP to save space. Select images and click "Optimize Selected".', 'ago-media' ); ?>
                            </p>
                            <div class="ago-audit-loading"><?php esc_html_e( 'Loading...', 'ago-media' ); ?></div>
                            <div class="ago-optimize-actions" style="display:none;margin-bottom:12px">
                                <label><input type="checkbox" id="ago-select-all-webp"> <?php esc_html_e( 'Select All', 'ago-media' ); ?></label>
                                <button type="button" class="button button-primary" id="ago-optimize-selected">
                                    <?php esc_html_e( 'Optimize Selected', 'ago-media' ); ?>
                                </button>
                                <span id="ago-optimize-progress" style="margin-left:8px;font-size:13px;color:#666"></span>
                            </div>
                            <table class="widefat ago-audit-table" id="ago-nonwebp-table" style="display:none">
                                <thead>
                                    <tr>
                                        <th style="width:30px"></th>
                                        <th style="width:60px"></th>
                                        <th><?php esc_html_e( 'Title', 'ago-media' ); ?></th>
                                        <th><?php esc_html_e( 'Type', 'ago-media' ); ?></th>
                                        <th><?php esc_html_e( 'Size', 'ago-media' ); ?></th>
                                        <th><?php esc_html_e( 'Status', 'ago-media' ); ?></th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                            <div class="ago-audit-empty" style="display:none">
                                <?php esc_html_e( 'All images are already WebP! Nothing to optimize.', 'ago-media' ); ?>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="ago-sidebar">

                    <div class="card ago-card">
                        <h3><?php esc_html_e( 'About', 'ago-media' ); ?></h3>
                        <p style="font-size:13px;color:#666">
                            <?php esc_html_e( 'Automatic image optimization on upload and media library auditing tools.', 'ago-media' ); ?>
                        </p>
                        <ul class="ago-features">
                            <li><?php esc_html_e( 'WebP conversion on upload', 'ago-media' ); ?></li>
                            <li><?php esc_html_e( 'Auto-resize large images', 'ago-media' ); ?></li>
                            <li><?php esc_html_e( 'Strip EXIF metadata', 'ago-media' ); ?></li>
                            <li><?php esc_html_e( 'Audit: missing ALT text', 'ago-media' ); ?></li>
                            <li><?php esc_html_e( 'Audit: orphaned media', 'ago-media' ); ?></li>
                            <li><?php esc_html_e( 'Audit: duplicate images', 'ago-media' ); ?></li>
                            <li><?php esc_html_e( 'Track space saved', 'ago-media' ); ?></li>
                        </ul>
                        <p style="margin-top:12px">
                            <a href="https://ago.cl/herramientas/wordpress/ago-media/docs" target="_blank" rel="noopener">
                                <span class="dashicons dashicons-book" style="vertical-align:middle;margin-right:4px"></span>
                                <?php esc_html_e( 'Documentation', 'ago-media' ); ?>
                            </a>
                        </p>
                    </div>

                    <div class="card ago-card ago-donation">
                        <h3><?php esc_html_e( 'Support Open Source', 'ago-media' ); ?></h3>
                        <p style="font-size:13px;color:#666">
                            <?php esc_html_e( 'If this plugin saves you time, consider supporting our open-source work.', 'ago-media' ); ?>
                        </p>
                        <div class="ago-donation-amounts">
                            <a href="https://paypal.me/sixtovaldes/3" class="ago-amount" target="_blank" rel="noopener">$3</a>
                            <a href="https://paypal.me/sixtovaldes/5" class="ago-amount" target="_blank" rel="noopener">$5</a>
                            <a href="https://paypal.me/sixtovaldes/10" class="ago-amount" target="_blank" rel="noopener">$10</a>
                        </div>
                        <a href="https://paypal.me/sixtovaldes" class="ago-coffee-btn" target="_blank" rel="noopener">
                            <span class="dashicons dashicons-coffee" style="margin-right:6px"></span>
                            <?php esc_html_e( 'Buy us a coffee', 'ago-media' ); ?>
                        </a>
                        <p class="ago-donation-note">
                            <?php esc_html_e( 'Voluntary donation. Thank you!', 'ago-media' ); ?>
                        </p>
                    </div>

                    <div class="ago-footer">
                        <a href="https://ago.cl" target="_blank" rel="noopener" class="ago-footer-logo">
                            <img src="<?php echo esc_url( AGOMEDIA_URL . 'assets/img/agolab.webp' ); ?>" alt="aGo Lab" style="height:40px;width:auto">
                        </a>
                        <p>
                            <?php
                            echo wp_kses_post(
                                sprintf(
                                    /* translators: 1: heart icon HTML, 2: aGo Lab link HTML */
                                    __( 'Developed with %1$s by %2$s', 'ago-media' ),
                                    '<span style="color:#e25555">&#10084;</span>',
                                    '<a href="https://ago.cl" target="_blank" rel="noopener"><strong>aGo Lab</strong></a>'
                                )
                            );
                            ?>
                        </p>
                        <p style="font-size:11px;color:#999">
                            <?php esc_html_e( 'Building tools for the web, one plugin at a time.', 'ago-media' ); ?>
                        </p>
                    </div>

                </div>
            </div>

        </div>
        <?php
    }
}
