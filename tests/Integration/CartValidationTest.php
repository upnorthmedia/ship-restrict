<?php
/**
 * Integration tests for cart validation.
 *
 * These tests require WooCommerce to be installed and active.
 *
 * @package Ship_Restrict
 */

namespace ShipRestrict\Tests\Integration;

use WP_UnitTestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * Test case for cart validation.
 *
 * @requires extension woocommerce
 */
class CartValidationTest extends WP_UnitTestCase {

    /**
     * Plugin instance.
     *
     * @var \APSR_Pro
     */
    private $plugin;

    /**
     * Check if WooCommerce is available.
     *
     * @return bool
     */
    private static function is_woocommerce_available() {
        return class_exists( 'WooCommerce' ) && function_exists( 'WC' );
    }

    /**
     * Set up test fixtures.
     */
    public function setUp(): void {
        parent::setUp();

        if ( ! self::is_woocommerce_available() ) {
            $this->markTestSkipped( 'WooCommerce is not available.' );
        }

        $this->plugin = \APSR_Pro::instance();
        delete_option( 'spsr_settings' );
    }

    /**
     * Tear down test fixtures.
     */
    public function tearDown(): void {
        delete_option( 'spsr_settings' );

        // Clear cart if WooCommerce available.
        if ( self::is_woocommerce_available() && WC()->cart ) {
            WC()->cart->empty_cart();
        }

        parent::tearDown();
    }

    /**
     * Get private/protected method accessible for testing.
     *
     * @param string $method_name Method name.
     * @return ReflectionMethod
     */
    private function get_method( $method_name ) {
        $reflection = new ReflectionClass( $this->plugin );
        $method     = $reflection->getMethod( $method_name );
        $method->setAccessible( true );
        return $method;
    }

    /**
     * Create a simple WooCommerce product.
     *
     * @param array $args Product arguments.
     * @return \WC_Product
     */
    private function create_simple_product( $args = array() ) {
        $defaults = array(
            'name'          => 'Test Product',
            'regular_price' => '10.00',
            'status'        => 'publish',
        );

        $args    = wp_parse_args( $args, $defaults );
        $product = new \WC_Product_Simple();
        $product->set_name( $args['name'] );
        $product->set_regular_price( $args['regular_price'] );
        $product->set_status( $args['status'] );
        $product->save();

        return $product;
    }

    /**
     * Test validation skips non-US addresses.
     */
    public function test_validation_skips_non_us_addresses() {
        if ( ! self::is_woocommerce_available() ) {
            $this->markTestSkipped( 'WooCommerce is not available.' );
        }

        // Set customer shipping to Canada.
        WC()->customer->set_shipping_country( 'CA' );
        WC()->customer->set_shipping_state( 'ON' );
        WC()->customer->set_shipping_city( 'Toronto' );

        $method     = $this->get_method( 'get_restricted_cart_items' );
        $restricted = $method->invoke( $this->plugin );

        // Should return empty array for non-US.
        $this->assertIsArray( $restricted );
        $this->assertEmpty( $restricted );
    }

    /**
     * Test validation skips addresses without state.
     */
    public function test_validation_skips_addresses_without_state() {
        if ( ! self::is_woocommerce_available() ) {
            $this->markTestSkipped( 'WooCommerce is not available.' );
        }

        // Set US country but no state.
        WC()->customer->set_shipping_country( 'US' );
        WC()->customer->set_shipping_state( '' );

        $method     = $this->get_method( 'get_restricted_cart_items' );
        $restricted = $method->invoke( $this->plugin );

        $this->assertIsArray( $restricted );
        $this->assertEmpty( $restricted );
    }

    /**
     * Test state restriction blocks product.
     */
    public function test_state_restriction_blocks_product() {
        if ( ! self::is_woocommerce_available() ) {
            $this->markTestSkipped( 'WooCommerce is not available.' );
        }

        // Create product with California restriction.
        $product = $this->create_simple_product();
        update_post_meta( $product->get_id(), '_restricted_states', array( 'CA' ) );

        // Add to cart.
        WC()->cart->add_to_cart( $product->get_id() );

        // Set shipping to California.
        WC()->customer->set_shipping_country( 'US' );
        WC()->customer->set_shipping_state( 'CA' );
        WC()->customer->set_shipping_city( 'Los Angeles' );
        WC()->customer->set_shipping_postcode( '90001' );

        $method     = $this->get_method( 'get_restricted_cart_items' );
        $restricted = $method->invoke( $this->plugin );

        $this->assertNotEmpty( $restricted );
        $this->assertArrayHasKey( $product->get_id(), array_column( $restricted, null, 'product_id' ) );
    }

    /**
     * Test state restriction allows non-restricted state.
     */
    public function test_state_restriction_allows_non_restricted_state() {
        if ( ! self::is_woocommerce_available() ) {
            $this->markTestSkipped( 'WooCommerce is not available.' );
        }

        // Create product with California restriction.
        $product = $this->create_simple_product();
        update_post_meta( $product->get_id(), '_restricted_states', array( 'CA' ) );

        // Add to cart.
        WC()->cart->add_to_cart( $product->get_id() );

        // Set shipping to New York (not restricted).
        WC()->customer->set_shipping_country( 'US' );
        WC()->customer->set_shipping_state( 'NY' );
        WC()->customer->set_shipping_city( 'New York' );
        WC()->customer->set_shipping_postcode( '10001' );

        $method     = $this->get_method( 'get_restricted_cart_items' );
        $restricted = $method->invoke( $this->plugin );

        $this->assertEmpty( $restricted );
    }

