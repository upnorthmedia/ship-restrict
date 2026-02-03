=== Ship Restrict ===
Contributors: upnorthmedia
Tags: shipping restrictions, shipping rules, shipping compliance, product restrictions, geo blocking
Requires at least: 5.6
Tested up to: 6.9
Requires PHP: 7.2
Stable tag: 1.2.9
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Control exactly where your products ship with powerful location-based restrictions by state, city, and zip code for WooCommerce stores.

== Description ==

**Ship Restrict** is the most comprehensive shipping restriction plugin for WooCommerce, giving you complete control over where your products can be shipped within the United States. Whether you need to comply with state regulations, manage regional distribution, or optimize your logistics, Ship Restrict provides the tools you need.

= Why Choose Ship Restrict? =

* **Regulatory Compliance** - Perfect for businesses with state-specific shipping requirements (alcohol, tobacco, CBD, firearms, etc.)
* **Regional Distribution Control** - Manage territory-based distribution agreements and exclusive dealer zones
* **Logistics Optimization** - Reduce shipping costs by limiting products to specific service areas
* **HPOS Compatible** - Fully compatible with WooCommerce High-Performance Order Storage
* **User-Friendly Interface** - Intuitive settings that integrate seamlessly with your existing WooCommerce workflow

= Key Features =

**🎯 Flexible Restriction Levels**
* Product-level restrictions for individual items
* Variation-level restrictions for specific product options
* Category-based rules for bulk management
* Tag-based rules for grouped products

**📍 Location Control Options**
* Restrict by state (all 50 US states)
* Restrict by city (case-insensitive matching)
* Restrict by zip code (exact match)
* Combine multiple restriction types for precise control

**⚡ Smart Cart Validation**
* Real-time validation during checkout
* Clear customer messaging for restricted items
* Automatic cart cleanup for restricted products
* Shipping method filtering based on restrictions

= Free Version Features =

The free version includes everything you need to get started:
* Create up to 2 shipping restriction rules
* Apply restrictions to up to 2 products
* Full state, city, and zip code restriction capabilities
* Category and tag-based restriction rules
* Product and variation-level restrictions
* Complete WooCommerce integration
* HPOS compatibility

= Pro Version Features =

Unlock unlimited potential with a Pro license:
* **Unlimited** shipping restriction rules
* **Unlimited** product restrictions
* Priority email support
* Advanced caching for improved performance
* Bulk restriction management tools
* Export/import restriction settings
* All free features included

