=== aGo Media ===
Contributors: sixtovaldese
Donate link: https://paypal.me/sixtovaldes
Tags: webp, image optimization, media, alt text, resize
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Image optimization on upload (WebP, auto-resize, EXIF strip) and media auditing (missing ALT, orphaned, duplicates).

== Description ==

aGo Media optimizes images as they are uploaded to the WordPress media library: converts to WebP, resizes oversized originals to a sane maximum dimension, strips EXIF metadata. Includes an admin tab to audit existing media: images without ALT text, orphaned attachments not used in any post, near-duplicates by hash.

**Features**

* Convert PNG and JPEG uploads to WebP automatically.
* Resize uploaded images to a maximum width or height.
* Strip EXIF metadata on upload.
* Audit: images without ALT text.
* Audit: orphaned attachments.
* Audit: duplicate detection by hash.
* Optimize existing attachments in bulk.
* No external services. All processing happens on your server using GD or Imagick.
* English, Spanish (es_ES) and Brazilian Portuguese (pt_BR) bundled.

== Installation ==

1. Upload the `ago-media` folder to `/wp-content/plugins/`.
2. Activate the plugin.
3. Go to **aGo Tools, Media**. Toggle features and configure WebP quality and max dimension.

== Frequently Asked Questions ==

= Does it require Imagick? =

No. The plugin uses whichever image library WordPress is configured with: Imagick if available, GD otherwise.

= Can it convert existing images? =

Yes. The "Optimize existing" audit lists non-WebP attachments. Run them in batches.

= Does it delete the originals? =

By default, the original file is kept alongside the converted WebP. This preserves your ability to revert. Disk-space-conscious users can disable this in settings.

== Screenshots ==

1. Media dashboard with optimization toggles.
2. Audit tab listing images without ALT text.
3. Orphaned attachments view.

== Changelog ==

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
