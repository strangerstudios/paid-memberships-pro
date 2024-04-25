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
import { PanelBody } from '@wordpress/components';

import Levels from './levels';
import Groups from './groups';


/**
 * Render the Membership Levels and Pricing Table block in the editor.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {WPElement} Element to render.
 */
export default function Edit(props) {
	const blockProps = useBlockProps( {} );

	return [
		<div className="pmpro-block-element"  { ...blockProps }>
			<span className="pmpro-block-title">{ __( 'Paid Memberships Pro', 'paid-memberships-pro' ) }</span>
			<span className="pmpro-block-subtitle">{ __( 'Membership Levels List', 'paid-memberships-pro' ) }</span>
		</div>,
		<InspectorControls>
			<PanelBody
				title={ __( 'Membership Groups', 'paid-memberships-pro' ) }
				initialOpen={ true }
			>
				{ Groups( props ) }
			</PanelBody>
			
			<PanelBody
				title={ __( 'Membership Levels', 'paid-memberships-pro' ) }
				initialOpen={ true }
			>
			{ Levels( props ) }
			</PanelBody>
		</InspectorControls>
		
	];
}
