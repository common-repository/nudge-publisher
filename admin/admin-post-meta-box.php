<?php

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

if ( !class_exists( 'NdgPublisherAdminPostException' ) ) {
	class NdgPublisherAdminPostException extends Exception {

	}
}

if ( class_exists( 'Nudge_Publisher_Admin_Post_Meta_Box' ) ) {
	return;
}

class Nudge_Publisher_Admin_Post_Meta_Box {

	const NONCE_NAME = 'ndg_plublisher_plublisher_code_id_nonce';

	public function __construct () {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10, 1 );

		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ), 10, 2 );
		add_action( 'save_post', array( $this, 'save_post_plublisher_code_id' ) );
	}

	function enqueue_scripts ( $hook ) {
		global $post;
		if ( $hook != 'post.php' ) {
			return;
		}
		if ( !$this->supports( $post->post_type ) ) {
			return;
		}

		wp_enqueue_style( 'ndg-plublisher-admin', NUDGE_PUBLISHER_URL . 'admin/css/styles.css' );
	}

	/**
	 * Register nudge plublisher code meta box for supported post types.
	 *
	 * @since 0.1.0
	 *
	 * @param $post_type
	 * @param WP_POST $post
	 */
	public function add_meta_box ( $post_type, $post = null ) {
		if ( !$this->supports( $post_type ) ) {
			return;
		}
		$post_id = $post ? $post->ID : null;

		if ( !$this->current_user_can( $post_type, $post_id ) ) {
			return;
		}

		add_meta_box( 'ndg-plublisher-post-meta', __( 'Nudge Publisher', NDG_PUBLISHER_TEXTDOMAIN ),
			array( $this, 'render_meta_box' ), $post_type, 'side', 'default' );
	}


	/**
	 * Renders meta box for choosing code in which post/page will be white listed.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_Post $post The post object
	 * @param $box
	 */
	public function render_meta_box ( $post, $box ) {
		$codes = ndgb_get_codes();
		$selected_code_id = $this->get_post_ndg_code_id( $post->ID );

		// Show an error message if a Nudge Code key hasn't been added
		if ( empty( $codes ) ) {
			$this->render_error(
				sprintf(
					__( 'For Nudge Publisher to work, a Nudge Codes need to be added on the %s', NDG_PUBLISHER_TEXTDOMAIN ),
					' <a href="' . Nudge_Publisher_Admin::codes_url() . '">' . __( 'Plugin settings screen', NDG_PUBLISHER_TEXTDOMAIN ) . '</a>.'
				)
			);

			return;
		}
		wp_nonce_field( 'update-post-ndg-plublisher-code_id-' . $post->ID, self::NONCE_NAME );
		?>
		<p>
			<label for="ndg_plublisher_code_id"
			><strong><?php _e( 'Inject Nudge Publisher Code', NDG_PUBLISHER_TEXTDOMAIN ) ?></strong>
			</label>
		</p>
		<select name="ndg_plublisher_code_id" id="ndg_plublisher_code_id" style="min-width: 75%">
			<option value="">---</option>
			<?php
			foreach ( $codes as $code ) {
				printf( '<option value="%s" %s>%s</option>',
					esc_attr( $code->id ),
					selected( $code->id, $selected_code_id, false ),
					$code->name
				);
			}
			?>
		</select>
		<p>
			<a href="<?php echo Nudge_Publisher_Admin::codes_url() ?>"><?php _e( 'Manage Nudge Publisher Codes', NDG_PUBLISHER_TEXTDOMAIN ); ?></a>
		</p>
		<?php
	}

	/**
	 * Save post Nudge Publisher Code.
	 *
	 * @since 0.1.0
	 *
	 * @param int $post_id Optional. The ID of the updated post.
	 */
	public function save_post_plublisher_code_id ( $post_id = null ) {
		if ( empty( $post_id ) ) {
			$post_id = $_REQUEST[ 'post_id' ];
		}

		$post_type = get_post_type( $post_id );
		if ( !$this->supports( $post_type ) ) {
			//Do nothing
			return;
		}
		if ( !$this->current_user_can( $post_type, $post_id ) ) {
			// Do nothing
			return;
		}

		$is_autosave = ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE );
		$is_revision = wp_is_post_revision( $post_id );
		if ( ( $is_autosave || $is_revision ) ) {
			//Do nothing
			return;
		}

		if ( !$this->verify_post_update_plublisher_code_id_nonce( $post_id ) ) {
			//Do nothing
			return;
		}

		$code_id = array_key_exists( 'ndg_plublisher_code_id', $_REQUEST ) ? (int)$_REQUEST[ 'ndg_plublisher_code_id' ] : null;

		$this->update_post_ndg_code_id( $post_id, $code_id );
	}

	/**
	 * Verifies nonce for update Post Nudge Inject Code
	 *
	 * @param $post_id
	 *
	 * @return bool
	 */
	private function verify_post_update_plublisher_code_id_nonce ( $post_id ) {
		$nonce = isset( $_REQUEST[ self::NONCE_NAME ] ) ? $_REQUEST[ self::NONCE_NAME ] : null;

		// Verify either an individual post nonce.
		// Requests can come from a page update, AJAX from the sidebar meta box.
		$is_nonce_valid = ( $nonce && wp_verify_nonce( $nonce, 'update-post-ndg-plublisher-code_id-' . $post_id ) );

		return $is_nonce_valid;
	}

	/**
	 * @param $post_id
	 * @param $code_id
	 */
	private function update_post_ndg_code_id ( $post_id, $code_id ) {
		if ( $code_id != $this->get_post_ndg_code_id( $post_id ) ) {
			if ( !$code_id ) {
				delete_post_meta( $post_id, '_ndg_plublisher_code_id' );
			} else {
				$code = ndgb_get_code( $code_id );
				if ( !$code ) {
					delete_post_meta( $post_id, '_ndg_plublisher_code_id' );
				} else {
					update_post_meta( $post_id, '_ndg_plublisher_code_id', $code->id );
				}
			}
		}
	}

	/**
	 * Validates if Nudge Publisher Campaign meta box is supported for given post type
	 *
	 * @param $post_type
	 *
	 * @return bool
	 */
	private function supports ( $post_type ) {
		return in_array( $post_type, Nudge_Publisher::supported_post_types() ) || post_type_supports( $post_type, 'ndg-plublisher' );
	}

	/**
	 * Whether current user can update post plublisher_code_id
	 *
	 * @param $post_type
	 *
	 * @param $post_id
	 *
	 * @return bool
	 */
	private function current_user_can ( $post_type, $post_id = null ) {
		return current_user_can( 'edit_' . $post_type, $post_id );
	}

	private function render_error ( $message, $print = true ) {
		$html = '<div class="ndg-plublisher-post-plublisher-code-id-feedback ndg-plublisher-post-plublisher-code-id-feedback-error"><p>'
			. $message
			. '</p></div>';

		if ( !$print ) {
			return $html;
		}
		echo $html;
	}

	/**
	 * Returns Nudge Code Id for given post_id.
	 *
	 * @since 0.1.0
	 *
	 * @param int $post_id ID of the post
	 *
	 * @return integer Nudge Code Id.
	 */
	private function get_post_ndg_code_id ( $post_id ) {
		return Nudge_Publisher::get_post_ndg_code_id( $post_id );
	}

}
