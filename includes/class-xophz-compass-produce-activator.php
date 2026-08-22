<?php

/**
 * Fired during plugin activation.
 *
 * @since      1.0.0
 * @package    Xophz_Compass_Produce
 * @subpackage Xophz_Compass_Produce/includes
 */

class Xophz_Compass_Produce_Activator {

	/**
	 * Create database tables for EDVEX crates, leases, and federated peer stalls.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// 1. Packaged Data Crates Table
		$table_crates = $wpdb->prefix . 'edvex_crates';
		$sql_crates = "CREATE TABLE $table_crates (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL DEFAULT 1,
			title varchar(191) NOT NULL,
			crate_key varchar(191) NOT NULL,
			provider_id varchar(100) NOT NULL,
			record_count int(11) unsigned NOT NULL DEFAULT 0,
			size_bytes bigint(20) unsigned NOT NULL DEFAULT 0,
			data_hash varchar(191) NOT NULL,
			merkle_root varchar(191) NOT NULL,
			schema_type varchar(191) NOT NULL,
			contract_address varchar(100) DEFAULT NULL,
			token_id varchar(100) DEFAULT NULL,
			royalty_originator decimal(5,2) NOT NULL DEFAULT 98.00,
			royalty_dao decimal(5,2) NOT NULL DEFAULT 1.50,
			royalty_architect decimal(5,2) NOT NULL DEFAULT 0.50,
			status varchar(50) NOT NULL DEFAULT 'packaged',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY uq_crate_key (crate_key),
			KEY provider_id (provider_id),
			KEY status (status)
		) $charset_collate;";
		dbDelta( $sql_crates );

		// 2. Active P2P Leases & Subscriptions Table
		$table_leases = $wpdb->prefix . 'edvex_leases';
		$sql_leases = "CREATE TABLE $table_leases (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			crate_id bigint(20) unsigned NOT NULL,
			buyer_address varchar(100) NOT NULL,
			license_type varchar(50) NOT NULL DEFAULT 'stream_lease',
			price_paid decimal(18,6) NOT NULL DEFAULT 0.000000,
			currency varchar(20) NOT NULL DEFAULT 'DIRT',
			tx_hash varchar(191) DEFAULT NULL,
			expires_at datetime DEFAULT NULL,
			status varchar(50) NOT NULL DEFAULT 'active',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY crate_id (crate_id),
			KEY buyer_address (buyer_address),
			KEY status (status)
		) $charset_collate;";
		dbDelta( $sql_leases );

		// 3. Federated w4 Peer Stalls Directory Table
		$table_peers = $wpdb->prefix . 'edvex_peers';
		$sql_peers = "CREATE TABLE $table_peers (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			node_name varchar(191) NOT NULL,
			w4_address varchar(191) NOT NULL,
			trust_score decimal(5,2) NOT NULL DEFAULT 100.00,
			schemas_offered text DEFAULT NULL,
			last_seen datetime DEFAULT CURRENT_TIMESTAMP,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY uq_w4_address (w4_address)
		) $charset_collate;";
		dbDelta( $sql_peers );

		// Register Custom Post Type for WP Query Compatibility
		self::register_cpt();
	}

	/**
	 * Register Custom Post Type for wp_edvex_crate.
	 */
	public static function register_cpt() {
		register_post_type( 'wp_edvex_crate', array(
			'labels' => array(
				'name'          => __( 'EDVEX Crates', 'xophz-compass-produce' ),
				'singular_name' => __( 'EDVEX Crate', 'xophz-compass-produce' ),
			),
			'public'       => false,
			'show_ui'      => false,
			'supports'     => array( 'title', 'custom-fields' ),
			'has_archive'  => false,
			'show_in_rest' => true,
		) );
	}
}
