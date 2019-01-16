

<input type="hidden" value="1" name="config[fields][{{_id}}][required]" class="field-config">

<p class="description" style="text-align:center;">
	<?php esc_html_e( 'Enter a form ID below which has another Zoho processor enabled.', 'lsx-cf-zoho' ); ?>
</p>
<?php /*
<div class="caldera-config-group">
	<label for="{{_id}}_form_id">
		<?php esc_html_e( 'Select your form', 'lsx-cf-zoho' ); ?>
	</label>
	<div class="caldera-config-field">
		<select id="{{_id}}_form_id" class="block-input field-config required" name="{{_name}}[form_id]">
			<option value="0"><?php esc_html_e( 'Select a form', 'lsx-cf-zoho' ); ?></option>

			<?php if ( is_array( $options ) ) {
				foreach ( $options as $option_key => $option_value ) {
					echo wp_kses_post( '<option {{#is form_id value="' . $option_key . '"}}selected="selected"{{/is}} value="' . $option_key . '">' . $option_value . '</option>' );
				}
			} ?>
		</select>
	</div>
</div>
 */ ?>

<div class="caldera-config-group">
	<label for="{{_id}}_form_id">
		<?php esc_html_e( 'Form ID', 'lsx-cf-zoho' ); ?>
	</label>
	<div class="caldera-config-field">
		<input type="text" id="{{_id}}_form_id" class="block-input field-config magic-tag-enabled" name="{{_name}}[form_id]" value="{{form_id}}">
	</div>
</div>

<div class="caldera-config-group">
	<label for="{{_id}}_limit">
		<?php esc_html_e( 'Limit', 'lsx-cf-zoho' ); ?>
	</label>
	<div class="caldera-config-field">
		<input type="text" id="{{_id}}_limit" class="block-input field-config magic-tag-enabled" name="{{_name}}[limit]" value="{{limit}}">
	</div>
</div>
