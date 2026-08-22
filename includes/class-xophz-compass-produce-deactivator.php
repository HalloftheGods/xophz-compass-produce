<?php

/**
 * Fired during plugin deactivation.
 *
 * @since      1.0.0
 * @package    Xophz_Compass_Produce
 * @subpackage Xophz_Compass_Produce/includes
 */

class Xophz_Compass_Produce_Deactivator {

	/**
	 * Deactivation cleanup routine.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		// Flush rewrite rules if needed
		flush_rewrite_rules();
	}
}
