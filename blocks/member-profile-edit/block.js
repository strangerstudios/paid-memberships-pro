/**
 * Block: PMPro Member Profile Edit
 *
 *
 */

import blockJSON from './block.json';

/**
 * Internal block libraries
 */
const { __ } = wp.i18n;
const { registerBlockType } = wp.blocks;

/**
 * Register block
 */
export default registerBlockType(
     blockJSON,
	 {
		title: __( 'Member Profile Edit', 'paid-memberships-pro' ),
		description: __( 'Allow member profile editing.', 'paid-memberships-pro' ),
		icon: {
			background: "#2997c8",
			foreground: "#ffffff",
			src: "admin-users",
		},
		edit: (props) => {
			return (
				<div className="pmpro-block-element">
					<span className="pmpro-block-title">{__("Paid Memberships Pro", "paid-memberships-pro")}</span>
					<span className="pmpro-block-subtitle">
						{__("Member Profile Edit", "paid-memberships-pro")}
					</span>
				</div>
			);
		},
		save() {
			return null;
		},
	}
);
