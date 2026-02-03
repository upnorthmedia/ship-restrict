<?php
/**
 * PHPUnit bootstrap file for Ship Restrict plugin tests.
 *
 * @package Ship_Restrict
 */

// Define test plugin directory.
define( 'SHIP_RESTRICT_PLUGIN_DIR', dirname( __DIR__ ) );
define( 'SHIP_RESTRICT_PLUGIN_FILE', SHIP_RESTRICT_PLUGIN_DIR . '/ship-restrict.php' );

// Composer autoloader must be loaded before WP_PHPUNIT__DIR will be available.
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Give access to tests_add_filter() function.
require_once getenv( 'WP_PHPUNIT__DIR' ) . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
    // Load WooCommerce first if available.
    $wc_plugin = WP_PLUGIN_DIR . '/woocommerce/woocommerce.php';
    if ( file_exists( $wc_plugin ) ) {
        require_once $wc_plugin;
    }

    // Load our plugin.
    require SHIP_RESTRICT_PLUGIN_FILE;
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require getenv( 'WP_PHPUNIT__DIR' ) . '/includes/bootstrap.php';

// Load WooCommerce test helpers if available.
$wc_tests_framework = WP_PLUGIN_DIR . '/woocommerce/tests/legacy/framework/class-wc-unit-test-case.php';
if ( file_exists( $wc_tests_framework ) ) {
    require_once $wc_tests_framework;
}
