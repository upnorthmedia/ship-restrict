# Advanced Product Shipping Restrictions Plugin  
## Architecture & Functionality Overview

---

## 1. High-Level Architecture

- **Single-File Plugin:**  
  All logic resides in [simplified-product-shipping-restrictions.php](mdc:simplified-product-shipping-restrictions.php). The plugin is encapsulated in the `Simplified_Product_Shipping_Restrictions` singleton class.

- **WordPress & WooCommerce Integration:**  
  - Hooks into both admin and frontend WooCommerce actions/filters.
  - Uses WordPress Settings API for configuration.
  - Stores product/variation restriction data as post meta.
  - Stores category/tag-based restriction rules as an array/object in plugin settings (no custom DB table).
  - Declares HPOS (High-Performance Order Storage) compatibility.

---

## 2. Main Components

### a. Initialization & Constants
- **Singleton Pattern:**  
  Ensures only one instance of the main class.
- **Constants:**  
  Defines plugin paths, version, and URLs for internal use.

### b. Admin Functionality
- **Settings Page:**  
  - Adds a submenu under WooCommerce for restriction settings.
  - Allows admin to set a global error message (now a large textarea).
  - Provides a form to create restriction rules:
    - Select taxonomy type (category or tag)
    - Select a single category or tag
    - Enter one or more states, cities, or zip codes (comma-separated)
    - Add rule button
  - Displays all rules in a table with delete action.
  - Help/information section:
    - Explains how to use tags/categories for bulk restriction.
    - Notes that individual product/variation rules are set on the product edit page.

- **Product/Variation Meta Fields:**  
  - Adds custom fields to product and variation edit screens for restricted states, cities, and ZIP codes.
  - Saves these fields as post meta.

### c. Frontend Functionality
- **Cart Validation:**  
  - On checkout, checks each cart item against shipping address (state, city, ZIP).
  - Checks if the product belongs to a restricted category or tag for the destination (using rules from plugin settings).
  - If a parent product is restricted, all its variations inherit the rule unless overridden.
  - If a restriction is matched, adds an error notice and blocks checkout.
- **Shipping Method Filtering:**  
  - During shipping rate calculation, removes all shipping methods if any cart item is restricted for the destination.

### d. Restriction Logic
- **Restriction Hierarchy:**  
  1. **Variation-level** (if present):  
     - Checks states, cities, ZIPs (set on product edit page).
  2. **Product-level:**  
     - Checks states, cities, ZIPs (set on product edit page).
  3. **Category/Tag-level:**  
     - Checks if product belongs to a restricted category or tag for the destination (from rules in plugin settings).
- **Case Handling:**  
  - All checks are case-insensitive for cities.
  - All user input is sanitized before saving.

### e. HPOS Compatibility
- **WooCommerce HPOS:**  
  - Declares compatibility using `FeaturesUtil::declare_compatibility`.
  - Does not interact directly with order tables; uses WooCommerce APIs.

---

## 3. Key Hooks & Filters

- **Admin:**
  - `admin_init`, `admin_menu` for settings.
  - `woocommerce_product_options_shipping`, `woocommerce_process_product_meta` for product fields.
  - `woocommerce_product_after_variable_attributes`, `woocommerce_save_product_variation` for variation fields.

- **Frontend:**
  - `woocommerce_check_cart_items` for cart validation.
  - `woocommerce_package_rates` for shipping method filtering.

---

## 4. Data Storage

- **Global Settings:**  
  Stored in the `spsr_settings` option (error message, restriction rules array/object).
- **Category/Tag Restriction Rules:**  
  Each rule is an object with:
  - `type`: 'category' or 'tag'
  - `term_id`: the selected category or tag ID
  - `states`: array of state codes
  - `cities`: array of cities
  - `zip_codes`: array of zip codes
- **Product/Variation Restrictions:**  
  Stored as post meta:
  - `_restricted_states`
  - `_restricted_cities`
  - `_restricted_zip_codes`

---

## 5. Security & Best Practices

- **Direct Access Prevention:**  
  `defined('ABSPATH') || exit;`
- **Sanitization:**  
  All user input is sanitized before saving to the database.
- **Internationalization:**  
  All user-facing strings are translatable.

---

## 6. Extensibility

- **Hooks/Filters:**  
  Uses WooCommerce and WordPress hooks for extensibility.
- **No Custom Tables:**  
  Relies on standard WordPress/WooCommerce storage.  
  **Scalability Note:** If rule volume or complexity grows, consider migrating to a custom table for performance and advanced querying.

---

## 7. Compatibility

- **WordPress:**  
  Requires WP 5.6+ and PHP 7.2+.
- **WooCommerce:**  
  Requires WC 5.0+, tested up to 9.8.
- **HPOS:**  
  Fully compatible.

---

## 8. User Guidance & Notes

- For bulk restriction, use tags or categories and set up rules in the settings page.
- For individual product or variation restrictions, go to the product 'edit' page and set them there.
- For large catalogs, consider tagging products by restriction type (e.g., "Magazine Capacity Limit") or by state code (e.g., "CA") for easier management.

---

## 9. Licensing & Pro Feature Access

- **KeyForge Licensing Integration:**
  - APSR Pro requires a valid license key, managed via KeyForge.
  - License key input field and upgrade notice are displayed at the top of the settings page.
  - All rule management and product/variation restriction UI are blocked until a valid license is entered and validated.
  - License key is stored in the `spsr_settings` option (visible, not masked).
  - License validation is performed on settings save; result is cached for 24 hours.
  - Only one license key is allowed per WooCommerce site (enforced via device identifier).
  - Upgrade link points to `https://example.com/upgrade`.
  - If the license is missing or invalid, a dismissible but blocking notice is shown, and all Pro features are inaccessible.
  - All changes follow WP/WC best practices and are fully documented in this file.
  - A "Manage License in KeyForge Portal" link is shown when a license key is present, allowing users to self-service license management.
  - Validation/activation logic uses a per-site UUID device identifier, stored in `spsr_device_id`, and communicates with KeyForge `/activate` and `/validate` endpoints.
  - If the KeyForge API is unreachable, the last known valid status is respected for up to 24 hours to avoid blocking stores during outages.