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
 * Render the Membership Invoice block in the editor.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {WPElement} Element to render.
 */
export default function Edit() {
	const blockProps = useBlockProps( {} );

	return [
		<div className="pmpro-block-element" { ...blockProps }>
			<span className="pmpro-block-title">{ __( 'Paid Memberships Pro', 'paid-memberships-pro' ) }</span>
			<span className="pmpro-block-subtitle">{ __( 'Membership Invoices', 'paid-memberships-pro' ) }</span>
		</div>
	];
}
