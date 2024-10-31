<?php

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'Nudge_Publisher_Admin_Codes' ) ) {
	return;
}

class Nudge_Publisher_Admin_Codes {
	const PAGE_NAME = 'ndg-admin-codes';

	public function __construct () {
		// Add codes page to menu
		add_action( 'admin_menu', array( $this, 'add_page' ) );
	}

	/**
	 * Add a Nudge Publisher Codes screen.
	 *
	 * @since 0.1.0
	 */
	public function add_page () {
		add_submenu_page( 'options-general.php', __( 'Nudge Publisher Codes', NDG_PUBLISHER_TEXTDOMAIN ), __( 'Nudge Publisher', NDG_PUBLISHER_TEXTDOMAIN ),
			'manage_options', self::PAGE_NAME, array( $this, 'render' ) );
	}

	public function render () {
		$page = isset( $_GET[ 'action' ] ) ? $_GET[ 'action' ] : '';
		if ( empty( $page ) ) {
			$page = isset( $_POST[ 'action' ] ) ? $_POST[ 'action' ] : '';
		}

		switch ( $page ) {
			case 'add-ndg-code':
				$this->add_code_action();
				return;
			case 'edit-ndg-code':
				$this->edit_code_action();
				return;
			case 'delete':
				$this->delete_code_action();
				return;
			case 'edit':
				?>
				<div class="wrap">
					<?php
					$this->render_code_form();
					?>
				</div>
				<?php
				return;
			case 'list':
			default:
				$this->list_action();
				return;
		}
	}

	protected function add_code_action () {
		check_admin_referer( 'ndg-add-code', '_wpnonce_ndg-add-code' );

		$ret = $this->create_code();

		$location = admin_url( "options-general.php?page=" . $_GET[ "page" ] );
		if ( $ret && !is_wp_error( $ret ) ) {
			$location = add_query_arg( 'message', 1, $location );
		} else {
			$this->set_error( $ret->get_error_message() );
		}

		wp_redirect( $location );
		exit;
	}

	protected function edit_code_action () {
		check_admin_referer( 'ndg-edit-code', '_wpnonce_ndg-edit-code' );

		$ret = $this->update_code();

		$location = admin_url( "options-general.php?page=" . $_GET[ "page" ] );
		if ( $ret && !is_wp_error( $ret ) ) {
			$location = add_query_arg( 'message', 2, $location );
		} else {
			$this->set_error( $ret->get_error_message() );
		}

		wp_redirect( $location );
		exit;
	}

	protected function delete_code_action () {
		$code_id = isset( $_GET[ 'code_id' ] ) ? $_GET[ 'code_id' ] : '';
		$location = admin_url( "options-general.php?page=" . $_GET[ "page" ] );

		if ( $code_id ) {
			check_admin_referer( 'delete-ndg-code_' . $code_id );

			$ret = $this->delete_code( $code_id );

			if ( $ret && !is_wp_error( $ret ) ) {
				$location = add_query_arg( 'message', 3, $location );
			} else {
				$this->set_error( $ret->get_error_message() );
			}
		}

		wp_redirect( $location );
		exit;
	}

