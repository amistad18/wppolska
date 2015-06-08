<?php
/**
 * Importer view file
 *
 * Displays importer form
 *
 * @since 1.0.0
 *
 * @package wppolska-fb-importer
 */
?>

<div class="wrap">
	
	<h2><?php _e( 'Facebook Event Importer', WPPOLSKA_TEXT_DOMAIN ); ?></h2>

	<form action="<?php echo admin_url( 'admin-post.php' ); ?>" method="post">

		<?php wp_nonce_field( 'wppfbi_add_event', 'nonce' ); ?>

		<input type="hidden" name="action" value="wppfbi_add_event">
		
		<table class="form-table">

			<tr>
				<th scope="row"><label for="event"><?php _e( 'Event URL or ID', WPPOLSKA_TEXT_DOMAIN ); ?></label></th>
				<td><input type="text" name="event" id="event" class="regular-text" placeholder="https://www.facebook.com/events/XXXXXXXXXXXXXXXX/" required="required"></td>
			</tr>

			<tr>
				<th scope="row"><label for="event_type"><?php _e( 'Event Type', WPPOLSKA_TEXT_DOMAIN ); ?></label></th>
				<td>
					<select type="text" name="event_type" id="event_type">
						<option>WordUp</option>
						<option>WordCamp</option>
					</select>
				</td>
			</tr>

			<tr>
				<th scope="row"></th>
				<td><?php submit_button( __( 'Add Event', WPPOLSKA_TEXT_DOMAIN ), 'primary', null, false ); ?></td>
			</tr>

		</table>

	</form>

</div>