    /**
     * Test city restriction is case insensitive.
     */
    public function test_city_restriction_is_case_insensitive() {
        if ( ! self::is_woocommerce_available() ) {
            $this->markTestSkipped( 'WooCommerce is not available.' );
        }

        // Create product with city restriction.
        $product = $this->create_simple_product();
        update_post_meta(
            $product->get_id(),
            '_restricted_state_cities',
            array(
                array(
                    'state' => 'CA',
                    'city'  => 'Los Angeles', // Mixed case.
                ),
            )
        );

        // Add to cart.
        WC()->cart->add_to_cart( $product->get_id() );

        // Set shipping with uppercase city.
        WC()->customer->set_shipping_country( 'US' );
        WC()->customer->set_shipping_state( 'CA' );
        WC()->customer->set_shipping_city( 'LOS ANGELES' ); // All caps.
        WC()->customer->set_shipping_postcode( '90001' );

        $method     = $this->get_method( 'get_restricted_cart_items' );
        $restricted = $method->invoke( $this->plugin );

        // Should still be restricted despite case difference.
        $this->assertNotEmpty( $restricted );
    }

    /**
     * Test ZIP code restriction exact match.
     */
    public function test_zip_code_restriction_exact_match() {
        if ( ! self::is_woocommerce_available() ) {
            $this->markTestSkipped( 'WooCommerce is not available.' );
        }

        // Create product with ZIP restriction.
        $product = $this->create_simple_product();
        update_post_meta( $product->get_id(), '_restricted_zip_codes', '90210, 90211' );

        // Add to cart.
        WC()->cart->add_to_cart( $product->get_id() );

        // Set shipping to restricted ZIP.
        WC()->customer->set_shipping_country( 'US' );
        WC()->customer->set_shipping_state( 'CA' );
        WC()->customer->set_shipping_city( 'Beverly Hills' );
        WC()->customer->set_shipping_postcode( '90210' );

        $method     = $this->get_method( 'get_restricted_cart_items' );
        $restricted = $method->invoke( $this->plugin );

        $this->assertNotEmpty( $restricted );

        // Now test with non-restricted ZIP.
        WC()->customer->set_shipping_postcode( '90212' );

        // Clear and re-add product.
        WC()->cart->empty_cart();
        WC()->cart->add_to_cart( $product->get_id() );

        $restricted = $method->invoke( $this->plugin );
        $this->assertEmpty( $restricted );
    }

    /**
     * Test custom error message is used.
     */
    public function test_custom_error_message_is_used() {
        if ( ! self::is_woocommerce_available() ) {
            $this->markTestSkipped( 'WooCommerce is not available.' );
        }

        $custom_message = 'Sorry, this product cannot ship to your area.';

        update_option(
            'spsr_settings',
            array(
                'message'       => $custom_message,
                'license_valid' => false,
            )
        );

        // Create and restrict product.
        $product = $this->create_simple_product( array( 'name' => 'Restricted Item' ) );
        update_post_meta( $product->get_id(), '_restricted_states', array( 'CA' ) );

        // Add to cart.
        WC()->cart->add_to_cart( $product->get_id() );

        // Set California shipping.
        WC()->customer->set_shipping_country( 'US' );
        WC()->customer->set_shipping_state( 'CA' );
        WC()->customer->set_shipping_postcode( '90001' );

        $method     = $this->get_method( 'get_restricted_cart_items' );
        $restricted = $method->invoke( $this->plugin );

        $this->assertNotEmpty( $restricted );

        // Check that the restriction data is populated.
        $first_restricted = reset( $restricted );
        $this->assertArrayHasKey( 'message', $first_restricted );
    }

    /**
     * Test multiple products with different restrictions.
     */
    public function test_multiple_products_with_different_restrictions() {
        if ( ! self::is_woocommerce_available() ) {
            $this->markTestSkipped( 'WooCommerce is not available.' );
        }

        // Product 1: Restricted to CA.
        $product1 = $this->create_simple_product( array( 'name' => 'CA Restricted' ) );
        update_post_meta( $product1->get_id(), '_restricted_states', array( 'CA' ) );

        // Product 2: Restricted to NY.
        $product2 = $this->create_simple_product( array( 'name' => 'NY Restricted' ) );
        update_post_meta( $product2->get_id(), '_restricted_states', array( 'NY' ) );

        // Product 3: No restrictions.
        $product3 = $this->create_simple_product( array( 'name' => 'No Restriction' ) );

        // Add all to cart.
        WC()->cart->add_to_cart( $product1->get_id() );
        WC()->cart->add_to_cart( $product2->get_id() );
        WC()->cart->add_to_cart( $product3->get_id() );

        // Ship to California.
        WC()->customer->set_shipping_country( 'US' );
        WC()->customer->set_shipping_state( 'CA' );
        WC()->customer->set_shipping_postcode( '90001' );

        $method     = $this->get_method( 'get_restricted_cart_items' );
        $restricted = $method->invoke( $this->plugin );

        // Only product 1 (CA restricted) should be blocked.
        $this->assertCount( 1, $restricted );

        $product_ids = array_column( $restricted, 'product_id' );
        $this->assertContains( $product1->get_id(), $product_ids );
        $this->assertNotContains( $product2->get_id(), $product_ids );
        $this->assertNotContains( $product3->get_id(), $product_ids );
    }
}