The Pro version requires a license key from [Ship Restrict](https://shiprestrict.com/#pricing). When activating a Pro license, your site URL and a unique device identifier are sent to KeyForge for license validation purposes only.

= Perfect For =

* **Regulated Industries** - Alcohol, tobacco, CBD, supplements, firearms
* **Regional Businesses** - Local delivery services, regional distributors
* **Subscription Boxes** - Geographic-specific product offerings
* **B2B Operations** - Territory management and dealer exclusivity
* **Dropshippers** - Supplier-based shipping limitations
* **Fresh/Frozen Goods** - Temperature-controlled delivery zones

= How It Works =

1. **Set Your Rules** - Create restriction rules based on categories, tags, or individual products
2. **Define Locations** - Specify which states, cities, or zip codes are restricted
3. **Automatic Enforcement** - Ship Restrict validates all orders automatically
4. **Customer Communication** - Clear messages inform customers about shipping restrictions

== Installation ==

= Automatic Installation (Recommended) =

1. Log in to your WordPress admin dashboard
2. Navigate to **Plugins > Add New**
3. Search for "Ship Restrict"
4. Click **Install Now** and then **Activate**
5. Go to **WooCommerce > Ship Restrict** to configure your settings

= Manual Installation =

1. Download the plugin ZIP file from WordPress.org
2. Log in to your WordPress admin dashboard
3. Navigate to **Plugins > Add New > Upload Plugin**
4. Choose the downloaded ZIP file and click **Install Now**
5. Activate the plugin through the 'Plugins' menu
6. Go to **WooCommerce > Ship Restrict** to configure your settings

= First-Time Setup =

1. After activation, navigate to **WooCommerce > Ship Restrict**
2. Click **Add New Rule** to create your first restriction
3. Select the restriction type (Category or Tag)
4. Choose the specific category/tag to restrict
5. Add restricted locations (states, cities, zip codes)
6. Save your settings

= Pro License Activation =

1. Purchase a license key from [Ship Restrict Pro](https://shiprestrict.com/#pricing)
2. Go to **WooCommerce > Ship Restrict > License**
3. Enter your license key and click **Activate**
4. Enjoy unlimited restrictions and premium features

== Frequently Asked Questions ==

= Does this plugin work with all WooCommerce shipping methods? =

Yes! Ship Restrict works with all WooCommerce shipping methods including flat rate, free shipping, local pickup, and third-party shipping plugins. The plugin validates restrictions at the cart level, preventing restricted products from being purchased regardless of the shipping method selected.

= Can I restrict shipping to specific zip codes? =

Absolutely! You can restrict shipping by state, city, zip code, or any combination of these. The plugin supports exact zip code matching and you can add multiple zip codes to a single restriction rule.

= What happens when a customer tries to ship to a restricted location? =

When a customer with a restricted location tries to checkout, they'll see a clear message explaining which products cannot be shipped to their address. The restricted products are automatically removed from their cart, and they can continue shopping for other available items.

= Can I set different restrictions for product variations? =

Yes! Ship Restrict supports variation-level restrictions. For example, you could restrict a red shirt to certain states while allowing the blue shirt to ship everywhere. Variation restrictions take priority over product-level restrictions.

= Is the plugin GDPR compliant? =

Yes. The free version does not collect any personal data. The Pro version only sends your site URL and a device identifier to KeyForge for license validation - no customer data is ever transmitted or stored externally.

= How do I upgrade from free to Pro? =

Simply purchase a license key from our website and enter it in the plugin settings. Your existing restrictions and settings will be preserved, and you'll immediately have access to unlimited rules and products.

= Can I use this plugin for international shipping restrictions? =

Currently, Ship Restrict is optimized for United States shipping restrictions. International shipping restriction support is planned for a future update. For now, you can use WooCommerce's built-in shipping zones for country-level restrictions.

= Does the plugin slow down my site? =

No! Ship Restrict is highly optimized with intelligent caching mechanisms. Restriction checks are only performed during cart validation and checkout, with minimal impact on page load times. The Pro version includes additional performance optimizations.

= Can I export and import my restriction settings? =

The Pro version includes export/import functionality for backing up your restriction rules or migrating them between sites. This feature is perfect for agencies managing multiple client stores.

= Where can I get support? =

Free version users can get support through the WordPress.org support forums. Pro license holders receive priority email support directly from our team at [https://shiprestrict.com/#contact](https://shiprestrict.com/#contact).

== Screenshots ==

1. **Main Settings Page** - Overview of all active shipping restriction rules with easy management options
2. **Create New Rule** - Intuitive interface for creating category or tag-based restriction rules
3. **Product Restrictions Tab** - Set restrictions directly on the product edit page in WooCommerce
4. **Variation Restrictions** - Configure unique restrictions for each product variation
5. **State Selection Interface** - User-friendly state selector with select all/none options
6. **City Restrictions** - Add multiple cities with easy-to-use tag input interface
7. **Customer Cart Message** - Clear messaging when products are restricted from shipping
8. **License Activation** - Simple Pro license activation interface

== Privacy Policy ==

This plugin stores restriction settings in your WordPress database using the WordPress Options API and post meta system. 

**Data Storage:**
* Restriction rules are stored in the `spsr_settings` option
* Product-specific restrictions are stored as post meta
* Device ID for Pro licensing stored in `spsr_device_id` option

**Pro Version Data Transmission:**
When validating a Pro license key, the following data is sent to KeyForge (https://keyforge.dev):
* Your website URL
* A unique device identifier (UUID)
* The license key for validation

This data is used solely for license validation and is not shared with third parties. No customer personal data or order information is ever collected or transmitted.

**GDPR Compliance:**
* Users can request deletion of all plugin data
* No personal customer data is collected
* All data remains on your server (except Pro license validation)

For more information, visit our [Privacy Policy](https://shiprestrict.com/privacy).

== Additional Resources ==

* [Plugin Homepage](https://shiprestrict.com)
* [Documentation](https://shiprestrict.com/docs)
* [Pro Version](https://shiprestrict.com/#pricing)
* [Support Forum](https://wordpress.org/support/plugin/ship-restrict/)
