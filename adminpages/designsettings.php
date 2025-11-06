<?php
	// Only admins can get to this screen.
	if ( ! function_exists( 'current_user_can' ) || ( ! current_user_can( 'manage_options' ) && ! current_user_can('pmpro_designsettings' ) ) ) {
		die (esc_html__( 'You do not have permissions to perform this action.', 'paid-memberships-pro' ) );
	}

	global $wpdb, $msg, $msgt, $allowedposttags;

	// Check nonce for saving settings
	if ( ! empty( $_REQUEST['savesettings'] ) && ( empty( $_REQUEST['pmpro_designsettings_nonce'] ) || ! check_admin_referer( 'savesettings', 'pmpro_designsettings_nonce' ) ) ) {
		$msg = -1;
		$msgt = __( 'Are you sure you want to do that? Try again.', 'paid-memberships-pro' );
		unset( $_REQUEST['savesettings'] );
	}	

	// Get/set settings.
	if ( ! empty( $_REQUEST['savesettings'] ) ) {

		// Global styles.
		pmpro_setOption( 'style_variation' );

		// Color settings.
		$pmpro_colors = array(
			'base' => sanitize_text_field( $_REQUEST['pmpro_base_color'] ),
			'contrast' => sanitize_text_field( $_REQUEST['pmpro_contrast_color'] ),
			'accent' => sanitize_text_field( $_REQUEST['pmpro_accent_color'] ),
		);
		update_option( 'pmpro_colors', $pmpro_colors );

		// Assume success.
		$msg = true;
		$msgt = __( 'Your design settings have been updated.', 'paid-memberships-pro' );
	}

	// Design settings.
	$pmpro_style_variation = get_option( 'pmpro_style_variation' );
	$pmpro_style_variation = ! empty( $pmpro_style_variation ) ? $pmpro_style_variation : 'variation_1';

	// Color settings.
	$pmpro_colors = get_option( 'pmpro_colors' );
	$pmpro_colors = ! empty( $pmpro_colors ) ? $pmpro_colors : array(
		'base' => '#ffffff',
		'contrast' => '#222222',
		'accent' => '#0c3d54',
	);

	require_once(dirname(__FILE__) . "/admin_header.php");
