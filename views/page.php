<style>
#mailster-map li{
	border-bottom: 1px solid #CCC;
}
#mailster-map li label{
	display: inline-block;
	width: 150px;
}
#mailster-map li label select{
	display: inline-block;
	width: 200px;
}
#mailster-map li ul li{
	border-bottom: 0;
	padding-left: 20px;
}

</style>
<?php

if ( ! function_exists( 'mailster' ) ) {

	echo '<h3>Please enable the <a href="https://mailster.co/?utm_campaign=wporg&utm_source=Gravity+Forms+Mailster+Addon">Mailster Newsletter Plugin</a></h3>';

	return;
}

	$form_id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : null;
	$form = RGFormsModel::get_form_meta( $form_id );
	$mailster = isset( $form['mailster'] ) ? $form['mailster'] : array( 'lists' => array(), 'map' => array() );

?>
<div class="gform_panel gform_panel_mailster_settings" id="mailster_settings">

		<h3><span><?php _e( 'Mailster Settings', 'mailster-gravityforms' ) ?></span></h3>

		<form action="" method="post" id="gform_form_settings">

		<table class="gforms_form_settings" cellspacing="0" cellpadding="0">
			<tr>
				<th></th>
				<td><label><input type="checkbox" name="mailster[active]" value="1" <?php checked( isset( $mailster['active'] ) ) ?>> <?php _e( 'Enable Mailster for this Form', 'mailster-gravityforms' ) ?></label>
				</td>
			</tr>
			<tr>
				<th><?php _e( 'Map Fields', 'mailster-gravityforms' ) ?></th>
				<td>
				<p class="description"><?php _e( 'define which field represents which value from your Mailster settings', 'mailster-gravityforms' ) ?></p>
				<?php
				$fields = array(
					'email' => mailster_text( 'email' ),
					'firstname' => mailster_text( 'firstname' ),
					'lastname' => mailster_text( 'lastname' ),
				);

				if ( $customfields = mailster()->get_custom_fields() ) {
					foreach ( $customfields as $field => $data ) {
						$fields[ $field ] = $data['name'];
					}
				}
				$optionsdd = '<option value="-1">' . __( 'choose', 'mailster-gravityforms' ) . '</option>';
				foreach ( $fields as $id => $name ) {
					$optionsdd .= '<option value="' . $id . '">' . $name . '</option>';
				}

				if ( is_array( $form['fields'] ) && ! empty( $form['fields'] ) ) {
					echo '<ul id="mailster-map">';
					foreach ( $form['fields'] as $field ) {
						if ( isset( $field['inputs'] ) && is_array( $field['inputs'] ) ) {
							echo '<li><strong>' . ( ! empty( $field['label'] ) ? $field['label'] : __( 'Untitled', 'mailster-gravityforms' )) . ':</strong><ul>';

							foreach ( $field['inputs'] as $input ) {
								echo '<li><label>' . $input['label'] . '</label> ➨ <select name="mailster[map][' . $input['id'] . ']" >';
									echo '<option value="-1">' . __( 'not mapped', 'mailster-gravityforms' ) . '</option>';
								foreach ( $fields as $id => $name ) {
									echo '<option value="' . $id . '" ' . selected( $id, @$mailster['map'][ $input['id'] . '' ], false ) . '>' . $name . '</option>';
								}
								echo '</select></li>';
							}

							echo '</ul></li>';

						} else {
							echo '<li> <label><strong>' . $field['label'] . '</strong></label> ➨ <select name="mailster[map][' . $field['id'] . ']">';
								echo '<option value="-1">' . __( 'not mapped', 'mailster-gravityforms' ) . '</option>';
							foreach ( $fields as $id => $name ) {
								echo '<option value="' . $id . '" ' . selected( $id, @$mailster['map'][ $field['id'] . '' ], false ) . '>' . $name . '</option>';
							}
							echo '</select></li>';
						}
					}
					echo '</ul>';
				} else {
					_e( 'no fields defined!', 'mailster-gravityforms' );
				}

				?>

				</td>
			</tr>
			<tr>
				<th><?php _e( 'Subscribe new users to', 'mailster-gravityforms' ) ?></th>
				<td>
				<?php
				$selected = isset( $mailster['lists'] ) ? $mailster['lists'] : array();
				mailster( 'lists' )->print_it( null, null, $name = 'mailster[lists]', false, $selected );
				?>
				</td>
			</tr>
			<tr>
				<th><?php _e( 'Double Opt In', 'mailster-gravityforms' ) ?></th>
				<td><label><input type="checkbox" name="mailster[double-opt-in]" value="1" <?php checked( isset( $mailster['double-opt-in'] ) ) ?>> <?php _e( 'Users have to confirm their subscription', 'mailster-gravityforms' ) ?></label><br>

				</td>
			</tr>

			<tr>
				<th><?php _e( 'Conditional check', 'mailster-gravityforms' ) ?></th>
				<td><label><input type="checkbox" name="mailster[conditional]" value="1" <?php checked( isset( $mailster['conditional'] ) ) ?>> <?php _e( 'Enable Conditional check', 'mailster-gravityforms' ) ?></label>
				<p><?php _e( 'subscribe user only if', 'mailster-gravityforms' ) ?>
				<?php
				if ( is_array( $form['fields'] ) ) {
					echo '<select name="mailster[conditional_field]"><option value="-1">-</option>';
					foreach ( $form['fields'] as $field ) {
						if ( ! in_array( $field['type'], array( 'checkbox', 'radio' ) ) ) { continue; }

						if ( isset( $field['inputs'] ) && is_array( $field['inputs'] ) ) {
							echo '<optgroup label="' . ($field['label'] ? $field['label'] : __( 'Checkbox', 'mailster-gravityforms' )) . '">';
							foreach ( $field['inputs'] as $input ) {
								echo '<option value="' . $input['id'] . '" ' . selected( $input['id'], $mailster['conditional_field'], false ) . '>' . $input['label'] . '</option>'; }
							echo '</optgroup>';

						} elseif ( isset( $field['choices'] ) && is_array( $field['choices'] ) ) {
							echo '<optgroup label="' . $field['label'] . '">';
							foreach ( $field['choices'] as $input ) {
								echo '<option value="' . $field['id'] . '|' . $input['value'] . '" ' . selected( $input['value'], $mailster['conditional_field'], false ) . '>' . $input['text'] . '</option>'; }
							echo '</optgroup>';

						} else {
							echo '<option value="' . $field['id'] . '" ' . selected( $input['id'], $mailster['conditional_field'], false ) . '>sss' . $field['label'] . '</option>';
						}
					}
					echo '</select>';
				}
				?>
				<?php _e( 'is checked', 'mailster-gravityforms' ) ?></p>
				</td>
			</tr>
		</table>
		<?php wp_nonce_field( 'mailster_gf_save_form', 'gform_save_form_settings' ); ?>
		<input type="hidden" id="gform_meta" name="gform_meta">
		<input type="submit" id="gform_save_settings" name="gform_save_settings" value="<?php _e( 'Update Form Settings', 'mailster-gravityforms' ) ?>" class="button-primary gfbutton">

		</form>


</div>
