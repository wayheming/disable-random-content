<?php
/**
 * Same-request plugin lifecycle integration tests.
 *
 * @package DisableRandomContent
 */

namespace KAGG\DisableRandomContent\Tests\Integration;

use WP_UnitTestCase;

/**
 * Exercise the registered lifecycle callbacks without reloading the plugin file.
 *
 * @group integration
 */
class PluginLifecycleIntegrationTest extends WP_UnitTestCase {

	/**
	 * Repeated reactivation keeps the feature disabled and its saved routes absent.
	 */
	public function test_same_request_reactivation_removes_random_routes(): void {
		global $wp_rewrite;

		$plugin = plugin_basename( dirname( __DIR__, 2 ) . '/plugin.php' );
		$wp_rewrite->set_permalink_structure( '/%postname%/' );

		for ( $cycle = 0; $cycle < 2; ++$cycle ) {
			do_action( 'deactivate_' . $plugin, false );
			self::assertTrue( wp_is_random_content_redirect_enabled() );
			self::assertNotEmpty( $wp_rewrite->random_rewrite_rules() );

			do_action( 'activate_' . $plugin, false );
			self::assertFalse( wp_is_random_content_redirect_enabled() );

			$rules = get_option( 'rewrite_rules' );
			self::assertIsArray( $rules );
			self::assertNotEmpty( $rules );

			foreach ( $rules as $destination ) {
				self::assertStringNotContainsString( 'random=1', $destination );
			}

			$this->go_to( '/' . $wp_rewrite->get_random_base() . '/' );
			self::assertTrue( is_404() );
		}
	}

	/**
	 * Network reactivation restores the filter while leaving rule invalidation deferred.
	 */
	public function test_same_request_network_reactivation_disables_random_content(): void {
		if ( ! is_multisite() ) {
			self::markTestSkipped( 'Requires a WordPress multisite test run.' );
		}

		$plugin   = plugin_basename( dirname( __DIR__, 2 ) . '/plugin.php' );
		$sentinel = [ 'sentinel/?$' => 'index.php?test=1' ];
		update_option( 'rewrite_rules', $sentinel );

		do_action( 'deactivate_' . $plugin, true );
		self::assertTrue( wp_is_random_content_redirect_enabled() );

		do_action( 'activate_' . $plugin, true );
		self::assertFalse( wp_is_random_content_redirect_enabled() );
		self::assertSame( $sentinel, get_option( 'rewrite_rules' ) );
	}

	/**
	 * Reactivation preserves another plugin's explicit override at a later priority.
	 */
	public function test_reactivation_preserves_later_filter_override(): void {
		global $wp_rewrite;

		$plugin = plugin_basename( dirname( __DIR__, 2 ) . '/plugin.php' );
		$wp_rewrite->set_permalink_structure( '/%postname%/' );

		do_action( 'deactivate_' . $plugin, false );
		add_filter( 'wp_enable_random_content_redirect', '__return_true', 5 );

		try {
			do_action( 'activate_' . $plugin, false );
			self::assertTrue( wp_is_random_content_redirect_enabled() );
			self::assertNotEmpty( $wp_rewrite->random_rewrite_rules() );
		} finally {
			remove_filter( 'wp_enable_random_content_redirect', '__return_true', 5 );
		}

		self::assertFalse( wp_is_random_content_redirect_enabled() );
	}
}
