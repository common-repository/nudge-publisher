<?php

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'Nudge_Publisher_Admin' ) ) {
	return;
}

class Nudge_Publisher_Admin {
	/**
	 * Load the admin functionality.
	 *
	 * @since 0.1.0
	 */
	public function __construct () {
		require_once( NUDGE_PUBLISHER_DIR . 'admin/admin-post-meta-box.php' );
		new Nudge_Publisher_Admin_Post_Meta_Box();


		require_once( NUDGE_PUBLISHER_DIR . 'admin/admin-codes.php' );
		new Nudge_Publisher_Admin_Codes();
	}

	public static function codes_url () {
		return admin_url( 'options-general.php?page=' . Nudge_Publisher_Admin_Codes::PAGE_NAME );
	}

}


