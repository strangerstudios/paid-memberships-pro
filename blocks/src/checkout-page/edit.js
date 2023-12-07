/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';

/**
 * WordPress dependencies
 */
import { useSelect } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';

/**
 * Render the Membership Checkout block in the editor.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {WPElement} Element to render.
 */
export default function Edit({ attributes, setAttributes }) {
	const blockProps = useBlockProps({});
	const postType = useSelect(
		( select ) => select( 'core/editor' ).getCurrentPostType(),
		[]
	);
	const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );
	const pmpro_default_level_value = meta['pmpro_default_level'];
	const updateMetaValue = ( value ) => {
		setMeta( { ...meta, pmpro_default_level: value } );
	};

	return (
		<>
		<InspectorControls>
			<PanelBody>
				<SelectControl
					label={ __( 'Membership Level', 'paid-memberships-pro' ) }
					help={ __( 'Choose a default level for Membership Checkout.', 'paid-memberships-pro' ) }
					value={ pmpro_default_level_value }
					onChange={ updateMetaValue }
					options={ [''].concat( window.pmpro.all_level_values_and_labels ) }
				/>
			</PanelBody>
		</InspectorControls>
		<div className="pmpro-block-element" { ...blockProps }>
			<span className="pmpro-block-title">{ __( 'Paid Memberships Pro', 'paid-memberships-pro' ) }</span>
			<span className="pmpro-block-subtitle"> { __( 'Membership Checkout Form', 'paid-memberships-pro' ) }</span>
		</div>
		</>
	);
}
