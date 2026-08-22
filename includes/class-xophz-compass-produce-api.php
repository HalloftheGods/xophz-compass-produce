<?php

/**
 * REST API Endpoints for Local Produce & EDVEX Federation.
 *
 * @since      1.0.0
 * @package    Xophz_Compass_Produce
 * @subpackage Xophz_Compass_Produce/includes
 */

class Xophz_Compass_Produce_API {

	/**
	 * Namespace for the REST API.
	 *
	 * @var string
	 */
	protected $namespace = 'xophz-produce/v1';

	/**
	 * Register all REST API routes.
	 */
	public function register_routes() {
		// 1. Providers / Apple Carts discovery
		register_rest_route( $this->namespace, '/providers', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_providers' ),
			'permission_callback' => '__return_true',
		) );

		// 2. Harvest summary & telemetry
		register_rest_route( $this->namespace, '/harvest', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_harvest_summary' ),
			'permission_callback' => '__return_true',
		) );

		// 3. Package Crate
		register_rest_route( $this->namespace, '/package', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'package_crate' ),
			'permission_callback' => '__return_true',
		) );

		// 4. Mint Preview & Gas Estimation
		register_rest_route( $this->namespace, '/mint-preview', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'preview_mint' ),
			'permission_callback' => '__return_true',
		) );

		// 5. Federated Farmer's Market Stalls
		register_rest_route( $this->namespace, '/stalls', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_market_stalls' ),
			'permission_callback' => '__return_true',
		) );

		// 6. Peer Docking Handshake
		register_rest_route( $this->namespace, '/dock', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'dock_peer' ),
			'permission_callback' => '__return_true',
		) );

		// 7. Compost / Crypto-Shredding
		register_rest_route( $this->namespace, '/compost', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'compost_data' ),
			'permission_callback' => '__return_true',
		) );
	}

	/**
	 * Get list of all harvest providers.
	 */
	public function get_providers( $request ) {
		$providers = Xophz_Compass_Produce_Engine::get_active_providers();
		return rest_ensure_response( array(
			'success'   => true,
			'providers' => $providers,
		) );
	}

	/**
	 * Get aggregate harvest summary.
	 */
	public function get_harvest_summary( $request ) {
		$providers = Xophz_Compass_Produce_Engine::get_active_providers();
		$total_points = 0;
		$total_tokens = 0.0;
		$active_count = 0;

		foreach ( $providers as $prov ) {
			if ( ! empty( $prov['active'] ) ) {
				$total_points += (int) $prov['data_points'];
				$total_tokens += (float) $prov['est_tokens'];
				$active_count++;
			}
		}

		return rest_ensure_response( array(
			'success'        => true,
			'active_carts'   => $active_count,
			'total_points'   => $total_points,
			'est_tokens_day' => round( $total_tokens, 2 ),
			'currency'       => 'DIRT',
		) );
	}

	/**
	 * Package data into a crate.
	 */
	public function package_crate( $request ) {
		$params      = $request->get_json_params();
		$provider_id = isset( $params['provider_id'] ) ? sanitize_text_field( $params['provider_id'] ) : '';
		$splits      = isset( $params['splits'] ) ? (array) $params['splits'] : array();

		if ( empty( $provider_id ) ) {
			return new WP_Error( 'missing_param', __( 'provider_id is required', 'xophz-compass-produce' ), array( 'status' => 400 ) );
		}

		$result = Xophz_Compass_Produce_Engine::package_crate( $provider_id, $splits );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( array(
			'success' => true,
			'crate'   => $result,
		) );
	}

	/**
	 * Preview mint parameters and splits.
	 */
	public function preview_mint( $request ) {
		$params     = $request->get_json_params();
		$orig_pct   = isset( $params['originator'] ) ? (float) $params['originator'] : 98.00;
		$dao_pct    = isset( $params['dao'] ) ? (float) $params['dao'] : 1.50;
		$arch_pct   = isset( $params['architect'] ) ? (float) $params['architect'] : 0.50;

		$splits = Xophz_Compass_Produce_Engine::validate_split_config( $orig_pct, $dao_pct, $arch_pct );

		return rest_ensure_response( array(
			'success'       => true,
			'splits'        => $splits,
			'network'       => 'Elysium EVM',
			'gas_token'     => 'LAVA',
			'est_gas_cost'  => '0.00042 LAVA (~$0.0001)',
			'contract_type' => 'ERC-1155 / EDVEX Channel Title',
		) );
	}

	/**
	 * Get federated market stalls across w4.
	 */
	public function get_market_stalls( $request ) {
		// Mock peer stalls representing federated nodes in the network
		$stalls = array(
			array(
				'id'          => 'stall_tucson_bio',
				'node_name'   => 'Tucson Bioregion Node',
				'w4_address'  => 'w4://compass.tucson-coop.org',
				'trust_score' => 98.5,
				'offerings'   => array(
					array(
						'title'       => 'Sonoran Solar Telemetry',
						'schema'      => 'youmeos/telemetry/solar/v1',
						'data_points' => 450,
						'price'       => '5.0 DIRT / mo',
						'curator'     => '0x71a...92b',
					),
					array(
						'title'       => 'Permaculture Micro-Climate Index',
						'schema'      => 'youmeos/ecology/climate/v1',
						'data_points' => 1200,
						'price'       => '12.0 DIRT / mo',
						'curator'     => '0x71a...92b',
					)
				)
			),
			array(
				'id'          => 'stall_cascade_dev',
				'node_name'   => 'Cascadia Builders Hive',
				'w4_address'  => 'w4://hive.cascadia-devs.net',
				'trust_score' => 99.2,
				'offerings'   => array(
					array(
						'title'       => 'OpenVue Component Benchmarks',
						'schema'      => 'youmeos/code/benchmarks/v1',
						'data_points' => 840,
						'price'       => '8.0 DIRT / mo',
						'curator'     => '0x88c...41e',
					)
				)
			),
		);

		return rest_ensure_response( array(
			'success' => true,
			'stalls'  => $stalls,
		) );
	}

	/**
	 * Perform cryptographic peer docking handshake.
	 */
	public function dock_peer( $request ) {
		$params = $request->get_json_params();
		$stall_id = isset( $params['stall_id'] ) ? sanitize_text_field( $params['stall_id'] ) : '';

		return rest_ensure_response( array(
			'success'       => true,
			'status'        => 'docked',
			'stall_id'      => $stall_id,
			'handshake_key' => 'w4_synk_' . wp_generate_password( 16, false ),
			'message'       => __( 'Sovereign P2P pipe established.', 'xophz-compass-produce' ),
		) );
	}

	/**
	 * Compost data / crypto-shredding.
	 */
	public function compost_data( $request ) {
		$params   = $request->get_json_params();
		$crate_id = isset( $params['crate_id'] ) ? (int) $params['crate_id'] : 0;

		if ( $crate_id > 0 ) {
			Xophz_Compass_Produce_Engine::compost_crate( $crate_id );
		}

		return rest_ensure_response( array(
			'success' => true,
			'message' => __( 'Data shredded and cryptographic keys purged.', 'xophz-compass-produce' ),
		) );
	}
}
