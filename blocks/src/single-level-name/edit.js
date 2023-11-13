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
import { AlignmentControl, HeadingLevelDropdown, BlockControls, useBlockProps } from '@wordpress/block-editor';
import { Fragment } from '@wordpress/element';

/**
 * Render the Single Level Name Block in the editor.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {WPElement} Element to render.
 */
export default function Edit(props) {
	const getName = (level) => {
		return pmpro.all_levels_formatted_text[level]
			? pmpro.all_levels_formatted_text[level].name
			: null;
	};

	const { attributes: { textAlign, level }, setAttributes } = props;

	const TagName = 'h' + level;

	const blockProps = useBlockProps( {
		className: classnames( {
			[ `has-text-align-${ textAlign }` ]: textAlign,
		} ),
	} );

	let titleElement;

	const levelName = getName(props.attributes.selected_membership_level);

	if (levelName) {
		// If levelName exists, use it and set it as dangerouslySetInnerHTML
		titleElement = (
			<TagName
				{...blockProps}
				dangerouslySetInnerHTML={{ __html: levelName }}
			/>
		);
	} else {
		// If levelName doesn't exist, use the placeholder text as children
		titleElement = (
			<TagName {...blockProps}>
				{ __('Level Name', 'paid-memberships-pro') }
			</TagName>
		);
	}

	return [
		<>
			<Fragment>
				<BlockControls>
					<HeadingLevelDropdown
						value={ level }
						onChange={ ( newLevel ) =>
							setAttributes( { level: newLevel } )
						}
					/>
					<AlignmentControl
						value={ textAlign }
						onChange={ ( nextAlign ) => {
							setAttributes( { textAlign: nextAlign } );
						} }
					/>
				</BlockControls>
			</Fragment>
			{ titleElement }
		</>,
	];
}
