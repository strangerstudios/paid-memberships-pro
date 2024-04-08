/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';

/**
 * WordPress dependencies
 */
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';

/**
 * Render the Login Form in the editor.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {WPElement} Element to render.
 */
export default function Edit({ attributes, setAttributes }) {
	const blockProps = useBlockProps();
	const {
		display_if_logged_in,
		show_menu,
		show_logout_link,
		location,
	} = attributes;
	
	return (
		<>
		<InspectorControls>
			<PanelBody>
				<ToggleControl
					label={ __( "Display 'Welcome' content when logged in.", "paid-memberships-pro" ) }
					checked={display_if_logged_in}
					onChange={(value) => {
						setAttributes({
							display_if_logged_in: value,
						});
					}}
				/>
				<ToggleControl
					label={ __( "Display the 'Log In Widget' menu in the 'Welcome' content.", "paid-memberships-pro" ) }
					help={ __( "Assign the menu under Appearance > Menus.", "paid-memberships-pro" ) }
					checked={show_menu}
					onChange={(value) => {
						setAttributes({
							show_menu: value,
						});
					}}
				/>
				<ToggleControl
					label={ __( "Display a 'Log Out' link in the 'Welcome' content.", "paid-memberships-pro" ) }
					checked={show_logout_link}
					onChange={(value) => {
						setAttributes({
							show_logout_link: value,
						});
					}}
				/>
			</PanelBody>
		</InspectorControls>
		<div className="pmpro-block-element" {...blockProps}>
			<span className="pmpro-block-title">{ __( "Paid Memberships Pro", "paid-memberships-pro" ) }</span>
			<span className="pmpro-block-subtitle">{ __( "Log in Form", "paid-memberships-pro" ) }</span>
		</div>
		</>
	);
}
