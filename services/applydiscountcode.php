<?php	
	//in case the file is loaded directly
	if( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	//vars
	global $wpdb;
	if(!empty($_REQUEST['code']))
	{
		$discount_code = preg_replace( "/[^A-Za-z0-9\-]/", "", sanitize_text_field( $_REQUEST['code'] ) );
		$discount_code_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $wpdb->pmpro_discount_codes WHERE code = %s LIMIT 1", $discount_code ) );
	}
	else
	{
		$discount_code = "";
		$discount_code_id = "";
	}

	if ( ! empty( $_REQUEST['level'] ) ) {
		$level_str = sanitize_text_field( $_REQUEST['level'] );
		$level_str = str_replace( ' ', '+', $level_str ); // If val passed via URL, + would be converted to space.
		$level_ids = array_map( 'intval', explode( '+', $level_str ) );
	} else {
		$level_ids = null;
	}

	if(!empty($_REQUEST['msgfield']))
		$msgfield = preg_replace("/[^A-Za-z0-9\_\-]/", "", sanitize_text_field( $_REQUEST['msgfield'] ) );
	else
		$msgfield = NULL;

	//check that the code is valid
	$codecheck = pmpro_checkDiscountCode($discount_code, $level_ids, true);
	if($codecheck[0] == false)
	{
		//uh oh. show code error
		echo esc_html( pmpro_no_quotes($codecheck[1]) );
		?>
		<script>
			jQuery('#<?php echo esc_attr( $msgfield ); ?>').show();
			jQuery('#<?php echo esc_attr( $msgfield ); ?>').removeClass('pmpro_success');
			jQuery('#<?php echo esc_attr( $msgfield ); ?>').addClass('pmpro_error');
			jQuery('#<?php echo esc_attr( $msgfield ); ?>').addClass('pmpro_discount_code_msg');
			jQuery('#<?php echo esc_attr( $msgfield ); ?>').attr('role', 'alert');

			var code_level;
			code_level = false;

			//filter to insert your own code. Not MMPU compatible.
			<?php do_action('pmpro_applydiscountcode_return_js', $discount_code, $discount_code_id, empty( $level_ids ) ? null : $level_ids[0], false); ?>
		</script>
		<?php

		exit(0);
	}

	// Okay, send back new price info.
	// Find levels whose price this code changed...
	$sqlQuery = "
		SELECT l.id, cl.*, l.name, l.description, l.allow_signups 
		FROM $wpdb->pmpro_discount_codes_levels cl 
			LEFT JOIN $wpdb->pmpro_membership_levels l
				ON cl.level_id = l.id 
			LEFT JOIN $wpdb->pmpro_discount_codes dc
				ON dc.id = cl.code_id WHERE dc.code = '" . esc_sql( $discount_code ) . "'
				AND cl.level_id IN (" . implode( ',', array_map( 'intval', $level_ids ) ) . ")";
	$code_levels = $wpdb->get_results($sqlQuery);

	// ... and then get prices for the remaining levels.
	$levels_found = array();
	foreach( $code_levels as $code_level ) {
		$levels_found[] = intval( $code_level->level_id );
	}
	if ( ! empty( array_diff( $level_ids, $levels_found ) ) ) {
		$sqlQuery = "SELECT * FROM $wpdb->pmpro_membership_levels WHERE id IN (" . implode( ',', array_map( 'intval', array_diff( $level_ids, $levels_found ) ) ) . ")";
		$code_levels = array_merge( $code_levels, $wpdb->get_results($sqlQuery) );
	}

	//filter adjustments to the level
	if ( count( $code_levels ) <= 1 ) {
		// Should return just a single level object or null.
		$code_levels = array( apply_filters("pmpro_discount_code_level", empty( $code_levels ) ? null : $code_levels[0], $discount_code_id) );
	} else {
		// Should return an array of levels objects.
		$code_levels = apply_filters("pmpro_discount_code_level", $code_levels, $discount_code_id);
	}

	echo esc_html( sprintf( __( "The %s code has been applied to your order. ", 'paid-memberships-pro' ), $discount_code ) );

	$combined_level = null;
	foreach ( $code_levels as $code_level ) {
		if ( empty( $combined_level ) ) {
			$combined_level = clone $code_level;
		} else {
			$combined_level->initial_payment = $combined_level->initial_payment + $code_level->initial_payment;
			$combined_level->billing_amount = $combined_level->billing_amount + $code_level->billing_amount;
		}
	}


	?>
	<script>
		var code_level = <?php echo json_encode($combined_level); ?>;

		jQuery('#<?php echo esc_attr( $msgfield ); ?>').show();
		jQuery('#<?php echo esc_attr( $msgfield ); ?>').removeClass('pmpro_error');
		jQuery('#<?php echo esc_attr( $msgfield ); ?>').addClass('pmpro_success');
		jQuery('#<?php echo esc_attr( $msgfield ); ?>').addClass('pmpro_discount_code_msg');
		jQuery('#<?php echo esc_attr( $msgfield ); ?>').attr('role', 'alert');

		if (jQuery("#discount_code").length) {
			jQuery('#discount_code').val('<?php echo esc_attr( $discount_code );?>');
		} else {
			jQuery('<input>').attr({
				type: 'hidden',
				id: 'discount_code',
				name: 'discount_code',
				value: '<?php echo esc_attr( $discount_code );?>'
			}).appendTo('#pmpro_form');
		}

		jQuery('#other_discount_code_tr').hide();
		jQuery('#other_discount_code_p').html('<button type="button" id="other_discount_code_toggle"><?php esc_html_e('Click here to change your discount code', 'paid-memberships-pro' );?></button>');
		jQuery('#other_discount_code_p').show();

		jQuery('#other_discount_code_toggle').click(function() {
			jQuery('#other_discount_code_tr').show();
			jQuery('#other_discount_code_p').hide();
		});

			<?php
			$html = [];

			$html[] = '<p class="' . pmpro_get_element_class( 'pmpro_level_discount_applied' ) . '">' . wp_kses_post( sprintf( __( 'The <strong>%s</strong> code has been applied to your order.', 'paid-memberships-pro' ), $discount_code ) ) . '</div>';

			if ( count( $code_levels ) <= 1 ) {
				$code_level = empty( $code_levels ) ? null : $code_levels[0];

				$level_cost_text = pmpro_getLevelCost( $code_level );
				if ( ! empty( $level_cost_text ) ) {
					$html[] = '<div class="' . pmpro_get_element_class( 'pmpro_level_cost_text' ) . '">' . wpautop( $level_cost_text ) . '</div>';
				}

				$level_expiration_text = pmpro_getLevelExpiration( $code_level );
				if ( ! empty( $level_expiration_text ) ) {
					$html[] = '<div class="' . pmpro_get_element_class( 'pmpro_level_expiration_text' ) . '">' . wpautop( $level_expiration_text ) . '</div>';
				}
			} else {
				$levels_cost_text = pmpro_getLevelsCost( $code_levels );
				if ( ! empty( $levels_cost_text ) ) {
					$html[] = '<div class="' . pmpro_get_element_class( 'pmpro_level_cost_text' ) . '">' . wpautop( $levels_cost_text ) . '</div>';
				}

				$levels_expiration_text = pmpro_getLevelsExpiration( $code_levels );
				if ( ! empty( $levels_expiration_text ) ) {
					$html[] = '<div class="' . pmpro_get_element_class( 'pmpro_level_expiration_text' ) . '">' . wpautop( $levels_expiration_text ) . '</div>';
				}
			}

			$html = array_filter( $html );
			$html = implode( "\n\n", $html );
			?>
				jQuery('#pmpro_level_cost').html( <?php echo wp_json_encode( wp_kses_post( $html ) ); ?> );
			<?php

			//tell gateway javascripts whether or not to fire (e.g. no Stripe on free levels)
			if(pmpro_areLevelsFree($code_levels))
			{
			?>
				pmpro_require_billing = false;
			<?php
			}
			else
			{
			?>
				pmpro_require_billing = true;
			<?php
			}

			//hide/show billing
			if(pmpro_areLevelsFree($code_levels) || pmpro_getGateway() == "paypalexpress" || pmpro_getGateway() == "paypalstandard" || pmpro_getGateway() == 'check')
			{
				?>
				jQuery('#pmpro_billing_address_fields').hide();
				jQuery('#pmpro_payment_information_fields').hide();
				<?php
			}
			else
			{
				?>
				jQuery('#pmpro_billing_address_fields').show();
				jQuery('#pmpro_payment_information_fields').show();
				<?php
			}

			if ( pmpro_getGateway() == "paypal" && true == apply_filters('pmpro_include_payment_option_for_paypal', true ) ) {
				if ( pmpro_areLevelsFree($code_levels) ) {
					?> jQuery('#pmpro_payment_method').hide(); <?php
				} else {
					?> jQuery('#pmpro_payment_method').show(); <?php
				}
			}

			//hide/show paypal button
			if(pmpro_getGateway() == "paypalexpress" || pmpro_getGateway() == "paypalstandard")
			{
				if(pmpro_areLevelsFree($code_levels))
				{
					?>
					jQuery('#pmpro_paypalexpress_checkout').hide();
					jQuery('#pmpro_submit_span').show();
					<?php
				}
				else
				{
					?>
					jQuery('#pmpro_submit_span').hide();
					jQuery('#pmpro_paypalexpress_checkout').show();
					<?php
				}
			}

			//filter to insert your own code. Not MMPU compatible.
			do_action('pmpro_applydiscountcode_return_js', $discount_code, $discount_code_id, empty( $level_ids ) ? null : $level_ids[0], empty( $code_levels ) ? null : $code_levels[0]);
		?>
	</script>