?>

	<form action="" method="post" enctype="multipart/form-data">
		<?php wp_nonce_field('savesettings', 'pmpro_designsettings_nonce'); ?>
		<hr class="wp-header-end">
		<h1><?php esc_html_e( 'Design Settings', 'paid-memberships-pro' ); ?></h1>
		<p><?php
			$design_settings_link = '<a title="' . esc_attr__( 'Paid Memberships Pro - Design Settings', 'paid-memberships-pro' ) . '" target="_blank" rel="nofollow noopener" href="https://www.paidmembershipspro.com/documentation/appearance/design-settings/?utm_source=plugin&utm_medium=pmpro-designsettings&utm_campaign=documentation&utm_content=design-settings">' . esc_html__( 'Design Settings', 'paid-memberships-pro' ) . '</a>';
			// translators: %s: Link to Design Settings doc.
			printf( esc_html__('Learn more about %s.', 'paid-memberships-pro' ), $design_settings_link ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?></p>
		<div id="global-styles-settings" class="pmpro_section" data-visibility="shown" data-activated="true">
			<div class="pmpro_section_toggle">
				<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
					<span class="dashicons dashicons-arrow-up-alt2"></span>
					<?php esc_html_e( 'Global Styles', 'paid-memberships-pro' ); ?>
				</button>
			</div>
			<div class="pmpro_section_inside">
				<p><?php esc_html_e( 'Choose a style variation for your frontend membership pages.', 'paid-memberships-pro' ); ?></p>
				<style id="pmpro_global_style_colors">
					:root {
						--pmpro--color--base: <?php echo esc_attr( $pmpro_colors['base'] ); ?>;
						--pmpro--color--contrast: <?php echo esc_attr( $pmpro_colors['contrast'] ); ?>;
						--pmpro--color--accent: <?php echo esc_attr( $pmpro_colors['accent'] ); ?>;
					}
				</style>
				<fieldset>
					<legend class="screen-reader-text"><?php esc_html_e( 'Style Variation', 'paid-memberships-pro' ); ?></legend>
					<div class="pmpro_style_variation_items">
						<div id="pmpro_style_variation_item-variation-1" class="pmpro_style_variation_item">
							<label for="style_variation_1">
								<input name="style_variation" id="style_variation_1" type="radio" value="variation_1" class="tog" <?php checked( 'variation_1', $pmpro_style_variation ); ?>>
								<span class="pmpro_style_variation_item-name"><?php esc_html_e( 'Default (Recommended)', 'paid-memberships-pro' ); ?></span>
								<?php pmpro_style_variation_item_preview_html(); ?>
								<p class="description"><?php esc_html_e( 'A light variation with rounded cards and soft shadows.', 'paid-memberships-pro' ); ?></p>
							</label>
						</div>
						<div id="pmpro_style_variation_item-high-contrast" class="pmpro_style_variation_item">
							<label for="style_high_contrast">
								<input name="style_variation" id="style_high_contrast" type="radio" value="variation_high_contrast" class="tog" <?php checked( 'variation_high_contrast', $pmpro_style_variation ); ?>>
								<span class="pmpro_style_variation_item-name"><?php esc_html_e( 'High Contrast', 'paid-memberships-pro' ); ?></span>
								<?php pmpro_style_variation_item_preview_html(); ?>
								<p class="description"><?php esc_html_e( 'Crisp borders and high contrast colors.', 'paid-memberships-pro' ); ?></p>
							</label>
						</div>
						<div id="pmpro_style_variation_item-minimal" class="pmpro_style_variation_item">
							<label for="style_variation_minimal">
								<input name="style_variation" id="style_variation_minimal" type="radio" value="variation_minimal" class="tog" <?php checked( 'variation_minimal', $pmpro_style_variation ); ?>>
								<span class="pmpro_style_variation_item-name"><?php esc_html_e( 'Minimal', 'paid-memberships-pro' ); ?></span>
								<?php pmpro_style_variation_item_preview_html(); ?>
								<p class="description"><?php esc_html_e( 'Load minimal styles and let your theme handle the rest.', 'paid-memberships-pro' ); ?></p>
							</label>
						</div>
					</div>
				</fieldset>
			</div> <!-- end pmpro_section_inside -->
		</div> <!-- end pmpro_section -->
		<div id="color-settings" class="pmpro_section" data-visibility="shown" data-activated="true">
			<div class="pmpro_section_toggle">
				<button class="pmpro_section-toggle-button" type="button" aria-expanded="true">
					<span class="dashicons dashicons-arrow-up-alt2"></span>
					<?php esc_html_e( 'Color Settings', 'paid-memberships-pro' ); ?>
				</button>
			</div>
			<div class="pmpro_section_inside">
				<table class="form-table">
				<tbody>
					<tr>
						<th scope="row" valign="top">
							<label for="pmpro_base_color"><?php esc_html_e( 'Base Color', 'paid-memberships-pro' ); ?></label>
						</th>
						<td>
							<input name="pmpro_base_color" type="color" id="pmpro_base_color" value="<?php echo esc_attr( $pmpro_colors['base'] ); ?>" class="pmpro_color_picker" />
						</td>
					</tr>
					<tr>
						<th scope="row" valign="top">
							<label for="pmpro_contrast_color"><?php esc_html_e( 'Contrast Color', 'paid-memberships-pro' ); ?></label>
						</th>
						<td>
							<input name="pmpro_contrast_color" type="color" id="pmpro_contrast_color" value="<?php echo esc_attr( $pmpro_colors['contrast'] ); ?>" class="pmpro_color_picker" />
						</td>
					</tr>
					<tr>
						<th scope="row" valign="top">
							<label for="pmpro_accent_color"><?php esc_html_e( 'Accent Color', 'paid-memberships-pro' ); ?></label>
						</th>
						<td>
							<input name="pmpro_accent_color" type="color" id="pmpro_accent_color" value="<?php echo esc_attr( $pmpro_colors['accent'] ); ?>" class="pmpro_color_picker" />
						</td>
					</tr>
				</tbody>
				</table>
			</div> <!-- end pmpro_section_inside -->
		</div> <!-- end pmpro_section -->
		<p class="submit">
			<input name="savesettings" type="submit" class="button button-primary" value="<?php esc_attr_e('Save Settings', 'paid-memberships-pro' ); ?>" />
		</p>
	</form>

<?php
	require_once(dirname(__FILE__) . "/admin_footer.php");

/**
 * Function to return to style variation preview card HTML.
 */
function pmpro_style_variation_item_preview_html() { ?>
	<div class="pmpro_style_variation_item-preview">
		<div class="pmpro_style_variation_item-preview-content">
			<span class="pmpro_style_variation_item-preview-heading"><?php esc_html_e( 'Section Heading', 'paid-memberships-pro' ); ?></span>
			<span class="pmpro_style_variation_item-preview-price">
				<?php esc_html_e( '$10 per month', 'paid-memberships-pro' ); ?>
			</span>
			<span class="pmpro_style_variation_item-preview-button">
				<span class="pmpro_btn"><?php esc_html_e( 'Sign Up', 'paid-memberships-pro' ); ?></span>
			</span>
		</div>
		<div class="pmpro_style_variation_item-preview-actions">
			<span class="pmpro_card_action"><?php esc_html_e( 'Action Link', 'paid-memberships-pro' ); ?></span>
			<span class="pmpro_card_action_separator"><?php echo esc_html( pmpro_actions_nav_separator() ); ?></span>
			<span class="pmpro_card_action"><?php esc_html_e( 'Action Link', 'paid-memberships-pro' ); ?></span>
		</div>
	</div>
	<?php
}

