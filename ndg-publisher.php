<?php
/**
 * Plugin Name: Nudge Publisher
 * Plugin URI: http://www.giveitanudge.com/
 * Description: Manage multiple Nudge Analytics Codes on specific posts and pages.
 * Version: 1.0
 * Author: Nudge
 * Author URI: http://www.giveitanudge.com/
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Set a constant path to the plugin's root directory.
 */
if ( !defined( 'NUDGE_PUBLISHER_DIR' ) ) {
	define( 'NUDGE_PUBLISHER_DIR', plugin_dir_path( __FILE__ ) );
}

if ( !defined( 'NUDGE_PUBLISHER_URL' ) ) {
	define( 'NUDGE_PUBLISHER_URL', plugin_dir_url( __FILE__ ) );
}

if ( !defined( 'NDG_PUBLISHER_TEXTDOMAIN' ) ) {
	define( 'NDG_PUBLISHER_TEXTDOMAIN', 'ndg-plublisher' );
}

if ( !class_exists( 'Nudge_Publisher' ) ):
	class Nudge_Publisher {
		const DB_VERSION = '0.1';
		const CODE_OPTION = 'ndg_plublisher_code';

		/**
		 * Setup the plugin.
		 *
		 * @since 0.1.0
		 */
		public static function load () {
			// Load the admin functionality.
			if ( is_admin() ) {
				add_action( 'admin_init', array( __CLASS__, 'upgrade' ) );

				require_once( NUDGE_PUBLISHER_DIR . 'admin/admin.php' );
				new Nudge_Publisher_Admin();
			}
		}

		public static function supported_post_types () {
			return array( 'post', 'page' );
		}

		public static function footer_add_nudge_analytics_code () {
			if ( is_singular( self::supported_post_types() ) ) {
				$post_id = get_the_ID();
				$code_id = self::get_post_ndg_code_id( $post_id );
				if ( !$code_id ) {
					return;
				}
				$code = ndgb_get_code( $code_id );
				if ( !$code ) {
					return;
				}

				echo $code->code;
			}
		}

		/**
		 * @param $post_id
		 * @return mixed
		 */
		public static function get_post_ndg_code_id ( $post_id ) {
			return get_post_meta( $post_id, '_ndg_plublisher_code_id', true );
		}


		public static function install () {
			/* @var $wpdb wpdb */
			global $wpdb;

			$table_name = $wpdb->prefix . 'ndg_plublisher_codes';

			$charset_collate = $wpdb->get_charset_collate();

			$sql = "CREATE TABLE $table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                name varchar(100) NOT NULL,
                code text NOT NULL,
                UNIQUE KEY id (id)
            ) $charset_collate;";

			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );

			add_option( 'ndg_plublisher_version', self::DB_VERSION );
		}

		public static function upgrade () {
			//do nothing
		}
	}

	function ndgb_get_codes () {
		/* @var wpdb $wpdb */
		global $wpdb;
		$table_name = $wpdb->prefix . 'ndg_plublisher_codes';

		return $wpdb->get_results( "
            SELECT *
            FROM $table_name
            ORDER BY name DESC
        " );
	}

	function ndgb_get_code ( $code_id ) {
		/* @var wpdb $wpdb */
		global $wpdb;
		$table_name = $wpdb->prefix . 'ndg_plublisher_codes';

		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $code_id ) );
	}

	register_activation_hook( __FILE__, array( 'Nudge_Publisher', 'install' ) );

	add_action( 'plugins_loaded', array( 'Nudge_Publisher', 'load' ) );
	add_action( 'wp_head', array( 'Nudge_Publisher', 'footer_add_nudge_analytics_code' ), 10000 );

endif;
