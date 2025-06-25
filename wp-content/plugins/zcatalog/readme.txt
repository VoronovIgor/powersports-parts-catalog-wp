=== ZCatalog ===
Contributors: igorvoronov
Tags: catalog, spare parts, powersports, ecommerce
Requires at least: 6.0
Tested up to: 6.6
Stable tag: 2.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Plugin URI: https://zapscript.net/powersports-e-catalogs
Author URI: https://zapscript.net
Author: Voronov Igor

Multi-level spare parts e-catalog (Groups → Brands → Years → Models → Bodies → Parts). Includes diagrams of spare parts for: ATVs, FL Models, Motorcycles, Personal Watercraft, Scooters, Side x Side, Snowmobiles, Utilities, Watercrafts, GEM® Electric, Slingshot, Lawn Tractor, Multi-Purpose Engine, Outdoor Power Equipment, Race Kart, Sport Boat, WaveRunner, Roadster, Boats.

== Description ==

ZCatalog is a lightweight, API‑driven plugin for displaying a spare-parts catalog for powersports vehicles directly on your WordPress site.

**Structure**:
* Groups → Brands → Years → Models → Bodies → Parts
* No external JS frameworks required
* Clean HTML markup
* Responsive CSS Grid layout

**Features**:
* Admin settings:
  - API key
  - Placeholder image
  - Part‑link template (e.g. `@article`)
* Shortcode support (coming soon)
* Customizable frontend styles via CSS
* Developer-friendly structure and hooks

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate **ZCatalog** through the “Plugins” screen.
3. Go to **Settings → ZCatalog** and enter your API key and other optional settings.

== Frequently Asked Questions ==

= Does the plugin require JavaScript frameworks like React or Vue? =
No. ZCatalog uses clean server-side rendering and a modern CSS grid for layout. No frameworks required.

= Can I customize how parts are linked? =
Yes, use the part-link template setting in the admin panel (e.g., `/shop/parts/@article`).

= Will it work with my theme? =
Yes, ZCatalog is theme-agnostic and styled using standard WordPress and CSS Grid practices.

== Screenshots ==

1. Admin Settings page (API key, placeholders)
2. Example catalog structure (Brands → Models → Parts)
3. Responsive layout on mobile devices

== Changelog ==

= 2.1.0 =
* Added activation/deactivation/uninstall hooks
* New: “Order” button for parts

== Upgrade Notice ==

= 2.1.0 =
This version introduces install/uninstall logic and improves frontend interactivity with the new order button.

== License ==

This plugin is licensed under the [GPLv2 or later](https://www.gnu.org/licenses/gpl-2.0.html).
