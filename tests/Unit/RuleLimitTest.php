<?php
/**
 * Tests for rule and product limits (free vs pro tiers).
 *
 * @package Ship_Restrict
 */

namespace ShipRestrict\Tests\Unit;

use WP_UnitTestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * Test case for rule limits.
 */
class RuleLimitTest extends WP_UnitTestCase {

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
        // Reset settings before each test.
        delete_option( 'spsr_settings' );
    }

    /**
     * Tear down test fixtures.
     */
    public function tearDown(): void {
        delete_option( 'spsr_settings' );
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
     * Test free tier rule limit is 2.
     */
    public function test_free_tier_rule_limit_is_2() {
        // Ensure no pro license is active.
        update_option(
            'spsr_settings',
            array(
                'license_valid' => false,
            )
        );

        $method = $this->get_method( 'get_rule_limit' );
        $limit  = $method->invoke( $this->plugin );

        $this->assertEquals( 2, $limit );
    }

    /**
     * Test free tier product limit is 2.
     */
    public function test_free_tier_product_limit_is_2() {
        // Ensure no pro license is active.
        update_option(
            'spsr_settings',
            array(
                'license_valid' => false,
            )
        );

        $method = $this->get_method( 'get_product_limit' );
        $limit  = $method->invoke( $this->plugin );

        $this->assertEquals( 2, $limit );
    }

    /**
     * Test pro tier has unlimited rules.
     */
    public function test_pro_tier_rule_limit_is_unlimited() {
        // Simulate pro license active.
        update_option(
            'spsr_settings',
            array(
                'license_valid' => true,
                'license_key'   => 'test-license-key',
            )
        );

        $method = $this->get_method( 'get_rule_limit' );
        $limit  = $method->invoke( $this->plugin );

        $this->assertEquals( PHP_INT_MAX, $limit );
    }

    /**
     * Test pro tier has unlimited products.
     */
    public function test_pro_tier_product_limit_is_unlimited() {
        // Simulate pro license active.
        update_option(
            'spsr_settings',
            array(
                'license_valid' => true,
                'license_key'   => 'test-license-key',
            )
        );

        $method = $this->get_method( 'get_product_limit' );
        $limit  = $method->invoke( $this->plugin );

        $this->assertEquals( PHP_INT_MAX, $limit );
    }

    /**
     * Test is_pro_active returns false when no license.
     */
    public function test_is_pro_active_returns_false_without_license() {
        delete_option( 'spsr_settings' );

        $method    = $this->get_method( 'is_pro_active' );
        $is_active = $method->invoke( $this->plugin );

        $this->assertFalse( $is_active );
    }

    /**
     * Test is_pro_active returns false when license_valid is false.
     */
    public function test_is_pro_active_returns_false_with_invalid_license() {
        update_option(
            'spsr_settings',
            array(
                'license_valid' => false,
                'license_key'   => 'invalid-key',
            )
        );

        $method    = $this->get_method( 'is_pro_active' );
        $is_active = $method->invoke( $this->plugin );

        $this->assertFalse( $is_active );
    }

    /**
     * Test is_pro_active returns true when license is valid.
     */
    public function test_is_pro_active_returns_true_with_valid_license() {
        update_option(
            'spsr_settings',
            array(
                'license_valid' => true,
                'license_key'   => 'valid-key',
            )
        );

        $method    = $this->get_method( 'is_pro_active' );
        $is_active = $method->invoke( $this->plugin );

        $this->assertTrue( $is_active );
    }

    /**
     * Test count_restricted_products returns correct count.
     */
    public function test_count_restricted_products_returns_correct_count() {
        // Clear any caches.
        wp_cache_flush();

        // Create products with restrictions.
        $product1 = $this->factory->post->create(
            array(
                'post_type'   => 'product',
                'post_status' => 'publish',
            )
        );
        update_post_meta( $product1, '_restricted_states', array( 'CA' ) );

        $product2 = $this->factory->post->create(
            array(
                'post_type'   => 'product',
                'post_status' => 'publish',
            )
        );
        update_post_meta( $product2, '_restricted_zip_codes', '90210, 90211' );

        // Create product without restrictions.
        $product3 = $this->factory->post->create(
            array(
                'post_type'   => 'product',
                'post_status' => 'publish',
            )
        );

        // Flush cache and test.
        wp_cache_flush();

        $method = $this->get_method( 'count_restricted_products' );
        $count  = $method->invoke( $this->plugin );

        // Should count products with restrictions.
        $this->assertGreaterThanOrEqual( 2, $count );
    }

    /**
     * Test upgrade prompt shows for free tier.
     */
    public function test_show_upgrade_prompt_returns_message_for_free_tier() {
        update_option(
            'spsr_settings',
            array(
                'license_valid' => false,
            )
        );

        $method = $this->get_method( 'show_upgrade_prompt' );

        // Test rules context.
        $rules_message = $method->invoke( $this->plugin, 'rules' );
        $this->assertStringContainsString( 'Upgrade to Pro', $rules_message );
        $this->assertStringContainsString( '2 restriction rules', $rules_message );

        // Test products context.
        $products_message = $method->invoke( $this->plugin, 'products' );
        $this->assertStringContainsString( 'Upgrade to Pro', $products_message );
        $this->assertStringContainsString( '2 product restrictions', $products_message );
    }

    /**
     * Test upgrade prompt is empty for pro tier.
     */
    public function test_show_upgrade_prompt_returns_empty_for_pro_tier() {
        update_option(
            'spsr_settings',
            array(
                'license_valid' => true,
                'license_key'   => 'valid-key',
            )
        );

        $method = $this->get_method( 'show_upgrade_prompt' );

        $message = $method->invoke( $this->plugin, 'rules' );
        $this->assertEmpty( $message );
    }

    /**
     * Test rules array storage and retrieval.
     */
    public function test_rules_are_stored_and_retrieved_correctly() {
        $rules = array(
            array(
                'rule_type'  => 'block_from',
                'location'   => 'states',
                'states'     => array( 'CA', 'NY' ),
                'applies_to' => 'category',
                'categories' => array( 1, 2 ),
            ),
            array(
                'rule_type'  => 'allow_only',
                'location'   => 'zip_codes',
                'zip_codes'  => '90210, 10001',
                'applies_to' => 'tag',
                'tags'       => array( 3 ),
            ),
        );

        $settings = array(
            'rules'         => $rules,
            'license_valid' => false,
        );

        update_option( 'spsr_settings', $settings );

        $retrieved = get_option( 'spsr_settings' );

        $this->assertIsArray( $retrieved['rules'] );
        $this->assertCount( 2, $retrieved['rules'] );
        $this->assertEquals( 'block_from', $retrieved['rules'][0]['rule_type'] );
        $this->assertEquals( 'allow_only', $retrieved['rules'][1]['rule_type'] );
    }
}
