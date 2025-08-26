<?php
/**
 * Plugin Name: Ship Restrict
 * Plugin URI: https://shiprestrict.com
 * Description: Restrict products and variations from being shipped to specific states, cities, or zip codes based on configurable rules.
 * Version: 1.0.0
 * Author: UpNorth Media
 * Author URI: https://upnorthmedia.co
 * Text Domain: ship-restrict
 * Domain Path: /languages
 * License: GPLv2 or later
 * Requires at least: 5.6
 * Requires PHP: 7.2
 * WC requires at least: 5.0
 * WC tested up to: 9.8
 *
 * @package Ship_Restrict_Pro
 */

// KeyForge product identifier for APSR Pro licensing
if ( ! defined( 'SPSR_KEYFORGE_PRODUCT_ID' ) ) {
    define( 'SPSR_KEYFORGE_PRODUCT_ID', 'p_bg74trwu1aa8d801q35qri5z' );
}

// Cache and rate limiting constants
if ( ! defined( 'SPSR_CACHE_DURATION' ) ) {
    define( 'SPSR_CACHE_DURATION', 86400 ); // 24 hours
}
if ( ! defined( 'SPSR_RATE_LIMIT_MAX' ) ) {
    define( 'SPSR_RATE_LIMIT_MAX', 10 );
}
if ( ! defined( 'SPSR_RATE_LIMIT_WINDOW' ) ) {
    define( 'SPSR_RATE_LIMIT_WINDOW', 300 ); // 5 minutes
}

defined('ABSPATH') || exit;

/**
 * Main plugin class
 */
class APSR_Pro {
    /**
     * Plugin version
     *
     * @var string
     */
    const VERSION = '1.0.0';

    /**
     * Plugin singleton instance
     *
     * @var APSR_Pro
     */
    protected static $_instance = null;

    /**
     * Main plugin instance
     *
     * @return APSR_Pro
     */
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor
     */
    public function __construct() {
        $this->define_constants();
        $this->init_hooks();
        
        // Declare HPOS compatibility
        add_action('before_woocommerce_init', array($this, 'declare_hpos_compatibility'));
    }

    /**
     * Define plugin constants
     */
    private function define_constants() {
        $this->define('SPSR_PLUGIN_FILE', __FILE__);
        $this->define('SPSR_PLUGIN_BASENAME', plugin_basename(__FILE__));
        $this->define('SPSR_VERSION', self::VERSION);
        $this->define('SPSR_PLUGIN_DIR', plugin_dir_path(__FILE__));
        $this->define('SPSR_PLUGIN_URL', plugin_dir_url(__FILE__));
    }

    /**
     * Define constant if not already defined
     *
     * @param string $name Constant name.
     * @param mixed  $value Constant value.
     */
    private function define($name, $value) {
        if (!defined($name)) {
            define($name, $value);
        }
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Admin hooks
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
            add_action('admin_menu', array($this, 'admin_menu'));
            add_action('woocommerce_product_options_shipping', array($this, 'product_options'));
            add_action('woocommerce_process_product_meta', array($this, 'save_product_options'));
            add_action('woocommerce_product_after_variable_attributes', array($this, 'variation_options'), 10, 3);
            add_action('woocommerce_save_product_variation', array($this, 'save_variation_options'), 10, 2);
        }
        
