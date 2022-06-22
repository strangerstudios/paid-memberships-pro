/**
 * Block: PMPro Login Form
 *
 * Add a login form to any page or post.
 *
 */

/**
 * Block dependencies
 */
import Inspector from "./inspector";

/**
 * Internal block libraries
 */
const { __ } = wp.i18n;
const { registerBlockType } = wp.blocks;
const { Fragment } = wp.element;
/**
 * Register block
 */
export default registerBlockType(
	'pmpro/login-form',
	{
		title: __( 'Login Form', 'paid-memberships-pro' ),
		description: __( 'Dynamic form that allows users to log in or recover a lost password. Logged in users can see a welcome message with the selected custom menu.', 'paid-memberships-pro' ),
		category: 'pmpro',
		icon: {
			background: '#FFFFFF',
            foreground: '#658B24',
            src: 'unlock',
        },
		keywords: [
			__( 'log in', 'paid-memberships-pro' ),
			__( 'lost password', 'paid-memberships-pro' ),
			__( 'paid memberships pro', 'paid-memberships-pro' ),
			__( 'password reset', 'paid-memberships-pro' ),
			__( 'pmpro', 'paid-memberships-pro' ),
		],
		supports: {},
		edit: (props) => {
			return [
				<Fragment>
					<Inspector {...props} />
					<div className="pmpro-block-element">
						<span className="pmpro-block-title">{__("Paid Memberships Pro", "paid-memberships-pro")}</span>
						<span className="pmpro-block-subtitle">{__("Log in Form", "paid-memberships-pro")}</span>
					</div>
				</Fragment>,
			];
		},
		save() {
			return null;
		},
	}
);
