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
import { PanelBody, CheckboxControl } from '@wordpress/components';

/**
 * Render the Membership Account block in the editor.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {WPElement} Element to render.
 */
export default function Edit({ attributes, setAttributes }) {
	const blockProps = useBlockProps({});

	return (
		<>
			<InspectorControls>
				<PanelBody>
					<CheckboxControl
						label={__('Show "My Memberships" Section', 'paid-memberships-pro')}
						checked={attributes.membership}
						onChange={(membership) => setAttributes({ membership })}
					/>
				</PanelBody>
				<PanelBody>
					<CheckboxControl
						label={__('Show "Profile" Section', 'paid-memberships-pro')}
						checked={attributes.profile}
						onChange={(profile) => setAttributes({ profile })}
					/>
				</PanelBody>
				<PanelBody>
					<CheckboxControl
						label={__('Show "Invoices" Section', 'paid-memberships-pro')}
						checked={attributes.invoices}
						onChange={(invoices) => setAttributes({ invoices })}
					/>
				</PanelBody>
				<PanelBody>
					<CheckboxControl
						label={__('Show "Member Links" Section', 'paid-memberships-pro')}
						checked={attributes.links}
						onChange={(links) => setAttributes({ links })}
					/>
				</PanelBody>
			</InspectorControls>
			<div className="pmpro-block-element" { ...blockProps }>
				<span className="pmpro-block-title">{ __( 'Paid Memberships Pro', 'paid-memberships-pro' ) }</span>
				<span className="pmpro-block-subtitle">{ __( 'Membership Account Page', 'paid-memberships-pro' ) }</span>
			</div>
		</>
	);
}
