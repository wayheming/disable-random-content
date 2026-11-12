<?php
/**
 * Plugin hooks and rewrite rule lifecycle.
 *
 * @package DisableRandomContent
 */

namespace KAGG\DisableRandomContent;

/**
 * Disables random redirects and refreshes rewrite rules when plugin status changes.
 */
final class Main {
	/** Keep only this many site IDs in memory at a time; all batches are processed. */
	private const SITE_BATCH_SIZE = 100;

	/**
	 * Main plugin file.
	 *
	 * @var string
	 */
	private string $plugin_file;

	/**
	 * Set the main plugin file used by WordPress activation hooks.
	 *
	 * @param string $plugin_file Absolute path to the main plugin file.
	 */
	public function __construct( string $plugin_file ) {
		$this->plugin_file = $plugin_file;
	}

	/**
	 * Register the plugin's WordPress hooks.
	 */
	public function init(): void {
		add_filter( 'wp_enable_random_content_redirect', [ $this, 'disable_random_content_redirect' ], 0 );
		add_action( 'update_site_option_active_sitewide_plugins', [ $this, 'network_status_updated' ], 10, 4 );
		add_action( 'add_site_option_active_sitewide_plugins', [ $this, 'network_status_added' ], 10, 3 );

		register_activation_hook( $this->plugin_file, [ $this, 'activate' ] );
		register_deactivation_hook( $this->plugin_file, [ $this, 'deactivate' ] );
	}

	/**
	 * Disable the core random content redirect feature.
	 *
	 * @return bool Always false.
	 */
	public function disable_random_content_redirect(): bool {
		return false;
	}

	/**
	 * Restore the disabling filter and refresh rules on ordinary activation.
	 *
	 * @param bool $network_wide Whether the plugin is activated for the network.
	 */
	public function activate( bool $network_wide ): void {
		// The plugin file is not loaded again when reactivated in the same request.
		add_filter( 'wp_enable_random_content_redirect', [ $this, 'disable_random_content_redirect' ], 0 );

		if ( ! $network_wide ) {
			flush_rewrite_rules( false );
		}
	}

	/**
	 * Restore the current site's rules on ordinary deactivation.
	 *
	 * @param bool $network_wide Whether the plugin is deactivated for the network.
	 */
	public function deactivate( bool $network_wide ): void {
		remove_filter( 'wp_enable_random_content_redirect', [ $this, 'disable_random_content_redirect' ], 0 );

		if ( ! $network_wide ) {
			flush_rewrite_rules( false );
		}
	}

	/**
	 * Invalidate site rules after network activation or deactivation completes.
	 *
	 * @param string $option     Network option name.
	 * @param mixed  $new_value  New network-wide active plugin list.
	 * @param mixed  $old_value  Previous network-wide active plugin list.
	 * @param int    $network_id Network ID.
	 *
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function network_status_updated( string $option, $new_value, $old_value, int $network_id ): void {
		$plugin     = plugin_basename( $this->plugin_file );
		$was_active = is_array( $old_value ) && isset( $old_value[ $plugin ] );
		$is_active  = is_array( $new_value ) && isset( $new_value[ $plugin ] );

		if ( $was_active !== $is_active ) {
			$this->clear_network_rewrite_rules( $network_id );
		}
	}

	/**
	 * Handle the first creation of the network-wide active plugin list.
	 *
	 * @param string $option     Network option name.
	 * @param mixed  $value      Network-wide active plugin list.
	 * @param int    $network_id Network ID.
	 *
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function network_status_added( string $option, $value, int $network_id ): void {
		if ( is_array( $value ) && isset( $value[ plugin_basename( $this->plugin_file ) ] ) ) {
			$this->clear_network_rewrite_rules( $network_id );
		}
	}

	/**
	 * Clear saved rules for every site in the affected network.
	 *
	 * @param int $network_id Network ID.
	 */
	private function clear_network_rewrite_rules( int $network_id ): void {
		if ( $network_id < 1 || ! is_multisite() ) {
			return;
		}

		$offset = 0;

		do {
			$site_ids = get_sites(
				[
					'network_id' => $network_id,
					'fields'     => 'ids',
					'number'     => self::SITE_BATCH_SIZE,
					'offset'     => $offset,
					'orderby'    => 'id',
					'order'      => 'ASC',
				]
			);

			foreach ( $site_ids as $site_id ) {
				switch_to_blog( (int) $site_id );

				try {
					// Core also deletes rewrite_rules for a switched site in update_home_siteurl().
					// A flush here could save rules from the original site's WP_Rewrite object.
					// The next request to this site regenerates rules with its own settings.
					delete_option( 'rewrite_rules' );
				} finally {
					restore_current_blog();
				}
			}

			$site_count = count( $site_ids );
			$offset    += $site_count;
		} while ( self::SITE_BATCH_SIZE === $site_count );
	}
}
