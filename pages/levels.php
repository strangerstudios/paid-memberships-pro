<?php
/**
 * Template: Levels
 * Version: 3.1
 *
 * See documentation for how to override the PMPro templates.
 * @link https://www.paidmembershipspro.com/documentation/templates/
 *
 * @version 3.1
 *
 * @author Paid Memberships Pro
 */
global $wpdb, $pmpro_msg, $pmpro_msgt, $current_user;

$pmpro_levels = pmpro_sort_levels_by_order( pmpro_getAllLevels(false, true) );
$pmpro_levels = apply_filters( 'pmpro_levels_array', $pmpro_levels );

$level_groups  = pmpro_get_level_groups_in_order();

?>
<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro' ) ); ?>">
	<?php
		if ( $pmpro_msg ) {
			?>
			<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_message ' . $pmpro_msgt, $pmpro_msgt ) ); ?>"><?php echo wp_kses_post( $pmpro_msg ); ?></div>
			<?php
		}
	?>
	<section id="pmpro_levels" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_section', 'pmpro_levels' ) ); ?>">
		<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_section_content' ) ); ?>">
			<?php
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
					?>
					<div id="pmpro_level_group-<?php echo esc_attr( $level_group->id ); ?>" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card pmpro_level_group', 'pmpro_level_group-' . esc_attr( $level_group->id ) ) ); ?>">
						<?php
							if ( count( $level_groups ) > 1  ) {
								?>
								<h2 class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_title pmpro_font-large' ) ); ?>"><?php echo esc_html( $level_group->name ); ?></h2>
								<?php
							}
						?>
						<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">
							<?php
								if ( count( $level_groups ) > 1  ) {
									if ( ! empty( $level_group->allow_multiple_selections ) ) {
										?>
										<p><?php esc_html_e( 'You may select multiple levels from this group.', 'paid-memberships-pro' ); ?></p>
										<?php
									} else {
										?>
										<p><?php esc_html_e( 'You may select only one level from this group.', 'paid-memberships-pro' ); ?></p>
										<?php
									}
									?>
									<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_spacer' ) ); ?>"></div>
									<?php
								}
							?>
							<table class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_table pmpro_levels_table', 'pmpro_levels_table' ) ); ?>">
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

										// Build the selectors for the single level elements.
										$element_classes = array();
										$element_classes[] = 'pmpro_level';
										if ( $has_level ) {
											$element_classes[] = 'pmpro_level-current';
										}
										$element_class = implode( ' ', array_unique( $element_classes ) );
									?>
									<tr id="pmpro_level-<?php echo esc_attr( $level->id ); ?>" class="<?php echo esc_attr( pmpro_get_element_class( $element_class, 'pmpro_level-' . esc_attr( $level->id ) ) ); ?>">
										<th data-title="<?php esc_attr_e( 'Level', 'paid-memberships-pro' ); ?>"><?php echo esc_html( $level->name ); ?></th>
										<td data-title="<?php esc_attr_e( 'Price', 'paid-memberships-pro' ); ?>">
											<?php
												$cost_text = pmpro_getLevelCost( $level, true, true );
												if ( ! empty($cost_text ) ) {
													?>
													<p class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_level-price' ) ); ?>"><?php echo wp_kses_post( $cost_text ); ?></p>
													<?php
												}

												$expiration_text = pmpro_getLevelExpiration($level);
												if ( ! empty($expiration_text ) ) {
													?>
													<p class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_level-expiration' ) ); ?>"><?php echo wp_kses_post( $expiration_text ); ?></p>
													<?php
												}
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
														<a aria-label="<?php echo esc_attr( sprintf( __('Renew your %s membership level', 'paid-memberships-pro' ), $level->name ) ); ?>" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_btn pmpro_btn-renew pmpro_btn-select', 'pmpro_btn-select' ) ); ?>" href="<?php echo esc_url( pmpro_url( "checkout", "?pmpro_level=" . $level->id, "https" ) ) ?>"><?php esc_html_e('Renew', 'paid-memberships-pro' );?></a>
													<?php
												} else {
													?>
														<a aria-label="<?php echo esc_attr( sprintf( __('View your %s membership account', 'paid-memberships-pro' ), $level->name ) ); ?>" class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_btn pmpro_btn-outline', 'pmpro_btn' ) ); ?>" href="<?php echo esc_url( pmpro_url( "account" ) ) ?>"><?php esc_html_e('Your&nbsp;Level', 'paid-memberships-pro' );?></a>
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
						</div> <!-- end pmpro_card_content -->
					</div> <!-- end pmpro_card -->
					<?php
				}
			?>
		</div> <!-- end pmpro_section_content -->
	</section> <!-- end pmpro_section -->
</div> <!-- end pmpro -->
