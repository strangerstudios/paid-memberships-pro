<?php
// Only admins can access this page.
if( !function_exists( "current_user_can" ) || ( !current_user_can( "manage_options" ) && 
	!current_user_can( "pmpro_taxsettings" ) ) ) {
	die( esc_html__( "You do not have permissions to perform this action.", 'paid-memberships-pro' ) );
}

global $msg, $msgt, $pmpro_countries;

// Bail if nonce field isn't set.
if ( !empty( $_REQUEST['savesettings'] ) && ( empty( $_REQUEST[ 'pmpro_taxsettings_nonce' ] ) 
	|| !check_admin_referer( 'savesettings', 'pmpro_taxsettings_nonce' ) ) ) {
	$msg = -1;
	$msgt = __( "Are you sure you want to do that? Try again.", 'paid-memberships-pro' );
	unset( $_REQUEST[ 'savesettings' ] );
}

// Save settings.
if( ! empty( $_REQUEST['savesettings'] ) ) {
	$tax_rates = $_REQUEST['tax_rates'];
	$validated_tax_rates = array();

	// Validate the tax rates.
	foreach( $tax_rates as $tax_rate ) {
		// Validate the tax rate.
		$tax_rate['country'] = empty( $tax_rate['country'] ) ? '*' : sanitize_text_field( $tax_rate['country'] );
		$tax_rate['state'] = empty( $tax_rate['state'] ) ? '*' : sanitize_text_field( $tax_rate['state'] );
		$tax_rate['city'] = empty( $tax_rate['city'] ) ? '*' : sanitize_text_field( $tax_rate['city'] );
		$tax_rate['zip'] = empty( $tax_rate['zip'] ) ? '*' : sanitize_text_field( $tax_rate['zip'] );
		$tax_rate['rate'] = empty( $tax_rate['rate'] ) ? 0 : (float) $tax_rate['rate'];

		$validated_tax_rates[] = $tax_rate;
	}

	// Sort the tax rates making sure that wildcard tax rates are at the end.
	usort( $validated_tax_rates, function( $a, $b ) {
		$order = array(
			strcmp( $a['country'], $b['country'] ),
			strcmp( $a['state'], $b['state'] ),
			strcmp( $a['city'], $b['city'] ),
			strcmp( $a['zip'], $b['zip'] ),
		);
	
		// Return the first nonzero comparison result
		foreach ($order as $cmp) {
			if ($cmp !== 0) return $cmp;
		}
		return 0;
	} );

	// Save the tax rates.
	update_option( 'pmpro_tax_rates', $validated_tax_rates );

	$msg = true;
	$msgt = esc_html__( 'Your tax settings have been updated.', 'paid-memberships-pro' );

}

// Get settings.
$tax_rates = get_option( 'pmpro_tax_rates' );
if ( empty( $tax_rates ) ) {
	$tax_rates = array();
}

// Load the admin header.
require_once( dirname(__FILE__) . '/admin_header.php' );

