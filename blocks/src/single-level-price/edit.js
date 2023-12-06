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
import { Fragment, RawHTML } from '@wordpress/element';

/**
 * Render the Level Price block in the editor.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {WPElement} Element to render.
 */
export default function Edit(props) {
	const getFormattedPrice = (level) => {
		return pmpro.all_levels_formatted_text[level]
			? pmpro.all_levels_formatted_text[level].formatted_price
			: null;
	};

	const { attributes: { textAlign }, setAttributes } = props;

	const TagName = 'div';

	const blockProps = useBlockProps( {
		className: classnames( {
			[ `has-text-align-${ textAlign }` ]: textAlign,
		} ),
	} );

	let priceElement;

	const levelPrice = getFormattedPrice(props.attributes.selected_membership_level);

	if (levelPrice) {
		// If levelPrice exists, use it and set it as dangerouslySetInnerHTML
		priceElement = (
			<TagName {...blockProps}>
				<RawHTML>
					{ levelPrice }
				</RawHTML>
			</TagName>
		);
	} else {
		// If levelPrice doesn't exist, use the placeholder text as children
		priceElement = (
			<TagName {...blockProps}>
				{ __('Level Price', 'paid-memberships-pro') }
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
			{ priceElement }
		</>,
	];
}
