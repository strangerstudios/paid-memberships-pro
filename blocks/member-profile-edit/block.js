/**
 * Block: PMPro Member Profile Edit
 *
 *
 */

/**
 * Internal block libraries
 */
const { __ } = wp.i18n;
const { registerBlockType } = wp.blocks;

/**
 * Register block
 */
export default registerBlockType("pmpro/member-profile-edit", {
	title: __("Member Profile Edit", "paid-memberships-pro"),
	description: __("Allow member profile editing.", "paid-memberships-pro"),
	category: "pmpro",
	icon: {
		background: "#2997c8",
		foreground: "#ffffff",
		src: "admin-users",
	},
	keywords: [
		__("pmpro", "paid-memberships-pro"),
		__("member", "paid-memberships-pro"),
		__("profile", "paid-memberships-pro"),
	],
	edit: (props) => {
		return (
			<div className="pmpro-block-element">
				<span>{__("Paid Memberships Pro", "paid-memberships-pro")}</span>
				<span>
					{__("Member Profile Edit", "paid-memberships-pro")}
				</span>
			</div>
		);
	},
	save() {
		return null;
	},
});
