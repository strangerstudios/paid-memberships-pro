<?php
/**
 * Template: Levels
 * Version: 3.0.1
 *
 * See documentation for how to override the PMPro templates.
 * @link https://www.paidmembershipspro.com/documentation/templates/
 *
 * @version 3.0.1
 *
 * @author Paid Memberships Pro
 */
global $wpdb, $pmpro_msg, $pmpro_msgt, $current_user;

$pmpro_levels = pmpro_sort_levels_by_order( pmpro_getAllLevels(false, true) );
$pmpro_levels = apply_filters( 'pmpro_levels_array', $pmpro_levels );

$level_groups  = pmpro_get_level_groups_in_order();

if($pmpro_msg)
{
?>
<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_message ' . $pmpro_msgt, $pmpro_msgt ) ); ?>"><?php echo wp_kses_post( $pmpro_msg ); ?></div>
<?php
}
foreach ( $level_groups as $level_group ) {
	$levels_in_group = pmpro_get_level_ids_for_group( $level_group->id );

	// The pmpro_levels_array filter is sometimes used to hide levels from the levels page.
	// Let's make sure that every level in the group should still be displayed.
	$levels_to_show_for_group = array();
	foreach ( $pmpro_levels as $level ) {
		if ( in_array( $level->id, $levels_in_group ) ) {
			$levels_to_show_for_group[] = $level;
		}
	}

	if ( empty( $levels_to_show_for_group ) ) {
		continue;
	}

	if ( count( $level_groups ) > 1  ) {
		?>
		<h2><?php echo esc_html( $level_group->name ); ?></h2>
		<?php
		if ( ! empty( $level_group->allow_multiple_selections ) ) {
			?>
			<p><?php esc_html_e( 'You may select multiple levels from this group.', 'paid-memberships-pro' ); ?></p>
			<?php
		} else {
			?>
			<p><?php esc_html_e( 'You may select only one level from this group.', 'paid-memberships-pro' ); ?></p>
			<?php
		}
	}
	
	?>
	<table class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_table pmpro_levels_table pmpro_checkout', 'pmpro_levels_table' ) ); ?>">
	<thead>
	<tr>
		<th><?php esc_html_e('Level', 'paid-memberships-pro' );?></th>
		<th><?php esc_html_e('Price', 'paid-memberships-pro' );?></th>	
		<th>&nbsp;</th>
	</tr>
	</thead>
	<tbody>
		<?php	
		$count = 0;
		foreach($levels_to_show_for_group as $level)
		{
			$user_level = pmpro_getSpecificMembershipLevelForUser( $current_user->ID, $level->id );
			$has_level = ! empty( $user_level );
		?>
		<tr class="<?php if($count++ % 2 == 0) { ?>odd<?php } ?><?php if( $has_level ) { ?> active<?php } ?>">
			<th><?php echo $has_level ? '<strong>' . esc_html( $level->name ) . '</strong>' : esc_html( $level->name )?></th>
			<td>
				<?php
					$cost_text = pmpro_getLevelCost($level, true, true); 
					$expiration_text = pmpro_getLevelExpiration($level);
					if(!empty($cost_text) && !empty($expiration_text))
						echo wp_kses_post( $cost_text . "<br />" . $expiration_text );
					elseif(!empty($cost_text))
						echo wp_kses_post( $cost_text );
					elseif(!empty($expiration_text))
						echo wp_kses_post( $expiration_text );
				?>
			</td>
			<td>
			<?php if ( ! $has_level ) { ?>                	
				<a aria-label="<?php echo esc_attr( sprintf( __('Select the %s membership level', 'paid-memberships-pro' ), $level->name ) ); ?>" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_btn pmpro_btn-select', 'pmpro_btn-select' ) ); ?>" href="<?php echo esc_url( pmpro_url( "checkout", "?pmpro_level=" . $level->id, "https" ) ) ?>"><?php esc_html_e('Select', 'paid-memberships-pro' );?></a>
			<?php } else { ?>      
				<?php
					//if it's a one-time-payment level, offer a link to renew	
					if( pmpro_isLevelExpiringSoon( $user_level ) && $level->allow_signups ) {
						?>
							<a aria-label="<?php echo esc_attr( sprintf( __('Renew your %s membership level', 'paid-memberships-pro' ), $level->name ) ); ?>" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_btn pmpro_btn-select', 'pmpro_btn-select' ) ); ?>" href="<?php echo esc_url( pmpro_url( "checkout", "?pmpro_level=" . $level->id, "https" ) ) ?>"><?php esc_html_e('Renew', 'paid-memberships-pro' );?></a>
						<?php
					} else {
						?>
							<a aria-label="<?php echo esc_attr( sprintf( __('View your %s membership account', 'paid-memberships-pro' ), $level->name ) ); ?>" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_btn disabled', 'pmpro_btn' ) ); ?>" href="<?php echo esc_url( pmpro_url( "account" ) ) ?>"><?php esc_html_e('Your&nbsp;Level', 'paid-memberships-pro' );?></a>
						<?php
					}
				?>
			<?php } ?>
			</td>
		</tr>
		<?php
		}
		?>
	</tbody>
	</table>
<?php } ?>
