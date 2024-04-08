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
 * Render the Level Description block in the editor.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {WPElement} Element to render.
 */
export default function Edit(props) {
	const getDescriptionText = (level) => {
		return pmpro.all_levels_formatted_text[level]
			? pmpro.all_levels_formatted_text[level].description
			: null;
	};

	const { attributes: { textAlign }, setAttributes } = props;

	const TagName = 'div';

	const blockProps = useBlockProps( {
		className: classnames( {
			[ `has-text-align-${ textAlign }` ]: textAlign,
		} ),
	} );

	let descriptionElement;

	const levelDescription = getDescriptionText(props.attributes.selected_membership_level);

	if (levelDescription) {
		// If levelDescription exists, use it and set it as dangerouslySetInnerHTML
		descriptionElement = (
			<TagName {...blockProps}>
				<RawHTML>
					{ levelDescription }
				</RawHTML>
			</TagName>
		);
	} else {
		// If levelDescription doesn't exist, use the placeholder text as children
		descriptionElement = (
			<TagName {...blockProps}>
				{ __('Level Description', 'paid-memberships-pro') }
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
			{ descriptionElement }
		</>,
	];
}
