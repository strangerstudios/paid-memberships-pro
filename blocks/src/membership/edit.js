/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';

/**
 * WordPress dependencies
 */
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';

/**
 * CSS code for the Membership Excluded block that gets applied to the editor.
 */
import './editor.scss';

/**
 * Internal dependencies
 */
import ContentVisibilityControls from '../component-content-visibility/content-visibility-controls';

/**
 * Render the Content Visibility block in the editor.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {WPElement} Element to render.
 */
export default function Edit(props) {
	// Set up the block.
	const blockProps = useBlockProps({});
	const { attributes: { invert_restrictions, segment, levels, visibilityBlockEnabled }, setAttributes, isSelected } = props;

	// Handle migrations from PMPro < 3.0.
	// If levels is not empty and segment is 'all', we  need to migrate.
	if (levels.length > 0 && segment == 'all') {
		// If '0' is in levels, then restrictions should be inverted.
		if (levels.includes('0')) {
			// If '0' was the only element, then the segment should be 'all'.
			if (levels.length == 1) {
				setAttributes({ invert_restrictions: '1', segment: 'all', levels: [] });
			} else {
				// Otherwise, the segment should be 'specific' and we need to change the levels array to
				// all level IDs that were not previously selected.
				const newLevels = pmpro.all_level_values_and_labels
					.map((level) => level.value + '')
					.filter((levelID) => !levels.includes(levelID));
				setAttributes({ invert_restrictions: '1', segment: 'specific', levels: newLevels });
			}
		} else {
			// If '0' is not in levels, then we do not need to invert subscriptions and just need to change the segment to 'specific'.
			setAttributes({ invert_restrictions: '0', segment: 'specific' });
		}
	}

	// Always set visibilityBlockEnabled to true.
	if ( visibilityBlockEnabled != true ) {
		setAttributes({ visibilityBlockEnabled: true });
	}

	return [
		isSelected && ContentVisibilityControls(props),
		<div className="pmpro-block-require-membership-element" {...blockProps}>
			<InnerBlocks templateLock={false} />
			<span className="pmpro-block-note">
				<span class="dashicon dashicons dashicons-lock"></span>
				{ __( 'This block has content visibility settings.', 'paid-memberships-pro' ) }
			</span>
		</div>,
	];
}
