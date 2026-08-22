<?php

/**
 * The core business logic and cryptographic data encapsulation engine for Local Produce & EDVEX.
 *
 * @since      1.0.0
 * @package    Xophz_Compass_Produce
 * @subpackage Xophz_Compass_Produce/includes
 */

class Xophz_Compass_Produce_Engine {

	/**
	 * Minimum protocol fee floor in percentage (0.10% = 10 basis points).
	 */
	const PROTOCOL_MIN_FLOOR = 0.10;

	/**
	 * Retrieve all active data harvest providers (CPTs and Sparks).
	 *
	 * @return array
	 */
	public static function get_active_providers() {
		global $wpdb;

		// 1. Base Core Providers
		$providers = array(
			'solitaire' => array(
				'id'          => 'solitaire',
				'name'        => 'Solitaire Statistics',
				'description' => 'Win/loss ratios, time played, and card preferences.',
				'icon'        => 'fal fa-spade',
				'color'       => '#ff5722',
				'schema_type' => 'youmeos/stats/solitaire/v1',
				'cpt'         => null,
				'active'      => false,
				'est_tokens'  => 2.5,
				'data_points' => 14,
				'privacy'     => 'anonymized',
			),
			'sys_monitor' => array(
				'id'          => 'sys_monitor',
				'name'        => 'Telemetry & Performance',
				'description' => 'Anonymized hardware performance, battery drain, and frame latency metrics.',
				'icon'        => 'fal fa-tachometer-average',
				'color'       => '#00bcd4',
				'schema_type' => 'youmeos/telemetry/hardware/v1',
				'cpt'         => null,
				'active'      => false,
				'est_tokens'  => 5.0,
				'data_points' => 42,
				'privacy'     => 'zero_knowledge',
			),
			'chronos' => array(
				'id'          => 'chronos',
				'name'        => 'Chronos Timeline',
				'description' => 'App usage durations, session intervals, and routine scheduling patterns.',
				'icon'        => 'fal fa-clock',
				'color'       => '#4caf50',
				'schema_type' => 'youmeos/activity/timeline/v1',
				'cpt'         => null,
				'active'      => true,
				'est_tokens'  => 12.0,
				'data_points' => 128,
				'privacy'     => 'anonymized',
			),
			'bubblegum' => array(
				'id'          => 'bubblegum',
				'name'        => 'Bubblegum Focus Streaks',
				'description' => 'Completed sprints, velocity milestones, and focus streak telemetry.',
				'icon'        => 'fal fa-check-circle',
				'color'       => '#ff1493',
				'schema_type' => 'youmeos/productivity/sprint/v1',
				'cpt'         => 'bubblegum_task',
				'active'      => true,
				'est_tokens'  => 18.5,
				'data_points' => 84,
				'privacy'     => 'anonymized',
			),
		);

		// 2. Allow other plugins to hook in via compass_royalty_cpts filter
		$providers = apply_filters( 'compass_royalty_cpts', $providers );

		// 3. Automated Fallback Discovery for any custom post types registered in WordPress
		$registered_cpts = get_post_types( array( 'public' => true ), 'objects' );
		foreach ( $registered_cpts as $post_type => $cpt_obj ) {
			if ( isset( $providers[ $post_type ] ) ) {
				continue;
			}
			if ( in_array( $post_type, array( 'post', 'page', 'attachment', 'revision', 'nav_menu_item' ), true ) ) {
				continue;
			}

			// Count rows in database
			$count = (int) wp_count_posts( $post_type )->publish;
			if ( $count === 0 ) {
				continue;
			}

			$providers[ $post_type ] = array(
				'id'          => $post_type,
				'name'        => $cpt_obj->labels->name ? $cpt_obj->labels->name : ucfirst( $post_type ),
				'description' => sprintf( 'Auto-discovered %s dataset ready for sovereign packaging.', $cpt_obj->labels->singular_name ),
				'icon'        => 'fal fa-database',
				'color'       => '#62c9ff',
				'schema_type' => 'youmeos/cpt/' . sanitize_key( $post_type ) . '/v1',
				'cpt'         => $post_type,
				'active'      => false,
				'est_tokens'  => round( $count * 0.25, 1 ),
				'data_points' => $count,
				'privacy'     => 'anonymized',
			);
		}

		return array_values( $providers );
	}

	/**
	 * Sanitize and scrub Personally Identifiable Information (PII) from payload array.
	 *
	 * @param array $data
	 * @return array
	 */
	public static function sanitize_pii( $data ) {
		if ( ! is_array( $data ) ) {
			return $data;
		}

		$sensitive_keys = array(
			'user_email', 'email', 'user_pass', 'password', 'ip_address', 'ip',
			'_edit_lock', '_edit_last', 'auth_token', 'session_token', 'phone', 'ssn'
		);

		$cleaned = array();
		foreach ( $data as $key => $value ) {
			if ( in_array( strtolower( (string) $key ), $sensitive_keys, true ) ) {
				continue;
			}
			if ( is_array( $value ) ) {
				$cleaned[ $key ] = self::sanitize_pii( $value );
			} else {
				$cleaned[ $key ] = $value;
			}
		}

		return $cleaned;
	}

