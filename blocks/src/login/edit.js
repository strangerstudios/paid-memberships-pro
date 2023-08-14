/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';

/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { Fragment } from '@wordpress/element';

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * Those files can contain any CSS code that gets applied to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './editor.scss';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
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
			  label={__("Display 'Welcome' content when logged in.", "paid-memberships-pro")}
			  checked={display_if_logged_in}
			  onChange={(value) => {
				setAttributes({
				  display_if_logged_in: value,
				});
			  }}
			/>
			<ToggleControl
			  label={__("Display the 'Log In Widget' menu.", "paid-memberships-pro")}
			  help={__("Assign the menu under Appearance > Menus.", "paid-memberships-pro")}
			  checked={show_menu}
			  onChange={(value) => {
				setAttributes({
				  show_menu: value,
				});
			  }}
			/>
			<ToggleControl
			  label={__("Display a 'Log Out' link.", "paid-memberships-pro")}
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
		  <span className="pmpro-block-title">{__("Paid Memberships Pro", "paid-memberships-pro")}</span>
		  <span className="pmpro-block-subtitle">{__("Log in Form", "paid-memberships-pro")}</span>
		</div>
	  </>
	);
}
