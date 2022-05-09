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
export default registerBlockType(
	'pmpro/member-profile-edit',
	{
		title: __( 'PMPro Page: Account Profile Edit', 'paid-memberships-pro' ),
		description: __( 'Dynamic form that allows the current logged in member to edit their default user profile information and any custom user profile fields.', 'paid-memberships-pro' ),
		category: 'pmpro-pages',
		icon: {
			background: '#FFFFFF',
			foreground: '#1A688B',
			src: 'admin-users',
		},
		keywords: [
			__( 'custom field', 'paid-memberships-pro' ),
			__( 'fields', 'paid-memberships-pro' ),
			__( 'paid memberships pro', 'paid-memberships-pro' ),
			__( 'pmpro', 'paid-memberships-pro' ),
			__( 'user fields', 'paid-memberships-pro' ),
		],
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
