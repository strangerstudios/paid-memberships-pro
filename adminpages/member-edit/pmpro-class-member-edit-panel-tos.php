<?php

class PMPro_Member_Edit_Panel_TOS extends PMPro_Member_Edit_Panel {
	/**
	 * Set up the panel.
	 */
	public function __construct() {
		$this->slug = 'tos';
		$this->title = __( 'Terms of Service', 'paid-memberships-pro' );
	}

	/**
	 * Display the panel contents.
	 */
	protected function display_panel_contents() {
		// Show TOS Consent History if available.
		$consent_log = pmpro_get_consent_log( self::get_user()->ID, true );
		if ( ! empty( $consent_log ) ) { ?>
			<h3><?php esc_html_e("TOS Consent History", 'paid-memberships-pro' ); ?></h3>
			<div id="tos_consent_history">
				<?php
					if ( ! empty( $consent_log ) ) {
						// Build the selectors for the invoices history list based on history count.
						$consent_log_classes = array();
						$consent_log_classes[] = "pmpro_consent_log";
						if ( count( $consent_log ) > 5 ) {
							$consent_log_classes[] = "pmpro_scrollable";
						}
						$consent_log_class = implode( ' ', array_unique( $consent_log_classes ) );
						echo '<ul class="' . esc_attr( $consent_log_class ) . '">';
						foreach( $consent_log as $entry ) {
							echo '<li>' . esc_html( pmpro_consent_to_text( $entry ) ) . '</li>';
						}
						echo '</ul> <!-- end pmpro_consent_log -->';
					} else {
						esc_html_e( 'N/A', 'paid-memberships-pro' );
					}
				?>
			</div>
			<?php
		} else {
			echo '<p>' . esc_html__( 'No TOS consent history found.', 'paid-memberships-pro' ) . '</p>';
		}
	}

	/**
	 * Do not show if TOS is not enabled.
	 *
	 * @return bool
	 */
	public function should_show() {
		if ( empty( get_option( 'pmpro_tospage' ) ) ) {
			return false;
		}

		return parent::should_show();
	}
}