=== Ship Restrict ===
Contributors: upnorthmedia
Tags: woocommerce, shipping, restrictions, shipping-zones, ecommerce
Requires at least: 5.6
Tested up to: 6.8
Requires PHP: 7.2
Stable tag: 1.2.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Restrict products and variations from being shipped to specific states, cities, or zip codes based on configurable rules.

== Description ==

Ship Restrict allows WooCommerce store owners to control where specific products can be shipped within the United States. Set shipping restrictions by state, city, or zip code for individual products, variations, categories, or tags.

**Free Version Features:**
* Create up to 2 shipping restriction rules
* Apply restrictions to up to 2 products
* Restrict by state, city, or zip code
* Category and tag-based restrictions
* Product and variation-level restrictions
* Full WooCommerce compatibility

**Pro Version Features (with license key):**
* Unlimited shipping restriction rules
* Unlimited product restrictions
* Priority support
* All free features included

The Pro version requires a license key from KeyForge (https://keyforge.dev). When activating a Pro license, your site URL and a unique device identifier are sent to KeyForge for license validation purposes only.

== Installation ==
1. Upload the plugin files to the `/wp-content/plugins/ship-restrict` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Configure shipping restriction rules under WooCommerce > Ship Restrict.

== Changelog ==
= 1.2.1 =
* Updated readme.txt to be more accurate for submission

= 1.2.0 =
* Created public version for Wordpress Plugin Directory
* Updated and Improved Caching and License Management

= 1.1.5 =
* Updated code to match WP best practices
* Optimized array data for security and performance

= 1.1.0 =
* Updated Rule Builder 
* Implemented Updated WooCommerce API Calls for Cart Validation

= 1.0.0 =
* Initial release.

== Upgrade Notice ==
= 1.0.0 =
Initial stable release with full shipping restriction features.

== Frequently Asked Questions ==

= Does this plugin work with all shipping methods? =
Yes, Ship Restrict works with all WooCommerce shipping methods by preventing restricted products from being added to cart when shipping to restricted locations.

= Can I restrict shipping to specific zip codes? =
Yes, you can restrict shipping by state, city, zip code, or any combination of these.

= What happens when a customer tries to ship to a restricted location? =
Customers will see a clear message explaining that the product cannot be shipped to their location and the product will be removed from their cart.

= Is the plugin GDPR compliant? =
The free version does not collect any personal data. The Pro version only sends your site URL and a device identifier to KeyForge for license validation, no customer data is transmitted.

== Privacy Policy ==

This plugin stores restriction settings in your WordPress database using the WordPress Options API. 

For Pro version users: When validating a license key, your site URL and a unique device identifier are sent to KeyForge (https://keyforge.dev) for license validation only. No personal customer data is collected or transmitted.

== License ==
This plugin is licensed under the GPLv2 or later. 

== Links ==
* Plugin Homepage: https://shiprestrict.com
* Support: https://shiprestrict.com/#contact
* Pro License: https://shiprestrict.com/#pricing 
