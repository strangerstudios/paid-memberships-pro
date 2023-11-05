/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';

/**
 * WordPress dependencies
 */
import { CheckboxControl, PanelBody } from '@wordpress/components';
import { InnerBlocks, useBlockProps, InspectorControls } from '@wordpress/block-editor';

/**
 * CSS code for the Membership Excluded block that gets applied to the editor.
 */
import './editor.scss';

/**
 * Render the Membership Excluded block in the editor.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {WPElement} Element to render.
 */
export default function Edit(props) {
	const blockProps = useBlockProps( {} );
	const all_levels = [{ value: 0, label: "Non-Members" }].concat( pmpro.all_level_values_and_labels );
	const { attributes: { levels, uid, show_noaccess }, setAttributes, isSelected } = props;
	if (uid === '') {
		var rand = Math.random() + "";
		setAttributes({ uid: rand });
	}

	function selectAllLevels(selectAll) {
		const allLevelValues = all_levels.map((level) => level.value + '');
		// If selectAll is true, set newLevels to all values. If false, set it to an empty array.
		const newLevels = selectAll ? allLevelValues : [];
		setAttributes({ levels: newLevels });
	}

	// Build an array of checkboxes for each level.
	var checkboxes = all_levels.map( function(level) {
		function setLevelsAttribute( nowChecked ) {
			if ( nowChecked && ! ( levels.some( levelID => levelID == level.value ) ) ) {
				// Add the level.
				const newLevels = levels.slice();
				newLevels.push( level.value + '' );
				setAttributes( { levels:newLevels } );
			} else if ( ! nowChecked && levels.some( levelID => levelID == level.value ) ) {
				// Remove the level.
				const newLevels = levels.filter(( levelID ) => levelID != level.value);
				setAttributes( { levels:newLevels } );
			}
		}
		return [
			<CheckboxControl
				label = { level.label }
				checked = { levels.some( levelID => levelID == level.value ) }
				onChange = { setLevelsAttribute }
			/>
		]
	});

	return [
		isSelected && <InspectorControls>
		<PanelBody>
			<p><strong>{ __( 'Which membership levels should this block be hidden from?', 'paid-memberships-pro' ) }</strong></p>
			<p>
				{ __('Select', 'paid-memberships-pro') } <a href="#" onClick={(event) => { event.preventDefault(); selectAllLevels(true); }}>{ __('All', 'paid-memberships-pro') }</a> | <a href="#" onClick={(event) => { event.preventDefault(); selectAllLevels(false); }}>{ __('None', 'paid-memberships-pro') }</a>
			</p>
			<div class="pmpro-block-inspector-scrollable">
				{checkboxes}
			</div>
		</PanelBody>
	</InspectorControls>,
	<div className="pmpro-block-require-membership-element" { ...blockProps }>
		<span className="pmpro-block-title">{ __( 'Membership Excluded', 'paid-memberships-pro' ) }</span>
		<InnerBlocks
			templateLock={ false }
		/>
	</div>,
	];
}
