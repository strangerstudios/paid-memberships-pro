/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from "@wordpress/i18n";

/**
 * WordPress dependencies
 */
import { AlignmentControl, BlockControls, useBlockProps } from '@wordpress/block-editor';
import { Fragment } from '@wordpress/element';

/**
 * Render the Level Expiration block in the editor.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {WPElement} Element to render.
 */
export default function Edit(props) {
	const getExpirationText = (level) => {
		return pmpro.all_levels_formatted_text[level]
			? pmpro.all_levels_formatted_text[level].formatted_expiration
			: null;
	};

	const { attributes: { textAlign }, setAttributes } = props;

	const TagName = 'div';

	const blockProps = useBlockProps( {
		className: classnames( {
			[ `has-text-align-${ textAlign }` ]: textAlign,
		} ),
	} );

	let expirationElement;

	const levelExpiration = getExpirationText(props.attributes.selected_membership_level);

	if (levelExpiration) {
		// If levelExpiration exists, use it and set it as dangerouslySetInnerHTML
		expirationElement = (
			<TagName
				{...blockProps}
				dangerouslySetInnerHTML={{ __html: levelExpiration }}
			/>
		);
	} else {
		// If levelExpiration doesn't exist, use the placeholder text as children
		expirationElement = (
			<TagName {...blockProps}>
				{ __('Level Expiration', 'paid-memberships-pro') }
			</TagName>
		);
	}

	return [
		<>
			<Fragment>
				<BlockControls>
					<AlignmentControl
						value={ textAlign }
						onChange={ ( nextAlign ) => {
							setAttributes( { textAlign: nextAlign } );
						} }
					/>
				</BlockControls>
			</Fragment>
			{ expirationElement }
		</>,
	];
}
