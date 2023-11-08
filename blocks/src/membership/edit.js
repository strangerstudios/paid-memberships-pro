/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';

/**
 * WordPress dependencies
 */
import { CheckboxControl, PanelBody, SelectControl, ToggleControl, IconButton } from '@wordpress/components';
import { InnerBlocks, useBlockProps, InspectorControls } from '@wordpress/block-editor';

/**
 * CSS code for the Membership Excluded block that gets applied to the editor.
 */
import './editor.scss';

/**
 * Render the Membership Required block in the editor.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {WPElement} Element to render.
 */
export default function Edit(props) {
	// Set up the block.
	const blockProps = useBlockProps({});
	const { attributes: { invert_restrictions, segment, levels, show_noaccess }, setAttributes, isSelected } = props;

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

	// Helper function to select/deselect all levels.
	function selectAllLevels(selectAll) {
		const allLevelValues = pmpro.all_level_values_and_labels.map((level) => level.value + '');
		// If selectAll is true, set newLevels to all values. If false, set it to an empty array.
		const newLevels = selectAll ? allLevelValues : [];
		setAttributes({ levels: newLevels });
	}

	// Helper function to handle changes to the segment attribute.
	function handleSegmentChange(newSegment) {
		// Set the segment attribute and clear the levels array.
		setAttributes({ segment: newSegment, levels: [] });
	}

	// Build the visibility component.
	function toggleVisibility() {
		setAttributes({ invert_restrictions: invert_restrictions === '1' ? '0' : '1' });
	}
	var segment_label = 
		<div>
			<IconButton
				icon={invert_restrictions === '1' ? 'hidden' : 'visibility'}
				label={invert_restrictions === '1' ? __('Hide content from these users', 'your-text-domain') : __('Show content to these users', 'your-text-domain')}
				onClick={toggleVisibility}
			/>
			{ invert_restrictions=='0' ? __( 'Show this block to:', 'paid-memberships-pro' ) : __( 'Hide this block from:', 'paid-memberships-pro' ) }
		</div>;

	// Build an array of checkboxes for each level.
	var checkboxes = pmpro.all_level_values_and_labels.map(function (level) {
		function setLevelsAttribute(nowChecked) {
			if (nowChecked && !(levels.some((levelID) => levelID == level.value))) {
				// Add the level.
				const newLevels = levels.slice();
				newLevels.push(level.value + '');
				setAttributes({ levels: newLevels });
			} else if (!nowChecked && levels.some((levelID) => levelID == level.value)) {
				// Remove the level.
				const newLevels = levels.filter((levelID) => levelID != level.value);
				setAttributes({ levels: newLevels });
			}
		}
		return [
			<CheckboxControl
				label={level.label}
				checked={levels.some((levelID) => levelID == level.value)}
				onChange={setLevelsAttribute}
			/>
		];
	});

	return [
		isSelected && (
			<InspectorControls>
				<PanelBody
					title={__( 'Restriction Settings', 'paid-memberships-pro' )}
					initialOpen={true}
				>
					<SelectControl
						value={segment}
						label={ segment_label }
						options={[
							{ label: __( 'All Members', 'paid-memberships-pro' ), value: 'all' },
							{ label: __( 'Specific Membership Levels', 'paid-memberships-pro' ), value: 'specific' },
							{ label: __( 'Logged-In Users', 'paid-memberships-pro' ), value: 'logged_in' }
						]}
						onChange={(segment) => handleSegmentChange(segment) }
					/>
					{ segment=='specific' && <>
						<p><strong>{ __( 'Membership Levels', 'paid-memberships-pro' ) }</strong></p>
						<p>
							{ __( 'Select', 'paid-memberships-pro' ) } <a href="#" onClick={(event) => { event.preventDefault(); selectAllLevels(true); }}>{ __('All', 'paid-memberships-pro') }</a> | <a href="#" onClick={(event) => { event.preventDefault(); selectAllLevels(false); }}>{ __( 'None', 'paid-memberships-pro' ) }</a>
						</p>
						<div class="pmpro-block-inspector-scrollable">
							{checkboxes}
						</div>
					</> }
					{ invert_restrictions=='0' && <>
						<SelectControl
							value={show_noaccess}
							label={ __( 'Show No Access Message?', 'paid-memberships-pro' ) }
							help={ __ ( "Modify the 'no access' message on the Memberships > Advanced Settings page.", "paid-memberships-pro" ) }
							options={[
								{ label: __( 'No - Hide this block if the user does not have access', 'paid-memberships-pro' ), value: '0' },
								{ label: __( "Yes - Show the 'no access' message if the user does not have access", 'paid-memberships-pro' ), value: '1' },
							]}
							onChange={(show_noaccess) => setAttributes({ show_noaccess })}
						/>
					</> }
				</PanelBody>
			</InspectorControls>
		),
		<div className="pmpro-block-require-membership-element" {...blockProps}>
			<span className="pmpro-block-title">{ __( 'Membership Required', 'paid-memberships-pro' ) }</span>
			<InnerBlocks templateLock={false} />
		</div>,
	];
}