        // Frontend validation hooks
        if (!is_admin()) {
            // Modern cart validation (recommended by WooCommerce docs)
            add_action('woocommerce_store_api_cart_errors', array($this, 'validate_cart_restrictions'), 10, 2);
            // Legacy cart validation (still supported)
            add_action('woocommerce_check_cart_items', array($this, 'legacy_cart_validation'));
        }
    }

    /**
     * Declare compatibility with HPOS
     */
    public function declare_hpos_compatibility() {
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        }
    }

    /**
     * Admin initialization
     */
    public function admin_init() {
    }

    /**
     * Add admin menu
     */
    public function admin_menu() {
        add_submenu_page(
            'woocommerce',
            __('Ship Restrict', 'ship-restrict'),
            __('Ship Restrict', 'ship-restrict'),
            'manage_woocommerce',
            'spsr-settings',
            array($this, 'settings_page')
        );
    }

    /**
     * Settings page
     */
    public function settings_page() {
        // Get settings
        $settings = get_option('spsr_settings', array());
        $message = isset($settings['message']) ? $settings['message'] : '';
        $rules = isset($settings['rules']) && is_array($settings['rules']) ? $settings['rules'] : array();
        $license_key = isset($settings['license_key']) ? $settings['license_key'] : '';
        $license_valid = isset($settings['license_valid']) ? (bool)$settings['license_valid'] : false;
        $license_last_checked = isset($settings['license_last_checked']) ? intval($settings['license_last_checked']) : 0;
        $license_error = isset($settings['license_error']) ? $settings['license_error'] : '';
        $now = time();
        $cache_valid = $license_valid && ($now - $license_last_checked < SPSR_CACHE_DURATION);
        $product_id = defined('SPSR_KEYFORGE_PRODUCT_ID') ? SPSR_KEYFORGE_PRODUCT_ID : '';
        $just_saved = false;
        $notice = '';
        $notice_type = '';

        // Handle add rule
        if (isset($_POST['spsr_add_rule']) && check_admin_referer('spsr_add_rule_action', 'spsr_add_rule_nonce')) {
            $rule_name = sanitize_text_field($_POST['spsr_rule_name'] ?? '');
            $rule_term = intval($_POST['spsr_rule_term'] ?? 0);
            
            // Auto-detect rule type based on selected term
            $rule_type = '';
            if ($rule_term) {
                $term = get_term($rule_term);
                if ($term && !is_wp_error($term)) {
                    if ($term->taxonomy === 'product_cat') {
                        $rule_type = 'category';
                    } elseif ($term->taxonomy === 'product_tag') {
                        $rule_type = 'tag';
                    }
                }
            }
            $rule_logic = in_array($_POST['spsr_rule_logic'] ?? 'block_from', array('block_from', 'allow_only'), true) ? $_POST['spsr_rule_logic'] : 'block_from';
            $rule_states = isset($_POST['spsr_rule_states']) && is_array($_POST['spsr_rule_states']) ? array_map('sanitize_text_field', $_POST['spsr_rule_states']) : array();
            
            // Handle state-city pairs
            $rule_state_cities = array();
            if (isset($_POST['spsr_state_cities']) && is_array($_POST['spsr_state_cities'])) {
                foreach ($_POST['spsr_state_cities'] as $pair) {
                    if (isset($pair['state'], $pair['city']) && !empty($pair['state']) && !empty($pair['city'])) {
                        $rule_state_cities[] = array(
                            'state' => sanitize_text_field($pair['state']),
                            'city' => sanitize_text_field($pair['city'])
                        );
                    }
                }
            }
            
            // Legacy cities removed - only use state-city pairs now
            $rule_cities = array(); // Keep empty for backward compatibility with existing data
            $rule_zip_codes = array_filter(array_map('trim', explode(',', $_POST['spsr_rule_zip_codes'] ?? '')));
            
            if ($rule_name && $rule_type && $rule_term) {
                $rules[] = array(
                    'name' => $rule_name,
                    'type' => $rule_type,
                    'term_id' => $rule_term,
                    'logic' => $rule_logic,
                    'states' => $rule_states,
                    'state_cities' => $rule_state_cities,
                    'cities' => $rule_cities, // Keep for backward compatibility
                    'zip_codes' => $rule_zip_codes,
                );
                $settings['rules'] = $rules;
                update_option('spsr_settings', $settings);
                $notice = __('Rule added successfully.', 'ship-restrict');
                $notice_type = 'success';
            } else {
                $notice = __('Failed to add rule. Please fill all required fields.', 'ship-restrict');
                $notice_type = 'error';
            }
        }
        // Handle delete rule
        foreach ($rules as $i => $rule) {
            if (isset($_POST['spsr_delete_rule'], $_POST['spsr_rule_index']) && intval($_POST['spsr_rule_index']) === $i && check_admin_referer('spsr_delete_rule_action_' . $i, 'spsr_delete_rule_nonce_' . $i)) {
                array_splice($rules, $i, 1);
                $settings['rules'] = $rules;
                update_option('spsr_settings', $settings);
                $notice = __('Rule deleted successfully.', 'ship-restrict');
                $notice_type = 'success';
                break;
            }
        }
        // Display admin notice if set
        if ($notice) {
            echo '<div class="notice notice-' . esc_attr($notice_type) . '"><p>' . esc_html($notice) . '</p></div>';
        }

        // License key form handling
        if (isset($_POST['spsr_save_license']) && check_admin_referer('spsr_save_license_action', 'spsr_save_license_nonce')) {
            $new_license_key = sanitize_text_field($_POST['spsr_license_key']);
            $activate = ($new_license_key !== $license_key || !$license_valid);
            $result = $this->keyforge_validate_license($new_license_key, $activate);
            $settings['license_key'] = $new_license_key;
            $settings['license_valid'] = $result['valid'];
            $settings['license_last_checked'] = $now;
            $settings['license_error'] = $result['error'];
            $settings['product_id'] = $result['product_id'];
            update_option('spsr_settings', $settings);
            $license_key = $new_license_key;
            $license_valid = $result['valid'];
            $license_last_checked = $now;
            $license_error = $result['error'];
            $cache_valid = $license_valid;
            $just_saved = true;
            // Refresh to prevent resubmission
            echo '<meta http-equiv="refresh" content="0">';
        } elseif ($license_key && (!$cache_valid || ($now - $license_last_checked) >= SPSR_CACHE_DURATION)) {
            // Revalidate if cache expired
            $result = $this->keyforge_validate_license($license_key, false);
            $settings['license_valid'] = $result['valid'];
            $settings['license_last_checked'] = $now;
            $settings['license_error'] = $result['error'];
            $settings['product_id'] = $result['product_id'];
            update_option('spsr_settings', $settings);
            $license_valid = $result['valid'];
            $license_last_checked = $now;
            $license_error = $result['error'];
            $cache_valid = $license_valid;
        }

        // Get categories and tags
        $categories = get_terms(array('taxonomy' => 'product_cat', 'hide_empty' => false));
        $tags = get_terms(array('taxonomy' => 'product_tag', 'hide_empty' => false));
        $us_states = function_exists('WC') ? WC()->countries->get_states('US') : array();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Ship Restrict Settings', 'ship-restrict'); ?></h1>

            <h2><?php esc_html_e('How to Use Ship Restrict', 'ship-restrict'); ?></h2>
            <ul>
                <li><?php esc_html_e('To restrict shipping for a group of products, use tags or categories and create a rule below.', 'ship-restrict'); ?></li>
                <li><?php esc_html_e('For large catalogs, consider tagging products by restriction type (e.g., "Magazine Capacity Limit") or by state code (e.g., "CA") for easier management.', 'ship-restrict'); ?></li>
                <li><?php esc_html_e('To set individual product or variation restrictions, go to the product "edit" page and set them there.', 'ship-restrict'); ?></li>
            </ul>
            <hr />

            <?php
            // Show license notification at the top ONLY if license is invalid
            if (!$license_valid) {
                echo '<div class="notice notice-error" style="margin-bottom:20px;">';
                echo '<strong>' . esc_html__('Ship Restrict requires a valid license key.', 'ship-restrict') . '</strong> ';
                echo '<a href="https://shiprestrict.com/#pricing" target="_blank">' . esc_html__('Upgrade to Pro', 'ship-restrict') . '</a> ';
                echo esc_html__('or enter your license key below.', 'ship-restrict');
                if ($license_error) {
                    echo '<br><span style="color:red;">' . esc_html($license_error) . '</span>';
                }
                echo '</div>';
            }
            ?>
            <form method="post" action="options.php">
                <?php settings_fields('spsr_settings'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php echo esc_html__('Error Message', 'ship-restrict'); ?></th>
                        <td>
                            <textarea name="spsr_settings[message]" rows="5" class="large-text"><?php echo esc_textarea($message); ?></textarea>
                            <p class="description"><?php echo esc_html__('Customize the message shown when a restricted product is in the cart. Use {product} to insert the product name. Leave blank for default.', 'ship-restrict'); ?></p>
                            <div style="margin-top: 10px; padding: 10px; border: 1px solid #ccd0d4; background-color: #f6f7f7;">
                                <strong><?php echo esc_html__('Example Notice (Default Format):', 'ship-restrict'); ?></strong><br>
                                <?php echo esc_html__('Some items in your cart cannot currently be shipped to your location:', 'ship-restrict'); ?>
                                <ul>
                                    <li><?php 
                                        $default_template = __('The {product} cannot currently be shipped to your location. Please remove from cart to continue.', 'ship-restrict');
                                        echo esc_html(str_replace('{product}', __('Example Product Name', 'ship-restrict'), $default_template)); 
                                    ?></li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>

            <hr />
            <h2><?php echo esc_html__('Restriction Rules', 'ship-restrict'); ?></h2>
            <form method="post">
                <?php wp_nonce_field('spsr_add_rule_action', 'spsr_add_rule_nonce'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php echo esc_html__('Rule Name', 'ship-restrict'); ?></th>
                        <td><input type="text" name="spsr_rule_name" class="regular-text" maxlength="100" required /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php echo esc_html__('Category/Tag', 'ship-restrict'); ?></th>
                        <td>
                            <select name="spsr_rule_term" required>
                                <option value=""><?php esc_html_e('Select a category or tag...', 'ship-restrict'); ?></option>
                                <optgroup label="<?php esc_attr_e('Categories', 'ship-restrict'); ?>">
                                    <?php foreach ($categories as $cat) {
                                        echo '<option value="' . esc_attr($cat->term_id) . '">' . esc_html($cat->name) . '</option>';
                                    } ?>
                                </optgroup>
                                <optgroup label="<?php esc_attr_e('Tags', 'ship-restrict'); ?>">
                                    <?php foreach ($tags as $tag) {
                                        echo '<option value="' . esc_attr($tag->term_id) . '">' . esc_html($tag->name) . '</option>';
                                    } ?>
                                </optgroup>
                            </select>
                            <p class="description"><?php esc_html_e('Choose which products this rule applies to by selecting a category or tag.', 'ship-restrict'); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php echo esc_html__('Rule Logic', 'ship-restrict'); ?></th>
                        <td>
                            <label>
                                <input type="radio" name="spsr_rule_logic" value="block_from" checked>
                                <?php esc_html_e('Block shipping to selected locations', 'ship-restrict'); ?>
                            </label><br>
                            <label>
                                <input type="radio" name="spsr_rule_logic" value="allow_only">
                                <?php esc_html_e('Allow shipping only to selected locations', 'ship-restrict'); ?>
                            </label>
                            <p class="description"><?php esc_html_e('Choose whether to block shipping to the selected locations or allow only to those locations.', 'ship-restrict'); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php echo esc_html__('States', 'ship-restrict'); ?></th>
                        <td>
                            <select name="spsr_rule_states[]" multiple size="6" class="large-text">
                                <?php foreach ($us_states as $code => $label) {
                                    echo '<option value="' . esc_attr($code) . '">' . esc_html($label) . ' (' . esc_html($code) . ')</option>';
                                } ?>
                            </select>
                            <p class="description"><?php esc_html_e('Hold Ctrl (Windows) or Command (Mac) to select multiple states.', 'ship-restrict'); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php echo esc_html__('Specific Cities', 'ship-restrict'); ?></th>
                        <td>
                            <div id="spsr-state-cities-container">
                                <div class="spsr-state-city-pair" style="margin-bottom: 10px;">
                                    <select name="spsr_state_cities[0][state]" style="width: 120px; margin-right: 10px;" class="spsr-state-select">
                                        <option value=""><?php esc_html_e('Select State', 'ship-restrict'); ?></option>
                                        <?php foreach ($us_states as $code => $label) {
                                            echo '<option value="' . esc_attr($code) . '">' . esc_html($code) . '</option>';
                                        } ?>
                                    </select>
                                    <input type="text" name="spsr_state_cities[0][city]" placeholder="<?php esc_attr_e('City name', 'ship-restrict'); ?>" style="width: 200px; margin-right: 10px;" class="spsr-city-input">
                                    <button type="button" class="button spsr-remove-city" style="display: none;"><?php esc_html_e('Remove', 'ship-restrict'); ?></button>
                                </div>
                            </div>
                            <button type="button" id="spsr-add-city" class="button"><?php esc_html_e('Add Another City', 'ship-restrict'); ?></button>
                            <p class="description"><?php esc_html_e('Add specific cities with their states to avoid confusion (e.g., Springfield, IL vs Springfield, MO).', 'ship-restrict'); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php echo esc_html__('ZIP Codes', 'ship-restrict'); ?></th>
                        <td>
                            <textarea name="spsr_rule_zip_codes" rows="2" class="large-text" placeholder="<?php esc_attr_e('60601, 90210, 10001', 'ship-restrict'); ?>"></textarea>
                            <p class="description"><?php esc_html_e('Enter ZIP codes separated by commas (spaces optional). Example: 60601, 90210, 10001', 'ship-restrict'); ?></p>
                        </td>
                    </tr>
                </table>
                <p><input type="submit" name="spsr_add_rule" class="button button-primary" value="<?php esc_attr_e('Add Rule', 'ship-restrict'); ?>" /></p>
            </form>

            <?php if (!empty($rules)) : ?>
                <h3><?php esc_html_e('Current Rules', 'ship-restrict'); ?></h3>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Name', 'ship-restrict'); ?></th>
                            <th><?php esc_html_e('Type', 'ship-restrict'); ?></th>
                            <th><?php esc_html_e('Category/Tag', 'ship-restrict'); ?></th>
                            <th><?php esc_html_e('States', 'ship-restrict'); ?></th>
                            <th><?php esc_html_e('Cities', 'ship-restrict'); ?></th>
                            <th><?php esc_html_e('ZIP Codes', 'ship-restrict'); ?></th>
                            <th><?php esc_html_e('Action', 'ship-restrict'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rules as $i => $rule) :
                            $term = get_term($rule['term_id'], $rule['type'] === 'category' ? 'product_cat' : 'product_tag');
                            
                            // Format cities display
                            $cities_display = '';
                            if (isset($rule['state_cities']) && is_array($rule['state_cities'])) {
                                $state_city_pairs = array();
                                foreach ($rule['state_cities'] as $pair) {
                                    if (isset($pair['state'], $pair['city'])) {
                                        $state_city_pairs[] = $pair['city'] . ', ' . $pair['state'];
                                    }
                                }
                                if (!empty($state_city_pairs)) {
                                    $cities_display = implode('; ', $state_city_pairs);
                                }
                            }
                            // Show legacy cities only if they exist (for existing rules)
                            if (isset($rule['cities']) && is_array($rule['cities']) && !empty($rule['cities'])) {
                                $legacy_cities = implode(', ', $rule['cities']);
                                if (!empty($cities_display)) {
                                    $cities_display .= '; ' . $legacy_cities;
                                } else {
                                    $cities_display = $legacy_cities;
                                }
                            }
                            
                            $rule_logic = isset($rule['logic']) ? $rule['logic'] : 'block_from';
                            $logic_display = $rule_logic === 'allow_only' ? __('Allow only', 'ship-restrict') : __('Block from', 'ship-restrict');
                            ?>
                            <tr>
                                <td><?php echo isset($rule['name']) ? esc_html($rule['name']) : ''; ?></td>
                                <td><?php echo esc_html(ucfirst($rule['type'])); ?> <small>(<?php echo esc_html($logic_display); ?>)</small></td>
                                <td><?php echo $term && !is_wp_error($term) ? esc_html($term->name) : esc_html__('(Deleted)', 'ship-restrict'); ?></td>
                                <td><?php echo esc_html(implode(', ', $rule['states'])); ?></td>
                                <td><?php echo esc_html($cities_display); ?></td>
                                <td><?php echo esc_html(implode(', ', $rule['zip_codes'])); ?></td>
                                <td>
                                    <form method="post" style="display:inline;">
                                        <?php wp_nonce_field('spsr_delete_rule_action_' . $i, 'spsr_delete_rule_nonce_' . $i); ?>
                                        <input type="hidden" name="spsr_rule_index" value="<?php echo esc_attr($i); ?>" />
                                        <input type="submit" name="spsr_delete_rule" class="button" value="<?php esc_attr_e('Delete', 'ship-restrict'); ?>" />
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <hr />
            <h2><?php echo esc_html__('License Status', 'ship-restrict'); ?></h2>
            <?php
            // If license is valid, show badge under License Status
            if ($license_valid) {
                echo '<div style="margin: 10px 0 20px 0; display: flex; align-items: center; gap: 12px;">';
                echo '<span style="display: inline-block; background: #46b450; color: #fff; padding: 4px 12px; border-radius: 12px; font-weight: 600; font-size: 14px;">' . esc_html__('Your Ship Restrict license is active.', 'ship-restrict') . '</span>';
                if ($license_key) {
                    $portal_url = 'https://shiprestrict.com/#pricing';
                    echo ' <a href="' . esc_url($portal_url) . '" target="_blank" style="margin-left:20px;">' . esc_html__('Manage License in KeyForge Portal', 'ship-restrict') . '</a>';
                }
                echo '</div>';
            }
            ?>
            <form method="post" style="margin-bottom:20px;">
                <?php wp_nonce_field('spsr_save_license_action', 'spsr_save_license_nonce'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php echo esc_html__('License Key', 'ship-restrict'); ?></th>
                        <td>
                            <input type="text" name="spsr_license_key" class="regular-text" value="<?php echo esc_attr($license_key); ?>" style="width:350px;" />
                            <p class="description"><?php echo esc_html__('Enter your Ship Restrict license key. One key per WooCommerce site.', 'ship-restrict'); ?></p>
                        </td>
                    </tr>
                </table>
                <p><input type="submit" name="spsr_save_license" class="button button-primary" value="<?php echo esc_attr__('Save License', 'ship-restrict'); ?>" /></p>
            </form>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            var cityIndex = 1;
            
            // Add new city pair
            $('#spsr-add-city').on('click', function() {
                var newPair = '<div class="spsr-state-city-pair" style="margin-bottom: 10px;">' +
                    '<select name="spsr_state_cities[' + cityIndex + '][state]" style="width: 120px; margin-right: 10px;" class="spsr-state-select">' +
                    '<option value=""><?php esc_html_e('Select State', 'ship-restrict'); ?></option>';
                <?php foreach ($us_states as $code => $label) { ?>
                newPair += '<option value="<?php echo esc_js($code); ?>"><?php echo esc_js($code); ?></option>';
                <?php } ?>
                newPair += '</select>' +
                    '<input type="text" name="spsr_state_cities[' + cityIndex + '][city]" placeholder="<?php esc_attr_e('City name', 'ship-restrict'); ?>" style="width: 200px; margin-right: 10px;" class="spsr-city-input">' +
                    '<button type="button" class="button spsr-remove-city"><?php esc_html_e('Remove', 'ship-restrict'); ?></button>' +
                    '</div>';
                    
                $('#spsr-state-cities-container').append(newPair);
                cityIndex++;
                updateRemoveButtons();
            });
            
            // Remove city pair
            $(document).on('click', '.spsr-remove-city', function() {
                $(this).closest('.spsr-state-city-pair').remove();
                updateRemoveButtons();
            });
            
            // Basic validation
            $(document).on('change', '.spsr-city-input', function() {
                var city = $(this).val().trim();
                var state = $(this).siblings('.spsr-state-select').val();
                
                if (city && !state) {
                    $(this).css('border-color', '#dc3545');
                    $(this).attr('title', '<?php esc_attr_e('Please select a state for this city', 'ship-restrict'); ?>');
                } else {
                    $(this).css('border-color', '');
                    $(this).removeAttr('title');
                }
            });
            
            $(document).on('change', '.spsr-state-select', function() {
                var state = $(this).val();
                var cityInput = $(this).siblings('.spsr-city-input');
                var city = cityInput.val().trim();
                
                if (city && !state) {
                    cityInput.css('border-color', '#dc3545');
                    cityInput.attr('title', '<?php esc_attr_e('Please select a state for this city', 'ship-restrict'); ?>');
                } else {
                    cityInput.css('border-color', '');
                    cityInput.removeAttr('title');
                }
            });
            
            // Form validation before submit
            $('form').on('submit', function(e) {
                var hasError = false;
                $('.spsr-state-city-pair').each(function() {
                    var state = $(this).find('.spsr-state-select').val();
                    var city = $(this).find('.spsr-city-input').val().trim();
                    
                    if ((state && !city) || (!state && city)) {
                        hasError = true;
                        $(this).find('select, input').css('border-color', '#dc3545');
                    }
                });
                
                if (hasError) {
                    alert('<?php esc_js(__('Please complete all state-city pairs or remove incomplete ones.', 'ship-restrict')); ?>');
                    e.preventDefault();
                    return false;
                }
            });
            
            function updateRemoveButtons() {
                var pairs = $('.spsr-state-city-pair');
                if (pairs.length > 1) {
                    $('.spsr-remove-city').show();
                } else {
                    $('.spsr-remove-city').hide();
                }
            }
            
            // Initialize
            updateRemoveButtons();
        });
        </script>
        
        <?php
    }

    /**
     * Sanitize settings
     *
     * @param array $input Settings input.
     * @return array
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        if (isset($input['message'])) {
            $sanitized['message'] = sanitize_textarea_field($input['message']);
        }
        if (isset($input['rules']) && is_array($input['rules'])) {
            $sanitized['rules'] = array();
            foreach ($input['rules'] as $rule) {
                $name = isset($rule['name']) ? sanitize_text_field($rule['name']) : '';
                $term_id = isset($rule['term_id']) ? intval($rule['term_id']) : 0;
                
                // Auto-detect type from term
                $type = '';
                if ($term_id) {
                    $term = get_term($term_id);
                    if ($term && !is_wp_error($term)) {
                        if ($term->taxonomy === 'product_cat') {
                            $type = 'category';
                        } elseif ($term->taxonomy === 'product_tag') {
                            $type = 'tag';
                        }
                    }
                }
                $states = isset($rule['states']) && is_array($rule['states']) ? array_map('sanitize_text_field', $rule['states']) : array();
                $cities = isset($rule['cities']) && is_array($rule['cities']) ? array_map('sanitize_text_field', $rule['cities']) : array();
                $zip_codes = isset($rule['zip_codes']) && is_array($rule['zip_codes']) ? array_map('sanitize_text_field', $rule['zip_codes']) : array();
                if ($name && $type && $term_id) {
                    $sanitized['rules'][] = array(
                        'name' => $name,
                        'type' => $type,
                        'term_id' => $term_id,
                        'logic' => isset($rule['logic']) && in_array($rule['logic'], array('block_from', 'allow_only'), true) ? $rule['logic'] : 'block_from',
                        'states' => $states,
                        'state_cities' => isset($rule['state_cities']) && is_array($rule['state_cities']) ? $rule['state_cities'] : array(),
                        'cities' => $cities,
                        'zip_codes' => $zip_codes,
                    );
                }
            }
        }
        return $sanitized;
    }

    /**
     * Add product options
     */
    public function product_options() {
        global $post;
        $us_states = function_exists('WC') ? WC()->countries->get_states('US') : array();
        $selected_states = get_post_meta($post->ID, '_restricted_states', true);
        // Migrate old string to array if needed
        if (!is_array($selected_states)) {
            $selected_states = array_filter(array_map('trim', explode(',', (string)$selected_states)));
            update_post_meta($post->ID, '_restricted_states', $selected_states);
        }
        echo '<p><label for="_restricted_states">' . esc_html__('Restricted States', 'ship-restrict') . '</label></p>';
        echo '<select id="_restricted_states" name="_restricted_states[]" multiple size="6" style="width:100%;max-width:400px;">';
        foreach ($us_states as $code => $label) {
            $selected = in_array($code, $selected_states, true) ? 'selected' : '';
            echo '<option value="' . esc_attr($code) . '" ' . $selected . '>' . esc_html($label) . ' (' . esc_html($code) . ')</option>';
        }
        echo '</select>';
        echo '<p class="description">' . esc_html__('Select one or more states where this product cannot be shipped.', 'ship-restrict') . '</p>';
        // Cities and ZIPs remain as textareas
        woocommerce_wp_textarea_input(
            array(
                'id'          => '_restricted_cities',
                'label'       => __('Restricted Cities', 'ship-restrict'),
                'placeholder' => __('Enter cities separated by commas', 'ship-restrict'),
                'desc_tip'    => true,
                'description' => __('Enter cities where this product cannot be shipped.', 'ship-restrict'),
            )
        );
        woocommerce_wp_textarea_input(
            array(
                'id'          => '_restricted_zip_codes',
                'label'       => __('Restricted ZIP Codes', 'ship-restrict'),
                'placeholder' => __('Enter ZIP codes separated by commas', 'ship-restrict'),
                'desc_tip'    => true,
                'description' => __('Enter ZIP codes where this product cannot be shipped.', 'ship-restrict'),
            )
        );
    }

    /**
     * Save product options
     *
     * @param int $post_id Product ID.
     */
    public function save_product_options($post_id) {
        // Save states as array
        if (isset($_POST['_restricted_states']) && is_array($_POST['_restricted_states'])) {
            $states = array_map('sanitize_text_field', $_POST['_restricted_states']);
            update_post_meta($post_id, '_restricted_states', $states);
        } else {
            delete_post_meta($post_id, '_restricted_states');
        }
        // Save cities and ZIPs as before
        $fields = array('_restricted_cities', '_restricted_zip_codes');
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_textarea_field(wp_unslash($_POST[$field])));
            }
        }
    }

    /**
     * Add variation options
     *
     * @param int     $loop           Position in the loop.
     * @param array   $variation_data Variation data.
     * @param WP_Post $variation      Post data.
     */
    public function variation_options($loop, $variation_data, $variation) {
        $us_states = function_exists('WC') ? WC()->countries->get_states('US') : array();
        $selected_states = get_post_meta($variation->ID, '_restricted_states', true);
        if (!is_array($selected_states)) {
            $selected_states = array_filter(array_map('trim', explode(',', (string)$selected_states)));
            update_post_meta($variation->ID, '_restricted_states', $selected_states);
        }
        echo '<tr><td colspan="2">';
        echo '<label for="_restricted_states_' . esc_attr($variation->ID) . '">' . esc_html__('Restricted States', 'ship-restrict') . '</label><br />';
        echo '<select id="_restricted_states_' . esc_attr($variation->ID) . '" name="_restricted_states[' . esc_attr($variation->ID) . '][]" multiple size="6" style="width:100%;max-width:400px;">';
        foreach ($us_states as $code => $label) {
            $selected = in_array($code, $selected_states, true) ? 'selected' : '';
            echo '<option value="' . esc_attr($code) . '" ' . $selected . '>' . esc_html($label) . ' (' . esc_html($code) . ')</option>';
        }
        echo '</select>';
        echo '<p class="description">' . esc_html__('Select one or more states where this variation cannot be shipped.', 'ship-restrict') . '</p>';
        echo '</td></tr>';
        // Cities and ZIPs remain as textareas
        woocommerce_wp_textarea_input(
            array(
                'id'          => '_restricted_cities_' . $variation->ID,
                'name'        => '_restricted_cities[' . $variation->ID . ']',
                'label'       => __('Restricted Cities', 'ship-restrict'),
                'placeholder' => __('Enter cities separated by commas', 'ship-restrict'),
                'desc_tip'    => true,
                'description' => __('Enter cities where this variation cannot be shipped.', 'ship-restrict'),
                'value'       => get_post_meta($variation->ID, '_restricted_cities', true),
            )
        );
        woocommerce_wp_textarea_input(
            array(
                'id'          => '_restricted_zip_codes_' . $variation->ID,
                'name'        => '_restricted_zip_codes[' . $variation->ID . ']',
                'label'       => __('Restricted ZIP Codes', 'ship-restrict'),
                'placeholder' => __('Enter ZIP codes separated by commas', 'ship-restrict'),
                'desc_tip'    => true,
                'description' => __('Enter ZIP codes where this variation cannot be shipped.', 'ship-restrict'),
                'value'       => get_post_meta($variation->ID, '_restricted_zip_codes', true),
            )
        );
    }

    /**
     * Save variation options
     *
     * @param int $variation_id Variation ID.
     * @param int $i            Position in the loop.
     */
    public function save_variation_options($variation_id, $i) {
        // Save states as array
        if (isset($_POST['_restricted_states'][$variation_id]) && is_array($_POST['_restricted_states'][$variation_id])) {
            $states = array_map('sanitize_text_field', $_POST['_restricted_states'][$variation_id]);
            update_post_meta($variation_id, '_restricted_states', $states);
        } else {
            delete_post_meta($variation_id, '_restricted_states');
        }
        // Save cities and ZIPs as before
        $fields = array('_restricted_cities', '_restricted_zip_codes');
        foreach ($fields as $field) {
            if (isset($_POST[$field][$variation_id])) {
                update_post_meta($variation_id, $field, sanitize_textarea_field(wp_unslash($_POST[$field][$variation_id])));
            }
        }
    }

    /**
     * Get all restricted cart items and reasons for the current cart and shipping address
     * @return array Array of arrays: [ 'product_name' => ..., 'product_id' => ..., 'reason' => ... ]
     */
    private function get_restricted_cart_items() {
        $restricted = array();
        if (!WC()->cart) {
            $this->log('WooCommerce cart not available', 'warning');
            return $restricted;
        }
        
        $shipping_country = WC()->customer->get_shipping_country();
        $shipping_state = WC()->customer->get_shipping_state();
        $shipping_city = WC()->customer->get_shipping_city();
        $shipping_postcode = WC()->customer->get_shipping_postcode();
        
        $this->log(sprintf('Checking restrictions for address: %s, %s, %s, %s', 
            $shipping_country, $shipping_state, $shipping_city, $shipping_postcode), 'info');
        
        if (empty($shipping_country) || empty($shipping_state) || $shipping_country !== 'US') {
            $this->log('Skipping restriction check - not US address or missing data', 'info');
            return $restricted;
        }
        $settings = get_option('spsr_settings', array());
        $default_message_template = __('The {product} cannot currently be shipped to your location. Please remove from cart to continue.', 'ship-restrict');
        $custom_message_template = isset($settings['message']) && !empty(trim($settings['message'])) ? trim($settings['message']) : null;

        foreach (WC()->cart->get_cart() as $cart_item) {
            $product_id = $cart_item['product_id'];
            $variation_id = $cart_item['variation_id'];
            $product = $cart_item['data'];
            $product_name = $product->get_name();
            $restriction_reason = ''; // This will now hold the final formatted message
            $is_restricted = false;

            // Determine the message template to use
            $message_template_to_use = $custom_message_template ? $custom_message_template : $default_message_template;

            // 1. Variation-level
            if ($variation_id) {
                $variation_restricted_states = get_post_meta($variation_id, '_restricted_states', true);
                if (!empty($variation_restricted_states)) {
                    if (!is_array($variation_restricted_states)) {
                        $variation_restricted_states = array_filter(array_map('trim', explode(',', (string)$variation_restricted_states)));
                        update_post_meta($variation_id, '_restricted_states', $variation_restricted_states);
                    }
                    if (in_array($shipping_state, $variation_restricted_states, true)) {
                        $is_restricted = true;
                        $restriction_reason = sprintf(__('State restriction (%1$s)', 'ship-restrict'), $shipping_state);
                    }
                }
                $variation_restricted_cities = get_post_meta($variation_id, '_restricted_cities', true);
                if (!$is_restricted && !empty($variation_restricted_cities) && !empty($shipping_city)) {
                    $restricted_cities = array_map('trim', explode(',', strtolower($variation_restricted_cities)));
                    if (in_array(strtolower($shipping_city), $restricted_cities, true)) {
                        $is_restricted = true;
                        $restriction_reason = sprintf(__('City restriction (%1$s)', 'ship-restrict'), $shipping_city);
                    }
                }
                $variation_restricted_zip_codes = get_post_meta($variation_id, '_restricted_zip_codes', true);
                if (!$is_restricted && !empty($variation_restricted_zip_codes) && !empty($shipping_postcode)) {
                    $restricted_zip_codes = array_map('trim', explode(',', $variation_restricted_zip_codes));
                    if (in_array($shipping_postcode, $restricted_zip_codes, true)) {
                        $is_restricted = true;
                        $restriction_reason = sprintf(__('ZIP code restriction (%1$s)', 'ship-restrict'), $shipping_postcode);
                    }
                }
            }
            // 2. Product-level
            if (!$is_restricted) {
                $product_restricted_states = get_post_meta($product_id, '_restricted_states', true);
                if (!empty($product_restricted_states)) {
                    if (!is_array($product_restricted_states)) {
                        $product_restricted_states = array_filter(array_map('trim', explode(',', (string)$product_restricted_states)));
                        update_post_meta($product_id, '_restricted_states', $product_restricted_states);
                    }
                    if (in_array($shipping_state, $product_restricted_states, true)) {
                        $is_restricted = true;
                        $restriction_reason = sprintf(__('State restriction (%1$s)', 'ship-restrict'), $shipping_state);
                    }
                }
            }
            if (!$is_restricted) {
                $product_restricted_cities = get_post_meta($product_id, '_restricted_cities', true);
                if (!empty($product_restricted_cities) && !empty($shipping_city)) {
                    $restricted_cities = array_map('trim', explode(',', strtolower($product_restricted_cities)));
                    if (in_array(strtolower($shipping_city), $restricted_cities, true)) {
                        $is_restricted = true;
                        $restriction_reason = sprintf(__('City restriction (%1$s)', 'ship-restrict'), $shipping_city);
                    }
                }
            }
            if (!$is_restricted) {
                $product_restricted_zip_codes = get_post_meta($product_id, '_restricted_zip_codes', true);
                if (!empty($product_restricted_zip_codes) && !empty($shipping_postcode)) {
                    $restricted_zip_codes = array_map('trim', explode(',', $product_restricted_zip_codes));
                    if (in_array($shipping_postcode, $restricted_zip_codes, true)) {
                        $is_restricted = true;
                        $restriction_reason = sprintf(__('ZIP code restriction (%1$s)', 'ship-restrict'), $shipping_postcode);
                    }
                }
            }
            // 3. Category/Tag-level
            if (!$is_restricted) {
                $rules = isset($settings['rules']) && is_array($settings['rules']) ? $settings['rules'] : array();
                $this->log(sprintf('Checking %d rules for product %s', count($rules), $product_name), 'info');
                
                if (!empty($rules)) {
                    $product_cats = wp_get_post_terms($product_id, 'product_cat', array('fields' => 'ids'));
                    $product_tags = wp_get_post_terms($product_id, 'product_tag', array('fields' => 'ids'));
                    
                    $this->log(sprintf('Product categories: %s, Product tags: %s', 
                        implode(', ', $product_cats), implode(', ', $product_tags)), 'info');
                    
                    foreach ($rules as $rule_index => $rule) {
                        if (!isset($rule['type'], $rule['term_id'])) {
                            continue;
                        }
                        $match = false;
                        if ($rule['type'] === 'category' && in_array($rule['term_id'], $product_cats, true)) {
                            $match = true;
                        } elseif ($rule['type'] === 'tag' && in_array($rule['term_id'], $product_tags, true)) {
                            $match = true;
                        }
                        if ($match) {
                            $this->log(sprintf('Rule "%s" matches product %s', 
                                isset($rule['name']) ? $rule['name'] : 'Rule #' . $rule_index, $product_name), 'info');
                            
                            $rule_logic = isset($rule['logic']) ? $rule['logic'] : 'block_from';
                            $location_matches_rule = false;
                            $restriction_type = '';
                            
                            // Check states
                            if (!empty($rule['states']) && in_array($shipping_state, $rule['states'], true)) {
                                $location_matches_rule = true;
                                $restriction_type = sprintf(__('State restriction (%1$s) via rule: %2$s', 'ship-restrict'), $shipping_state, isset($rule['name']) ? $rule['name'] : '');
                                $this->log(sprintf('State matches rule: %s', $restriction_type), 'info');
                            }
                            
                            // Check specific state-city pairs
                            if (!$location_matches_rule && isset($rule['state_cities']) && is_array($rule['state_cities']) && !empty($shipping_city)) {
                                foreach ($rule['state_cities'] as $pair) {
                                    if (isset($pair['state'], $pair['city']) && 
                                        $pair['state'] === $shipping_state && 
                                        strtolower($pair['city']) === strtolower($shipping_city)) {
                                        $location_matches_rule = true;
                                        $restriction_type = sprintf(__('City restriction (%1$s, %2$s) via rule: %3$s', 'ship-restrict'), $shipping_city, $shipping_state, isset($rule['name']) ? $rule['name'] : '');
                                        $this->log(sprintf('State-city pair matches rule: %s', $restriction_type), 'info');
                                        break;
                                    }
                                }
                            }
                            
                            // Check legacy cities (backward compatibility)
                            if (!$location_matches_rule && !empty($rule['cities']) && !empty($shipping_city)) {
                                $cities = array_map('strtolower', array_map('trim', $rule['cities']));
                                if (in_array(strtolower($shipping_city), $cities, true)) {
                                    $location_matches_rule = true;
                                    $restriction_type = sprintf(__('City restriction (%1$s) via rule: %2$s', 'ship-restrict'), $shipping_city, isset($rule['name']) ? $rule['name'] : '');
                                    $this->log(sprintf('Legacy city matches rule: %s', $restriction_type), 'info');
                                }
                            }
                            
                            // Check ZIP codes
                            if (!$location_matches_rule && !empty($rule['zip_codes']) && !empty($shipping_postcode)) {
                                $zip_codes = array_map('trim', $rule['zip_codes']);
                                if (in_array($shipping_postcode, $zip_codes, true)) {
                                    $location_matches_rule = true;
                                    $restriction_type = sprintf(__('ZIP code restriction (%1$s) via rule: %2$s', 'ship-restrict'), $shipping_postcode, isset($rule['name']) ? $rule['name'] : '');
                                    $this->log(sprintf('ZIP code matches rule: %s', $restriction_type), 'info');
                                }
                            }
                            
                            // Apply rule logic
                            if ($location_matches_rule) {
                                if ($rule_logic === 'block_from') {
                                    // Block from: restrict if location matches
                                    $is_restricted = true;
                                    $restriction_reason = $restriction_type;
                                    $this->log(sprintf('RESTRICTION TRIGGERED (Block): %s', $restriction_reason), 'warning');
                                    break;
                                } else {
                                    // Allow only: this rule allows this location, continue checking other rules
                                    $this->log(sprintf('Location allowed by rule: %s', $restriction_type), 'info');
                                }
                            } else {
                                if ($rule_logic === 'allow_only') {
                                    // Allow only: restrict if location doesn't match
                                    $is_restricted = true;
                                    $restriction_reason = sprintf(__('Location not in allowed list for rule: %1$s', 'ship-restrict'), isset($rule['name']) ? $rule['name'] : '');
                                    $this->log(sprintf('RESTRICTION TRIGGERED (Allow Only): %s', $restriction_reason), 'warning');
                                    break;
                                }
                            }
                        }
                    }
                }
            }
            if ($is_restricted) {
                $restricted[] = array(
                    'product_name' => $product_name,
                    'product_id' => $product_id,
                    'reason' => str_replace('{product}', $product_name, $message_template_to_use),
                );
            }
        }
        return $restricted;
    }

    /**
     * Check cart items for restrictions during checkout validation.
     */
    public function spsr_cart_restriction_notice() {
        // Get the validation errors - WooCommerce checkout validation doesn't pass WP_Error object
        // We need to use wc_add_notice instead

        $restricted = $this->get_restricted_cart_items();
        if (!empty($restricted)) {
            $this->log('Found restricted items during checkout: ' . count($restricted), 'info');
            
            // Use a general header notice, then list the specific items
            $header_notice = __('Some items in your cart cannot be shipped to your address:', 'ship-restrict');
            
            $msg = '<ul>'; // Start with only the list
            foreach ($restricted as $item) {
                // Directly use the pre-formatted reason which now includes the product name
                $msg .= sprintf('<li>%s</li>', esc_html($item['reason']));
                $this->log('Restricted item: ' . $item['product_name'] . ' - ' . $item['reason'], 'warning');
            }
            $msg .= '</ul>';

            // Add the restriction message as a checkout validation error
            wc_add_notice($header_notice . $msg, 'error');
        } else {
            $this->log('No restricted items found during checkout validation', 'info');
        }
    }

    /**
     * Modern cart validation using Store API (recommended approach)
     * 
     * @param WP_Error $errors Error object to add validation errors to
     * @param WC_Cart $cart   Cart object
     */
    public function validate_cart_restrictions($errors, $cart) {
        $this->log('Running modern cart validation via Store API', 'info');
        
        $restricted = $this->get_restricted_cart_items();
        
        if (!empty($restricted)) {
            $this->log('Found ' . count($restricted) . ' restricted items in cart', 'warning');
            
            $header_notice = __('Some items in your cart cannot be shipped to your address:', 'ship-restrict');
            $msg = '<ul>';
            
            foreach ($restricted as $item) {
                $msg .= sprintf('<li>%s</li>', esc_html($item['reason']));
                $this->log('Restricted item: ' . $item['product_name'] . ' - ' . $item['reason'], 'warning');
            }
            
            $msg .= '</ul>';
            
            // Add error to the WP_Error object
            $errors->add('shipping_restriction', $header_notice . $msg);
        } else {
            $this->log('No restricted items found in cart validation', 'info');
        }
    }

    /**
     * Legacy cart validation (fallback for older WooCommerce versions)
     */
    public function legacy_cart_validation() {
        $this->log('Running legacy cart validation', 'info');
        
        $restricted = $this->get_restricted_cart_items();
        
        if (!empty($restricted)) {
            $this->log('Found ' . count($restricted) . ' restricted items via legacy validation', 'warning');
            
            $header_notice = __('Some items in your cart cannot be shipped to your address:', 'ship-restrict');
            $msg = '<ul>';
            
            foreach ($restricted as $item) {
                $msg .= sprintf('<li>%s</li>', esc_html($item['reason']));
                $this->log('Restricted item: ' . $item['product_name'] . ' - ' . $item['reason'], 'warning');
            }
            
            $msg .= '</ul>';
            
            // Use wc_add_notice for legacy validation
            wc_add_notice($header_notice . $msg, 'error');
        } else {
            $this->log('No restricted items found in legacy validation', 'info');
        }
    }

    /**
     * Log message using WooCommerce logger
     *
     * @param string $message Message to log
     * @param string $level Log level (info, warning, error)
     */
    private function log($message, $level = 'info') {
        if (function_exists('wc_get_logger')) {
            $logger = wc_get_logger();
            $context = array('source' => 'ship-restrict');
            
            switch ($level) {
                case 'error':
                    $logger->error($message, $context);
                    break;
                case 'warning':
                    $logger->warning($message, $context);
                    break;
                default:
                    $logger->info($message, $context);
            }
        }
    }

    /**
     * Get cached value
     *
     * @param string $key Cache key
     * @return mixed Cached value or false if not found
     */
    private function get_cache($key) {
        return get_transient('spsr_cache_' . $key);
    }

    /**
     * Set cache value
     *
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int|null $expiration Expiration time in seconds (default: 15 minutes)
     * @return bool True if set successfully, false otherwise
     */
    private function set_cache($key, $value, $expiration = null) {
        if (null === $expiration) {
            $expiration = 15 * MINUTE_IN_SECONDS;
        }
        return set_transient('spsr_cache_' . $key, $value, $expiration);
    }

    /**
     * Clear cache
     *
     * @param string|null $key Specific cache key to clear, or null to clear all
     */
    private function clear_cache($key = null) {
        if ($key) {
            delete_transient('spsr_cache_' . $key);
        } else {
            // Clear all SPSR caches (implement if needed in the future)
            // This would require tracking all cache keys or using a different approach
        }
    }

    /**
     * Check if API requests are rate limited
     *
     * @return bool True if rate limited, false otherwise
     */
    private function is_rate_limited() {
        $transient_key = 'spsr_api_rate_limit';
        $attempts = get_transient($transient_key);
        
        if (false === $attempts) {
            set_transient($transient_key, 1, SPSR_RATE_LIMIT_WINDOW);
            return false;
        }
        
        if ($attempts >= SPSR_RATE_LIMIT_MAX) {
            return true;
        }
        
        set_transient($transient_key, $attempts + 1, SPSR_RATE_LIMIT_WINDOW);
        return false;
    }

    /**
     * Get or generate a unique device identifier for this site
     *
     * @return string
     */
    private function get_device_identifier() {
        $device_id = get_option('spsr_device_id');
        if (!$device_id) {
            $device_id = wp_generate_uuid4();
            update_option('spsr_device_id', $device_id);
        }
        return $device_id;
    }

    /**
     * Validate or activate license with KeyForge
     *
     * @param string $license_key
     * @param bool $activate
     * @return array [ 'valid' => bool, 'error' => string, 'product_id' => string ]
     */
    private function keyforge_validate_license($license_key, $activate = false) {
        // Check rate limiting
        if ($this->is_rate_limited()) {
            return array(
                'valid' => false,
                'error' => 'Too many validation attempts. Please try again in 5 minutes.',
                'product_id' => defined('SPSR_KEYFORGE_PRODUCT_ID') ? SPSR_KEYFORGE_PRODUCT_ID : ''
            );
        }
        
        $device_id = $this->get_device_identifier();
        $site_name = get_bloginfo('name');
        $site_url = home_url();
        $product_id = defined('SPSR_KEYFORGE_PRODUCT_ID') ? SPSR_KEYFORGE_PRODUCT_ID : '';
        $endpoint = $activate
            ? 'https://keyforge.dev/api/v1/public/licenses/activate'
            : 'https://keyforge.dev/api/v1/public/licenses/validate';
        $body = array(
            'licenseKey' => $license_key,
            'productId' => $product_id,
            'deviceIdentifier' => $device_id,
        );
        if ($activate) {
            $body['deviceName'] = $site_name . ' (' . $site_url . ')';
        }
        $args = array(
            'headers' => array('Content-Type' => 'application/json'),
            'body' => wp_json_encode($body),
            'timeout' => 10,
        );
        $response = wp_remote_post($endpoint, $args);
        if (is_wp_error($response)) {
            return array('valid' => false, 'error' => 'Could not reach license server.', 'product_id' => $product_id);
        }
        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        // Accept any 2xx code as success response format.
        if ($code < 200 || $code >= 300) {
            $err = 'License server error (' . $code . ').';
            if (is_array($data) && isset($data['message'])) {
                $err .= ' ' . $data['message'];
            }
            return array('valid' => false, 'error' => $err, 'product_id' => $product_id);
        }
        if (!is_array($data)) {
            // Some endpoints (e.g., activate) may return empty body; treat as success.
            return array('valid' => true, 'error' => '', 'product_id' => $product_id);
        }
        if ($activate) {
            // Activation returns 200 on success, no isValid field
            return array('valid' => true, 'error' => '', 'product_id' => $product_id);
        }
        if ((isset($data['isValid']) && $data['isValid']) || (isset($data['valid']) && $data['valid'])) {
            return array('valid' => true, 'error' => '', 'product_id' => $product_id);
        }
        return array('valid' => false, 'error' => isset($data['message']) ? $data['message'] : 'License invalid.', 'product_id' => $product_id);
    }
}

/**
 * Main plugin instance
 *
 * @return APSR_Pro
 */
function APSR_PRO() {
    return APSR_Pro::instance();
}

// Initialize the plugin
add_action('plugins_loaded', 'APSR_PRO');

// Use the proper checkout validation hook
add_action('woocommerce_checkout_process', array(APSR_Pro::instance(), 'spsr_cart_restriction_notice'), 5);

// Fallback: print notices in footer if not already rendered (useful for Cart page)
add_action('wp_footer', function() {
    // Add early exit if functions don't exist
    if (!function_exists('is_checkout') || !function_exists('is_cart')) {
        return;
    }
    if (is_checkout() || is_cart()) {
        wc_print_notices();
    }
}, 99);

// Add Settings link to plugin row actions
function shiprestrict_plugin_action_links($actions) {
    if (current_user_can('manage_woocommerce')) {
        $settings_url = admin_url('admin.php?page=spsr-settings');
        $settings_link = '<a href="' . esc_url($settings_url) . '">' . esc_html__('Settings', 'ship-restrict') . '</a>';
        array_unshift($actions, $settings_link);
    }
    return $actions;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'shiprestrict_plugin_action_links');
