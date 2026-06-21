=== Pictomancer Image Optimizer ===
Contributors: pictomancerai
Tags: image optimization, webp, compression, performance, media library
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically compress and convert WordPress media through the Pictomancer.ai API for faster pages and lower bandwidth.

== Description ==

Pictomancer Image Optimizer automatically compresses and converts your images in WordPress and WooCommerce using the Pictomancer.ai optimization API. Enjoy faster load times, reduced bandwidth, and improved SEO. Features include customizable optimization profiles, a real-time dashboard, and advanced developer tools. Compatible with WebP, AVIF, WP-CLI, GraphQL, and more.

== Features ==

* Automatic optimization of images on upload (Media Library, WooCommerce, and more).
* Optimizes the original upload and every generated thumbnail size (can be limited to the original in settings).
* Plays nicely with thumbnail regeneration: regenerated sizes are re-optimized, the original is compressed only once.
* Batch optimization tool for existing media with admin progress view.
* Customizable optimization profiles: define presets for quality, dimensions, and formats.
* Dashboard: real media savings, size reduction, and live API health.
* Requeue failed optimizations automatically via WP Cron.
* Feature flags: enable experimental features from the admin UI.
* Debug mode: detailed logging for troubleshooting and development.
* Multisite aware: per-site configuration and optional global stats.
* WP-CLI commands: manage optimization tasks and settings from the command line.
* REST API and GraphQL integration (if WPGraphQL is active).
* Developer hooks: extensible architecture for custom workflows.
* Compatible with popular SEO plugins (Yoast, RankMath, All in One SEO).
* Advanced configuration for power users and enterprises.

== Installation ==

1. Download the plugin from https://pictomancer.ai or the official WordPress plugin directory.
2. In your WordPress admin, go to Plugins > Add New > Upload Plugin.
3. Select the ZIP file and click Install Now.
4. Activate the plugin.
5. Go to Pictomancer > Settings to configure your API endpoint, optimization profiles, and preferences.

== Frequently Asked Questions ==

= Is Pictomancer compatible with WooCommerce? =
Yes, it automatically optimizes product images and gallery images in WooCommerce.

= What formats are supported? =
WebP, AVIF, JPEG, PNG, and more. You can customize output formats in the settings.

= Can I create custom optimization profiles? =
Yes, you can define multiple profiles for different image types, quality levels, and dimensions.

= Does it support multisite? =
Yes, each site can have its own configuration, or you can share global stats.

= Is there a debug mode? =
Yes, enable debug mode in settings to log detailed optimization and API activity.

= Is it compatible with SEO plugins? =
Yes, it works alongside Yoast, RankMath, and All in One SEO. Because optimization runs before offload and caching plugins act, it also composes with WP Offload Media, WP Stateless, and your CDN without extra configuration.

= Can I set the API key outside the database (wp-config.php / environment)? =
Yes. Define the `PICTOMANCER_API_KEY` constant (and optionally `PICTOMANCER_API_URL`) in wp-config.php, for example `define( 'PICTOMANCER_API_KEY', getenv( 'PICTOMANCER_API_KEY' ) );`. When set, the constant takes precedence over the settings form, the field is shown read-only, and the secret is never stored in the database -- ideal for staging/production and version-controlled config. Leave it unset to manage the key from the admin form.

= Does it work with S3 / offloading plugins like WP Offload Media or WP Stateless? =
Yes, by design. Optimization runs right after WordPress generates the attachment sizes and before offloading plugins push files to your bucket, so the bytes that reach your S3/R2/GCS storage -- and therefore your CDN -- are already optimized. No extra configuration or cloud credentials are needed in Pictomancer.

= Does it improve SEO? =
Yes, it boosts loading speed and PageSpeed scores, which benefits your SEO and Core Web Vitals.

== External services ==

This plugin relies on one external service to function: the Pictomancer.ai image optimization API. This is the core service that performs the actual compression and format conversion; without it the plugin cannot optimize your media.

What is sent and when: each time an image is optimized (on upload, when WordPress generates thumbnail sizes, or when you run the batch tool), the image bytes are sent to the API endpoint (https://api.pictomancer.ai by default, or the URL you configure in the settings) together with your API key, if you have set one, and the selected compression quality. The API returns the optimized image, which is saved in place. No data about your site's visitors is collected or sent.

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
* Initial release with automatic and batch optimization, Magic Preview, customizable profiles, real-time dashboard, HTTP streaming, WP-CLI, GraphQL, developer hooks, debug mode, and multisite support.

== Upgrade Notice ==

= 0.1.0 =
First stable version.