?>
<form action="" method="POST" enctype="multipart/form-data">
	<?php wp_nonce_field( 'savesettings', 'pmpro_taxsettings_nonce' );?>
	<hr class="wp-header-end">
	<h1><?php esc_html_e( 'Tax Settings', 'paid-memberships-pro' );?></h1>
	<div class="pmpro_section" data-visibility="shown" data-activated="true">
		<div class="pmpro_section_toggle">
			<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
				<span class="dashicons dashicons-arrow-up-alt2"></span>
				<?php esc_html_e( 'Tax Rates', 'paid-memberships-pro' ); ?>
			</button>
		</div>
		<div class="pmpro_section_inside">
			<p><?php esc_html_e( 'This feature currently only support tax-inclusive pricing. This means that every user will be charged the level price regardless of their location. After a payment is made, the total paid will then be broken down into tax and subtotal. This helps to ensure regulartory compliance in situations where a tax rate changes after a payment subscription has already been created.', 'paid-memberships-pro' ); ?></p>
			<p><?php printf( esc_html__( 'If tax-exclusive pricing is needed, the best way to add this functionality is by having your payment gateeway handle tax calculations during the payment step. This is currently possible with %s.', 'paid-memberships-pro' ), '<a href="https://www.paidmembershipspro.com/gateway/stripe/stripe-checkout/" target="_blank">'.esc_html__( 'Stripe Checkout', 'paid-memberships-pro' ).'</a>' ); ?></p>
			<p><strong><?php esc_html_e( 'Paid Memberships Pro takes no responsibility for taxes or legal obligations. The site owner must set tax rates and handle all tax requirements.', 'paid-memberships-pro' ); ?></strong></p>

			<table class="form-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Tax Country', 'paid-memberships-pro' ); ?></th>
						<th><?php esc_html_e( 'Tax State', 'paid-memberships-pro' ); ?></th>
						<th><?php esc_html_e( 'Tax City', 'paid-memberships-pro' ); ?></th>
						<th><?php esc_html_e( 'Tax Zip', 'paid-memberships-pro' ); ?></th>
						<th><?php esc_html_e( 'Tax Rate', 'paid-memberships-pro' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'paid-memberships-pro' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					$max_index = 0;
					foreach( $tax_rates as $index => $tax_rate ) {
						$max_index = max( $max_index, $index );
						?>
						<tr>
							<td>
								<select name="tax_rates[<?php echo esc_attr( $index ); ?>][country]">
									<option value="*"><?php esc_html_e( 'All Countries', 'paid-memberships-pro' ); ?></option>
									<?php foreach( $pmpro_countries as $country_code => $country_name ) { ?>
										<option value="<?php echo esc_attr( $country_code ); ?>" <?php selected( $tax_rate['country'], $country_code ); ?>><?php echo esc_html( $country_name ); ?></option>
									<?php } ?>
								</select>
							</td>
							<td><input type="text" name="tax_rates[<?php echo esc_attr( $index ); ?>][state]" value="<?php echo esc_attr( $tax_rate['state'] ); ?>" /></td>
							<td><input type="text" name="tax_rates[<?php echo esc_attr( $index ); ?>][city]" value="<?php echo esc_attr( $tax_rate['city'] ); ?>" /></td>
							<td><input type="text" name="tax_rates[<?php echo esc_attr( $index ); ?>][zip]" value="<?php echo esc_attr( $tax_rate['zip'] ); ?>" /></td>
							<td><input type="text" name="tax_rates[<?php echo esc_attr( $index ); ?>][rate]" value="<?php echo esc_attr( $tax_rate['rate'] ); ?>" />%</td>
							<td><button type="button" class="button pmpro-tax-button-remove"><?php esc_html_e( 'Remove', 'paid-memberships-pro' ); ?></button></td>
						</tr>
						<?php
					}
					?>
				</tbody>
				<tfoot>
					<tr>
						<td colspan="6">
							<button type="button" class="button pmpro-tax-button-add"><?php esc_html_e( 'Add New Tax Rate', 'paid-memberships-pro' ); ?></button>
						</td>
					</tr>
				</tfoot>
				<script>
					jQuery(document).ready(function($) {
						$('.pmpro-tax-button-remove').click(function() {
							$(this).closest('tr').remove();
						});

						// Track the last index added.
						var lastIndex = <?php echo intval( $max_index ); ?>;

						// Build the country options once so that we can use it for all new rows.
						var countryOptions = '<option value="*"><?php esc_html_e( 'All Countries', 'paid-memberships-pro' ); ?></option>';
						<?php foreach( $pmpro_countries as $country_code => $country_name ) { ?>
							countryOptions += '<option value="<?php echo esc_attr( $country_code ); ?>"><?php echo esc_html( $country_name ); ?></option>';
						<?php } ?>

						$('.pmpro-tax-button-add').click(function() {
							// Get the index of the new row
							var newIndex = lastIndex + 1;

							// Build the new row.
							var newRow = $('<tr>');
							newRow.append('<td><select name="tax_rates[' + newIndex + '][country]">' + countryOptions + '</select></td>');
							newRow.append('<td><input type="text" name="tax_rates[' + newIndex + '][state]" value="*" /></td>');
							newRow.append('<td><input type="text" name="tax_rates[' + newIndex + '][city]" value="*" /></td>');
							newRow.append('<td><input type="text" name="tax_rates[' + newIndex + '][zip]" value="*" /></td>');
							newRow.append('<td><input type="text" name="tax_rates[' + newIndex + '][rate]" value="0" /></td>');
							newRow.append('<td><button type="button" class="button pmpro-tax-button-remove"><?php esc_html_e( 'Remove', 'paid-memberships-pro' ); ?></button></td>');

							// Insert the new row at the end of the tbody in this table.
							$('tbody').append(newRow);

							// Make sure that this row can be removed.
							newRow.find('.pmpro-tax-button-remove').click(function() {
								$(this).closest('tr').remove();
							});

							// Update the last index.
							lastIndex++;

						});
					});
				</script>
			</table>
		</div>
	</div>
	<div class="submit">
		<input name="savesettings" type="submit" class="button button-primary" value="<?php esc_attr_e('Save Settings', 'paid-memberships-pro' );?>" />
	</div>
</form>
<?php

// Load the admin footer.
require_once( dirname(__FILE__) . '/admin_footer.php' );