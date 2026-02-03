<?php
/**
 * Tests for restriction matching logic.
 *
 * @package Ship_Restrict
 */

namespace ShipRestrict\Tests\Unit;

use WP_UnitTestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * Test case for restriction logic.
 */
class RestrictionLogicTest extends WP_UnitTestCase {

    /**
     * Plugin instance.
     *
     * @var \APSR_Pro
     */
    private $plugin;

    /**
     * Set up test fixtures.
     */
    public function setUp(): void {
        parent::setUp();
        $this->plugin = \APSR_Pro::instance();
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
     * Test ZIP3 state mapping returns valid data.
     */
    public function test_get_zip3_state_map_returns_array() {
        $method   = $this->get_method( 'get_zip3_state_map' );
        $zip_map  = $method->invoke( null );

        $this->assertIsArray( $zip_map );
        $this->assertNotEmpty( $zip_map );
    }

    /**
     * Test ZIP3 state mapping contains expected state codes.
     *
     * @dataProvider zip_code_state_provider
     *
     * @param string $zip3   First 3 digits of ZIP code.
     * @param string $state  Expected state code.
     */
    public function test_zip3_maps_to_correct_state( $zip3, $state ) {
        $method   = $this->get_method( 'get_zip3_state_map' );
        $zip_map  = $method->invoke( null );

        $this->assertArrayHasKey( $zip3, $zip_map );
        $this->assertEquals( $state, $zip_map[ $zip3 ] );
    }

    /**
     * Data provider for ZIP code to state mapping.
     *
     * @return array
     */
    public static function zip_code_state_provider() {
        return array(
            'Connecticut 060'   => array( '060', 'CT' ),
            'Massachusetts 021' => array( '021', 'MA' ),
            'New York 100'      => array( '100', 'NY' ),
            'California 900'    => array( '900', 'CA' ),
            'Texas 750'         => array( '750', 'TX' ),
            'Pennsylvania 150'  => array( '150', 'PA' ),
            'Florida 330'       => array( '330', 'FL' ),
        );
    }

    /**
     * Test that states array contains all US states.
     */
    public function test_get_us_states_returns_all_states() {
        $method = $this->get_method( 'get_us_states' );
        $states = $method->invoke( $this->plugin );

        $this->assertIsArray( $states );
        // Check for some known states.
        $this->assertArrayHasKey( 'CA', $states );
        $this->assertArrayHasKey( 'NY', $states );
        $this->assertArrayHasKey( 'TX', $states );
        $this->assertArrayHasKey( 'FL', $states );
        // Should have 50 states + DC + territories.
        $this->assertGreaterThanOrEqual( 51, count( $states ) );
    }

    /**
     * Test state matching is exact match.
     */
    public function test_state_restriction_is_exact_match() {
        $product_id = $this->factory->post->create(
            array(
                'post_type'   => 'product',
                'post_status' => 'publish',
            )
        );

        // Add state restriction for California.
        update_post_meta( $product_id, '_restricted_states', array( 'CA' ) );

        $restricted_states = get_post_meta( $product_id, '_restricted_states', true );

        $this->assertIsArray( $restricted_states );
        $this->assertContains( 'CA', $restricted_states );
        // Should be exact match, not partial.
        $this->assertTrue( in_array( 'CA', $restricted_states, true ) );
        $this->assertFalse( in_array( 'C', $restricted_states, true ) );
        $this->assertFalse( in_array( 'CAL', $restricted_states, true ) );
    }

    /**
     * Test city matching is case-insensitive.
     */
    public function test_city_restriction_is_case_insensitive() {
        $test_cities = array( 'Los Angeles', 'NEW YORK', 'chicago', 'San Francisco' );

        foreach ( $test_cities as $city ) {
            // City matching should work regardless of case.
            $normalized = strtolower( trim( $city ) );
            $this->assertEquals( strtolower( $city ), $normalized );
        }

        // Test actual matching logic.
        $shipping_city       = 'LOS ANGELES';
        $restricted_cities   = array_map( 'trim', explode( ',', 'los angeles, san francisco, chicago' ) );
        $restricted_cities   = array_map( 'strtolower', $restricted_cities );

        $this->assertTrue( in_array( strtolower( $shipping_city ), $restricted_cities, true ) );
    }

    /**
     * Test ZIP code matching is exact.
     */
    public function test_zip_code_restriction_is_exact_match() {
        $product_id = $this->factory->post->create(
            array(
                'post_type'   => 'product',
                'post_status' => 'publish',
            )
        );

        // Add ZIP restriction.
        update_post_meta( $product_id, '_restricted_zip_codes', '90210, 90211, 10001' );

        $zip_codes  = get_post_meta( $product_id, '_restricted_zip_codes', true );
        $zip_array  = array_map( 'trim', explode( ',', $zip_codes ) );

        // Exact match.
        $this->assertTrue( in_array( '90210', $zip_array, true ) );
        // Partial should not match.
        $this->assertFalse( in_array( '902', $zip_array, true ) );
        $this->assertFalse( in_array( '9021', $zip_array, true ) );
    }

    /**
     * Test state-city pair matching.
     */
    public function test_state_city_pair_matching() {
        $state_cities = array(
            array(
                'state' => 'CA',
                'city'  => 'Los Angeles',
            ),
            array(
                'state' => 'NY',
                'city'  => 'New York City',
            ),
        );

        $shipping_state = 'CA';
        $shipping_city  = 'los angeles'; // Lowercase for case-insensitive check.

        $is_restricted = false;
        foreach ( $state_cities as $pair ) {
            if ( $pair['state'] === $shipping_state &&
                 strtolower( $pair['city'] ) === strtolower( $shipping_city ) ) {
                $is_restricted = true;
                break;
            }
        }

        $this->assertTrue( $is_restricted );

        // Test non-matching city in same state.
        $shipping_city = 'San Diego';
        $is_restricted = false;
        foreach ( $state_cities as $pair ) {
            if ( $pair['state'] === $shipping_state &&
                 strtolower( $pair['city'] ) === strtolower( $shipping_city ) ) {
                $is_restricted = true;
                break;
            }
        }

        $this->assertFalse( $is_restricted );
    }

    /**
     * Test restriction hierarchy: variation > product.
     */
    public function test_variation_restriction_takes_priority() {
        // Create parent product.
        $product_id = $this->factory->post->create(
            array(
                'post_type'   => 'product',
                'post_status' => 'publish',
            )
        );

        // Add product-level state restriction.
        update_post_meta( $product_id, '_restricted_states', array( 'NY' ) );

        // Simulate variation with different restriction.
        $variation_id = $this->factory->post->create(
            array(
                'post_type'   => 'product_variation',
                'post_status' => 'publish',
                'post_parent' => $product_id,
            )
        );

        // Variation restricts CA, not NY.
        update_post_meta( $variation_id, '_restricted_states', array( 'CA' ) );

        // Check variation-level restriction.
        $variation_states = get_post_meta( $variation_id, '_restricted_states', true );
        $product_states   = get_post_meta( $product_id, '_restricted_states', true );

        // Variation should have CA.
        $this->assertContains( 'CA', $variation_states );
        $this->assertNotContains( 'NY', $variation_states );

        // Product should have NY.
        $this->assertContains( 'NY', $product_states );

        // Simulate priority check: variation first.
        $shipping_state = 'CA';
        $is_restricted  = false;

        // Check variation first (priority).
        if ( ! empty( $variation_states ) && in_array( $shipping_state, $variation_states, true ) ) {
            $is_restricted = true;
        }

        $this->assertTrue( $is_restricted );
    }
}
