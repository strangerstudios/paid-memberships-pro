/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';

/**
 * WordPress dependencies
 */
import { CheckboxControl, PanelBody, SelectControl, Button, __experimentalHStack as HStack } from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';

export default function inspectorControlsFragment (props) {
	const { attributes: { invert_restrictions, segment, levels, show_noaccess }, setAttributes } = props;
	
	// Helper function to handle changes to the segment attribute.
	const  handleSegmentChange = (newSegment) => {
		// Set the segment attribute and clear the levels array.
		setAttributes({ segment: newSegment, levels: [] });
	}

		// Build an array of checkboxes for each level.
	const checkboxes = pmpro.all_level_values_and_labels.map((level) => {
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

	return (
		<InspectorControls>
			<PanelBody
				title={__( 'Content Visibility', 'paid-memberships-pro' ) }
				initialOpen={true}
			>
				<HStack>
					{/* Button to toggle visibility to "show" mode */}
					<Button
						className="pmpro-block-require-membership-element__set-show-button"
						icon="visibility"
						variant={invert_restrictions === '0' ? 'primary' : 'secondary'}
						style={ { flexGrow: '1', justifyContent: 'center' } }
						onClick={() => setAttributes({ invert_restrictions: '0' })}
					>
						{__('Show', 'your-text-domain')}
					</Button>
					{/* Button to toggle visibility to "hide" mode */}
					<Button
						className="pmpro-block-require-membership-element__set-hide-button"
						icon="hidden"
						variant={invert_restrictions === '1' ? 'primary' : 'secondary'}
						style={ { flexGrow: '1', justifyContent: 'center' } }
						onClick={() => setAttributes({ invert_restrictions: '1' })}
					>
						{__('Hide', 'your-text-domain')}
					</Button>
				</HStack>
				<br />
				<SelectControl
					value={segment}
					label={ invert_restrictions === '1' ? __('Hide content from:', 'paid-memberships-pro') : __('Show content to:', 'paid-memberships-pro') }
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
)}