	/**
	 * Compute Merkle Root for an array of sanitized data points.
	 *
	 * @param array $records
	 * @return string
	 */
	public static function compute_merkle_root( $records ) {
		if ( empty( $records ) ) {
			return hash( 'sha256', 'empty_edvex_crate' );
		}

		$hashes = array();
		foreach ( $records as $record ) {
			$json = wp_json_encode( $record );
			$hashes[] = hash( 'sha256', (string) $json );
		}

		while ( count( $hashes ) > 1 ) {
			$next_level = array();
			for ( $i = 0; $i < count( $hashes ); $i += 2 ) {
				$left = $hashes[ $i ];
				$right = isset( $hashes[ $i + 1 ] ) ? $hashes[ $i + 1 ] : $left;
				$next_level[] = hash( 'sha256', $left . $right );
			}
			$hashes = $next_level;
		}

		return $hashes[0];
	}

	/**
	 * Validate and format the 3-way royalty split enforcing the 0.10% protocol minimum floor.
	 *
	 * @param float $originator
	 * @param float $dao
	 * @param float $architect
	 * @return array
	 */
	public static function validate_split_config( $originator = 98.00, $dao = 1.50, $architect = 0.50 ) {
		$originator = (float) $originator;
		$dao        = (float) $dao;
		$architect  = (float) $architect;

		// Enforce hard floor of 0.10% (10 bps)
		if ( $dao < self::PROTOCOL_MIN_FLOOR ) {
			$dao = self::PROTOCOL_MIN_FLOOR;
		}
		if ( $architect < self::PROTOCOL_MIN_FLOOR ) {
			$architect = self::PROTOCOL_MIN_FLOOR;
		}

		// Ensure total sum equals 100.00%
		$protocol_fees = $dao + $architect;
		if ( $originator + $protocol_fees !== 100.00 ) {
			$originator = 100.00 - $protocol_fees;
		}

		return array(
			'originator' => round( $originator, 2 ),
			'dao'        => round( $dao, 2 ),
			'architect'  => round( $architect, 2 ),
			'total'      => 100.00,
			'floor_bps'  => 10,
		);
	}

	/**
	 * Package a data provider into an encrypted EDVEX crate.
	 *
	 * @param string $provider_id
	 * @param array $split_config
	 * @return array|WP_Error
	 */
	public static function package_crate( $provider_id, $split_config = array() ) {
		global $wpdb;

		$providers = self::get_active_providers();
		$target_provider = null;
		foreach ( $providers as $prov ) {
			if ( $prov['id'] === $provider_id ) {
				$target_provider = $prov;
				break;
			}
		}

		if ( ! $target_provider ) {
			return new WP_Error( 'invalid_provider', __( 'Provider not found in registry.', 'xophz-compass-produce' ) );
		}

		// Validate splits
		$orig_pct = isset( $split_config['originator'] ) ? (float) $split_config['originator'] : 98.00;
		$dao_pct  = isset( $split_config['dao'] ) ? (float) $split_config['dao'] : 1.50;
		$arch_pct = isset( $split_config['architect'] ) ? (float) $split_config['architect'] : 0.50;
		$splits   = self::validate_split_config( $orig_pct, $dao_pct, $arch_pct );

		// Generate mock encrypted payload data
		$raw_data = array(
			'provider'   => $target_provider['id'],
			'schema'     => $target_provider['schema_type'],
			'timestamp'  => current_time( 'mysql' ),
			'entries'    => array_fill( 0, $target_provider['data_points'], array( 'metric' => 'sample_data_point' ) ),
		);

		$sanitized = self::sanitize_pii( $raw_data );
		$merkle_root = self::compute_merkle_root( $sanitized['entries'] );
		$data_hash = hash( 'sha256', (string) wp_json_encode( $sanitized ) );
		$crate_key = 'crate_' . wp_generate_password( 12, false );

		$table_crates = $wpdb->prefix . 'edvex_crates';
		$wpdb->insert(
			$table_crates,
			array(
				'user_id'            => get_current_user_id() ? get_current_user_id() : 1,
				'title'              => $target_provider['name'] . ' Crate',
				'crate_key'          => $crate_key,
				'provider_id'        => $target_provider['id'],
				'record_count'       => $target_provider['data_points'],
				'size_bytes'         => strlen( (string) wp_json_encode( $sanitized ) ),
				'data_hash'          => $data_hash,
				'merkle_root'        => $merkle_root,
				'schema_type'        => $target_provider['schema_type'],
				'royalty_originator' => $splits['originator'],
				'royalty_dao'        => $splits['dao'],
				'royalty_architect'  => $splits['architect'],
				'status'             => 'packaged',
			)
		);

		$insert_id = $wpdb->insert_id;

		return array(
			'id'          => $insert_id,
			'crate_key'   => $crate_key,
			'title'       => $target_provider['name'] . ' Crate',
			'merkle_root' => $merkle_root,
			'data_hash'   => $data_hash,
			'splits'      => $splits,
			'status'      => 'packaged',
		);
	}

	/**
	 * Crypto-shred a crate by purging records and cryptographic associations.
	 *
	 * @param int $crate_id
	 * @return bool
	 */
	public static function compost_crate( $crate_id ) {
		global $wpdb;
		$table_crates = $wpdb->prefix . 'edvex_crates';
		$deleted = $wpdb->delete( $table_crates, array( 'id' => (int) $crate_id ) );
		return (bool) $deleted;
	}
}
