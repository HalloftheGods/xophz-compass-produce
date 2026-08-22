<?php

/**
 * The core plugin orchestrator class for Local Produce.
 *
 * @since      1.0.0
 * @package    Xophz_Compass_Produce
 * @subpackage Xophz_Compass_Produce/includes
 */

class Xophz_Compass_Produce {

	/**
	 * The unique identifier of this plugin.
	 *
	 * @var string
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @var string
	 */
	protected $version;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->plugin_name = 'xophz-compass-produce';
		$this->version     = XOPHZ_COMPASS_PRODUCE_VERSION;

		$this->load_dependencies();
	}

	/**
	 * Load the required dependencies.
	 */
	private function load_dependencies() {
		require_once XOPHZ_COMPASS_PRODUCE_PATH . 'includes/class-xophz-compass-produce-engine.php';
		require_once XOPHZ_COMPASS_PRODUCE_PATH . 'includes/class-xophz-compass-produce-api.php';
	}

	/**
	 * Run the loader to execute all of the hooks.
	 */
	public function run() {
		$api = new Xophz_Compass_Produce_API();
		add_action( 'rest_api_init', array( $api, 'register_routes' ) );

		// Register with Event Horizon Sparks Registry
		add_filter( 'xophz_register_sparks', array( $this, 'register_spark' ) );
		add_filter( 'xophz_get_spark_manifest', array( $this, 'get_spark_manifest' ), 10, 2 );

		// Register submenu with My COMPASS WordPress admin
		add_action( 'admin_menu', array( $this, 'add_to_menu' ) );
	}

	/**
	 * Add Local Produce to the My COMPASS WordPress admin sidebar submenu.
	 */
	public function add_to_menu() {
		if ( class_exists( 'Xophz_Compass' ) ) {
			Xophz_Compass::add_submenu( 'xophz-compass-produce' );
		}
	}

	/**
	 * Register Local Produce spark in the global sparks list.
	 */
	public function register_spark( $sparks ) {
		$sparks['local-produce'] = array(
			'id'          => 'local-produce',
			'title'       => __( 'Local Produce', 'xophz-compass-produce' ),
			'description' => __( 'Farmer\'s Market & EDVEX Data Royalty Engine', 'xophz-compass-produce' ),
			'icon'        => 'fal fa-apple-crate',
			'color'       => '#4caf50',
			'categories'  => array( 'economics', 'productivity', 'core' ),
			'version'     => $this->version,
			'author'      => 'Hall of the Gods, Inc.'
		);
		$sparks['u-local-produce'] = $sparks['local-produce'];
		return $sparks;
	}

	/**
	 * Return structural manifest for rendering Local Produce in YouMeOS.
	 */
	public function get_spark_manifest( $manifest, $spark_id ) {
		if ( 'local-produce' !== $spark_id && 'u-local-produce' !== $spark_id ) {
			return $manifest;
		}

		return array(
			'id' => 'u-local-produce',
			'meta' => array(
				'title' => 'Local Produce',
				'icon' => 'fal fa-apple-crate',
				'color' => '#4caf50',
				'dimensions' => array(
					'width' => 680,
					'height' => 620,
					'minWidth' => 450,
					'minHeight' => 400
				)
			)
		);
	}
}
