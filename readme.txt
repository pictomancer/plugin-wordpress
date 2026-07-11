=== Pictomancer Image Optimizer ===
Contributors: pictomancerai, sonirico
Tags: image optimization, compression, performance, media library, images
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically compress WordPress media through the Pictomancer.ai API for faster pages and lower bandwidth.

== Description ==

Pictomancer Image Optimizer automatically compresses the images you upload to WordPress using the Pictomancer.ai optimization API. Smaller images mean faster load times, reduced bandwidth, and better Core Web Vitals.

The plugin hooks into the standard WordPress upload flow, so it works with anything that creates media library attachments -- the Media Library itself, WooCommerce product images, page builders, and so on.

= Features =

* Automatic optimization on upload: the original file and every generated thumbnail size are compressed through the API.
* Thumbnail control: limit optimization to the original upload from the settings if you prefer.
* Safe by design: an optimized file is only kept when it is actually smaller than the input, and the pristine `original_image` WordPress keeps for regeneration is never touched.
* Regeneration friendly: if you regenerate thumbnails, the new sizes are re-optimized while the main file is never compressed twice (no generation loss).
* Dashboard: real accumulated savings (bytes and percentage) and live API health, right in wp-admin.
* Debug mode: optional logging to a protected, non-guessable file for troubleshooting.
* Configuration via constants: define `PICTOMANCER_API_KEY` (and optionally `PICTOMANCER_API_URL`) in wp-config.php to keep secrets out of the database.
* Developer hooks: `pictomancer_log` action and `pictomancer_debug_logging_enabled` filter for custom workflows.

== Installation ==

1. Download the plugin from the WordPress plugin directory.
2. In your WordPress admin, go to Plugins > Add New > Upload Plugin.
3. Select the ZIP file and click Install Now.
4. Activate the plugin.
5. Go to the Pictomancer menu to configure your API key, compression quality, and preferences. Without an API key the plugin uses the Pictomancer.ai free tier.

== Frequently Asked Questions ==

= Does it work with WooCommerce? =
Yes. Product and gallery images are regular media library attachments, so they are optimized on upload like any other image.

= What image formats are supported? =
JPEG, PNG, WebP, TIFF, and GIF. Images are compressed in their original format; format conversion is not part of this version.

= Can I set the API key outside the database (wp-config.php / environment)? =
Yes. Define the `PICTOMANCER_API_KEY` constant (and optionally `PICTOMANCER_API_URL`) in wp-config.php, for example `define( 'PICTOMANCER_API_KEY', getenv( 'PICTOMANCER_API_KEY' ) );`. When set, the constant takes precedence over the settings form, the field is shown read-only, and the secret is never stored in the database -- ideal for staging/production and version-controlled config. Leave it unset to manage the key from the admin form.

= Does it work with S3 / offloading plugins like WP Offload Media or WP Stateless? =
Yes, by design. Optimization runs right after WordPress generates the attachment sizes and before offloading plugins push files to your bucket, so the bytes that reach your S3/R2/GCS storage -- and therefore your CDN -- are already optimized. No extra configuration or cloud credentials are needed in Pictomancer.

= Does it improve SEO? =
Smaller images improve loading speed and PageSpeed scores, which benefits your Core Web Vitals and therefore SEO.

= What happens if the API is unreachable? =
The original image is kept untouched and the upload completes normally. Optimization failures never break your media library.

== External services ==

This plugin relies on one external service to function: the Pictomancer.ai image optimization API. This is the core service that performs the actual compression; without it the plugin cannot optimize your media.

What is sent and when: each time an image is optimized (on upload or when WordPress generates thumbnail sizes), the image bytes are sent to the API endpoint (https://api.pictomancer.ai by default, or the URL you configure in the settings) together with your API key, if you have set one, and the selected compression quality. The API returns the optimized image, which is saved in place. No data about your site's visitors is collected or sent.

This service is provided by Pictomancer.ai. By using the plugin you agree to its terms and privacy policy:

* Terms of Service: https://pictomancer.ai/terms
* Privacy Policy: https://pictomancer.ai/privacy

== Source code ==

The admin interface bundle (build/pictomancer-admin.js) is compiled from React and TypeScript sources with Vite. The complete, human-readable source and the build tooling are publicly available at:

https://github.com/pictomancer/plugin-wordpress

To rebuild the bundle from source:

1. Install dependencies: `yarn install`
2. Build: `yarn build`

This regenerates build/pictomancer-admin.js.

== Changelog ==

= 0.1.0 =
* Initial release: automatic compression of uploads and thumbnails, savings dashboard with live API health, debug logging, wp-config constants for API credentials.

== Upgrade Notice ==

= 0.1.0 =
First stable version.
