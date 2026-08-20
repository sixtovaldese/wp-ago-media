=== aGo Media ===
Contributors: agolab
Donate link: https://paypal.me/sixtovaldes
Tags: webp, image optimization, media, alt text, resize
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Image optimization on upload (WebP, auto-resize, EXIF strip) and media auditing (missing ALT, orphaned, duplicates).

== Description ==

aGo Media optimizes images as they are uploaded to the WordPress media library: converts to WebP, resizes oversized originals to a sane maximum dimension, strips EXIF metadata. Includes an admin tab to audit existing media: images without ALT text, orphaned attachments not used in any post, duplicate files by filename.

**Features**

* Convert PNG and JPEG uploads to WebP automatically.
* Resize uploaded images to a maximum width or height.
* Strip EXIF metadata on upload.
* Audit: images without ALT text.
* Audit: orphaned attachments.
* Audit: duplicate detection by filename.
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

= What happens to the original file? =

When an image is converted to WebP, the original JPG or PNG is replaced by the new WebP file. There is no option to keep the original alongside it, so back up your media library beforehand if you need to preserve the source files.

== Screenshots ==

1. Media dashboard with optimization toggles.
2. Audit tab listing images without ALT text.
3. Orphaned attachments view.

== External services ==

This plugin does not connect to any external service. All image processing (WebP conversion, resize, EXIF stripping) runs locally with GD or Imagick. The donation links and the aGo Lab link in the admin page point to PayPal and ago.cl, opened only when the user clicks them.

== Privacy ==

The plugin stores two options (agomedia_settings, agomedia_stats) and short-lived audit transients. It sends no data to third parties. On uninstall, all options and transients are removed.

== Changelog ==

= 1.0.0 =
* Initial release.
* WebP conversion, auto-resize and EXIF stripping on upload.
* Bulk optimization of existing attachments.
* Audits for missing ALT text, orphaned media and duplicate filenames.
* English, Spanish and Brazilian Portuguese included.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