	protected function list_action () {
		?>
		<div class="wrap nosubsub">
			<h2><?php _e( 'Nudge Publisher Codes', NDG_PUBLISHER_TEXTDOMAIN ); ?></h2>
			<?php
			$message = isset( $_GET[ 'message' ] ) ? $_GET[ 'message' ] : '';
			switch ( $message ) {
				case 1:
					?>
					<div id="message" class="updated"><p>
							<strong><?php _e( 'Code created.', NDG_PUBLISHER_TEXTDOMAIN ); ?></strong></p></div>
					<?php
					break;
				case 2:
					?>
					<div id="message" class="updated"><p>
							<strong><?php _e( 'Code updated.', NDG_PUBLISHER_TEXTDOMAIN ); ?></strong></p></div>
					<?php
					break;
				case 3:
					?>
					<div id="message" class="updated"><p>
							<strong><?php _e( 'Code deleted.', NDG_PUBLISHER_TEXTDOMAIN ); ?></strong></p></div>
					<?php
					break;
			}
			$error_message = $this->get_error();
			if ( $error_message ) {
				?>
				<div id="message" class="error">
					<p><strong><?php echo $error_message; ?></strong></p>
				</div>
				<?php
			}
			?>
			<div class="col-container">
				<div id="col-right">
					<div class="col-wrap">
						<?php $this->render_list(); ?>
					</div>
				</div>
				<div id="col-left">
					<div class="col-wrap">
						<?php $this->render_create_form(); ?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	protected function render_list () {
		$codes = ndgb_get_codes();
		?>
		<table class="wp-list-table widefat">
			<thead>
			<tr>
				<th class="manage-column">Name</th>
				<th class="manage-column">Code</th>
			</tr>
			</thead>
			<tbody id="the-list">
			<?php if ( empty( $codes ) ): ?>
				<tr class="no-items">
					<td class="colspanchange" colspan="2">No items found.</td>
				</tr>
			<?php else: ?>
				<?php foreach ( $codes as $code ): ?>
					<tr>
						<td>
							<strong><?php echo esc_html( $code->name ); ?></strong>
							<div class="row-actions">
                            <span class="edit"><a
									href="<?php echo 'options-general.php?page=' . $_GET[ 'page' ] . '&amp;action=edit&amp;code_id=' . $code->id ?>">Edit</a> | </span>
                            <span class="delete"><a class="delete-ndg-code"
													href="<?php echo esc_attr( wp_nonce_url( 'options-general.php?page=' . $_GET[ 'page' ] . '&amp;action=delete&amp;code_id=' . $code->id . '&amp;noheader=true', 'delete-ndg-code_' . $code->id ) ); ?>">Delete</a></span>
							</div>
						</td>
						<td><?php echo esc_html( $code->code ); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
			</tbody>
		</table>
		<div class="form-wrap">
			<p>
				<strong>Note:</strong><br>Deleting a code does not delete the assigned posts and pages. Instead, posts
				and pages that were only assigned to the deleted code are unassigned.
			</p>
		</div>
		<?php
	}

	protected function render_create_form () {
		?>
		<div class="form-wrap">
			<h3><?php _e( 'Add New Code', NDG_PUBLISHER_TEXTDOMAIN ) ?></h3>
			<form id="add-ndg-code" method="post"
				  action="<?php echo esc_attr( admin_url( 'options-general.php?page=' . $_GET[ 'page' ] . '&noheader=true' ) ) ?>">
				<input type="hidden" name="action" value="add-ndg-code"/>
				<?php wp_nonce_field( 'ndg-add-code', '_wpnonce_ndg-add-code' ); ?>

				<div class="form-field form-required ndg-code-name-wrap">
					<label for="ndg-code-name"><?php _ex( 'Name', 'code form', NDG_PUBLISHER_TEXTDOMAIN ); ?></label>
					<input name="code_name" id="ndg-code-name" type="text" value="" size="40" aria-required="true"/>
					<p><?php _e( 'The name is how it appears in the post meta box .' ); ?></p>
				</div>

				<div class="form-field form-required ndg-code-code-wrap">
					<label for="ndg-code-code"><?php _ex( 'Code', 'code form', NDG_PUBLISHER_TEXTDOMAIN ); ?></label>
                    <textarea name="code" id="ndg-code-code" rows="5" cols="40"
							  placeholder="<?php esc_attr_e( '<script type="text/javascript" src="//cdn.ndg.io/all-ndg-XXXXXXXXX.js" async></script>', NDG_PUBLISHER_TEXTDOMAIN ) ?>"
					></textarea>
				</div>
				<?php submit_button( __( 'Add New Code', NDG_PUBLISHER_TEXTDOMAIN ) ); ?>
			</form>
		</div>
		<?php
		$this->scripts();
	}

	protected function render_code_form () {
		$code = $this->get_code();

		if ( empty( $code ) ) {
			?>
			<div id="message" class="updated">
				<p><strong><?php _e( 'You did not select an item for editing.' ); ?></strong></p>
			</div>
			<?php
			return;
		}

		?>
		<div class="wrap">
			<h2><?php _e( 'Edit Code', NDG_PUBLISHER_TEXTDOMAIN ) ?></h2>
			<form id="edit-ndg-code" method="post"
				  action="<?php echo esc_attr( admin_url( 'options-general.php?page=' . $_GET[ 'page' ] . '&noheader=true' ) ) ?>">
				<input type="hidden" name="action" value="edit-ndg-code"/>
				<input type="hidden" name="code_id" value="<?php echo esc_attr( $code->id ) ?>"/>
				<?php wp_nonce_field( 'ndg-edit-code', '_wpnonce_ndg-edit-code' ); ?>
				<table class="form-table">
					<tbody>
					<tr class="form-field form-required ndg-code-name-wrap">
						<th scope="row">
							<label
								for="ndg-code-name"><?php _ex( 'Name', 'code form', NDG_PUBLISHER_TEXTDOMAIN ); ?></label>
						</th>
						<td>
							<input name="code_name" id="ndg-code-name" type="text"
								   value="<?php echo esc_attr( $code->name ) ?>"
								   size="40" aria-required="true"/>
							<p class="description"><?php _e( 'The name is how it appears in the post meta box .' ); ?></p>
						</td>
					</tr>

					<tr class="form-field form-required ndg-code-code-wrap">
						<th scope="row">
							<label
								for="ndg-code-code"><?php _ex( 'Code', 'code form', NDG_PUBLISHER_TEXTDOMAIN ); ?></label>
						</th>
						<td>
                            <textarea name="code" id="ndg-code-code" rows="5" cols="40"
									  placeholder="<?php esc_attr_e( '<script type="text/javascript" src="//cdn.ndg.io/all-ndg-XXXXXXXXX.js" async></script>', NDG_PUBLISHER_TEXTDOMAIN ) ?>"><?php echo esc_textarea( $code->code ); ?></textarea>
						</td>
					</tr>
				</table>

				<?php submit_button( __( 'Update', NDG_PUBLISHER_TEXTDOMAIN ) ); ?>
			</form>
		</div>
		<?php
		$this->scripts();
	}


	protected function create_code () {
		/* @var $wpdb wpdb */
		global $wpdb;
		$name = isset( $_POST[ 'code_name' ] ) ? $_POST[ 'code_name' ] : '';
		$code = isset( $_POST[ 'code' ] ) ? $_POST[ 'code' ] : '';

		$name = stripslashes( $name );
		$code = stripslashes( $code );

		if ( '' == trim( $name ) ) {
			return new WP_Error( 'empty_ndg_code_name', __( 'A name is required', NDG_PUBLISHER_TEXTDOMAIN ) );
		}
		if ( '' == trim( $code ) ) {
			return new WP_Error( 'empty_ndg_code', __( 'A code is required', NDG_PUBLISHER_TEXTDOMAIN ) );
		}

		$wpdb->insert( $wpdb->prefix . 'ndg_plublisher_codes', array(
			'name' => $name,
			'code' => $code
		) );

		return array(
			'id' => (int)$wpdb->insert_id,
			'name' => $name,
			'code' => $code
		);
	}

	protected function update_code () {
		/* @var $wpdb wpdb */
		global $wpdb;
		$code_id = isset( $_POST[ 'code_id' ] ) ? $_POST[ 'code_id' ] : '';
		$name = isset( $_POST[ 'code_name' ] ) ? $_POST[ 'code_name' ] : '';
		$code = isset( $_POST[ 'code' ] ) ? $_POST[ 'code' ] : '';

		$name = stripslashes( $name );
		$code = stripslashes( $code );
		$codeRow = $this->get_code( $code_id );

		if ( empty( $codeRow ) ) {
			return new WP_Error( 'ndg_code_does_not_exist', __( 'Code with given ID does not exist', NDG_PUBLISHER_TEXTDOMAIN ) );
		}

		if ( '' == trim( $name ) ) {
			return new WP_Error( 'empty_ndg_code_name', __( 'A name is required', NDG_PUBLISHER_TEXTDOMAIN ) );
		}
		if ( '' == trim( $code ) ) {
			return new WP_Error( 'empty_ndg_code', __( 'A code is required', NDG_PUBLISHER_TEXTDOMAIN ) );
		}

		$wpdb->update( $wpdb->prefix . 'ndg_plublisher_codes', array(
			'name' => $name,
			'code' => $code
		), array( 'id' => $codeRow->id ) );

		return array(
			'id' => (int)$codeRow->id,
			'name' => $name,
			'code' => $code
		);
	}

	protected function delete_code ( $code_id ) {
		/* @var $wpdb wpdb */
		global $wpdb;
		$table_name = $wpdb->prefix . 'ndg_plublisher_codes';

		$id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table_name WHERE id = %d", $code_id ) );

		if ( !$id ) {
			return new WP_Error( 'ndg_code_does_not_exist', __( 'Code with given ID does not exist', NDG_PUBLISHER_TEXTDOMAIN ) );
		}

		$wpdb->delete( $table_name, array( 'id' => $code_id ) );
		delete_metadata( 'post', null, '_ndg_plublisher_code_id', $code_id, true );

		return true;
	}

	/**
	 * @param int $code_id
	 * @return stdClass
	 */
	protected function get_code ( $code_id = null ) {
		/* @var $wpdb wpdb */
		global $wpdb;
		$table_name = $wpdb->prefix . 'ndg_plublisher_codes';

		if ( $code_id === null ) {
			$code_id = isset( $_GET[ 'code_id' ] ) ? $_GET[ 'code_id' ] : '';
		}

		if ( !$code_id ) {
			return null;
		}

		$code = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $table_name . ' WHERE id = %d', $code_id ) );

