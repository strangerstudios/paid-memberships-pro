/**
 * Block: PMPro Login Form
 *
 * Add a login form to any page or post.
 *
 */

import metadata from './block.json';

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
     metadata,
	 {
		title: __( 'Log in Form', 'paid-memberships-pro' ),
		description: __( 'Displays a Log In Form for Paid Memberships Pro.', 'paid-memberships-pro' ),
		icon: {
			background: "#2997c8",
			foreground: "#ffffff",
			src: "unlock",
		},
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
