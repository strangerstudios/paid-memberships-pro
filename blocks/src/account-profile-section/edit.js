/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';

/**
 * WordPress dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Render the Membership Account: Profile block in the editor.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {WPElement} Element to render.
 */
export default function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps( {} );
	const updateTitle = ( event ) => {
		setAttributes( { title: event.target.value } );
	};

	return [
		<div className="pmpro-block-element" { ...blockProps }>
			<span className="pmpro-block-title">{ __( 'Paid Memberships Pro', 'paid-memberships-pro' ) }</span>
			<span className="pmpro-block-subtitle"> { __( 'Membership Account: Profile', 'paid-memberships-pro' ) }</span>
			<input
				placeholder={ __( 'No title will be shown.', 'paid-memberships-pro' ) }
				type="text"
				value={ attributes.title }
				className="block-editor-plain-text"
				onChange={ updateTitle }
			/>
		</div>
	];
}