		return $code;
	}


	function set_error ( $message ) {
		set_transient( 'ndg_plublisher_codes', $message, 60 );
	}

	/**
	 * Get last errors.
	 *
	 * @return bool|mixed Error message if it exists, otherwise false
	 */
	function get_error () {
		$message = get_transient( 'ndg_plublisher_codes' );
		if ( false !== $message && $message ) {
			delete_transient( 'ndg_plublisher_codes' );

			return $message;
		}

		return false;
	}

	protected function scripts () {
		?>
		<script type="text/javascript">
			jQuery(document).ready(function ($) {
				function validate(form) {
					return !$(form)
						.find('.form-required')
						.filter(function () {
							return $('input:visible, textarea:visible', this).val() === '';
						})
						.addClass('form-invalid')
						.find('input:visible, textarea:visible')
						.change(function () {
							$(this).closest('.form-invalid').removeClass('form-invalid');
						})
						.size();
				}

				$('#submit').click(function () {
					var form = $(this).parents('form');

					if (!validate(form))
						return false;
					return true;
				});


				$('#the-list').on('click', '.delete-ndg-code', function () {
					var r = true;
					if ('undefined' != showNotice)
						r = showNotice.warn();
					if (r) {
						return true;
					}
					return false;
				});
			});
		</script>
		<?php
	}
}
