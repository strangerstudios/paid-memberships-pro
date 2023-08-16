/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';

/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
import { CheckboxControl, PanelBody, SelectControl } from '@wordpress/components';
import { InnerBlocks, useBlockProps, InspectorControls } from '@wordpress/block-editor';


/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * Those files can contain any CSS code that gets applied to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './editor.scss';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
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
			<p><strong>{ __( 'Which membership levels can view this block?', 'paid-memberships-pro' ) }</strong></p>
			<div class="pmpro-block-inspector-scrollable">
				{checkboxes}
			</div>
			<hr />
			<p><strong>{ __( 'What should users without access see?', 'paid-memberships-pro' ) }</strong></p>
			<SelectControl
				value={ show_noaccess }
				help={__( "Modify the 'no access' message on the Memberships > Advanced Settings page.", "paid-memberships-pro" ) }
				options={ [
					{ label: __( "Show nothing", 'paid-memberships-pro' ), value: '0' },
					{ label: __( "Show the 'no access' message", 'paid-memberships-pro' ), value: '1' },
				] }
				onChange={ show_noaccess => setAttributes( { show_noaccess } ) }
			/>
		</PanelBody>
	</InspectorControls>,
	isSelected && <div className="pmpro-block-require-membership-element" { ...blockProps }>
		<span className="pmpro-block-title">{ __( 'Membership Required', 'paid-memberships-pro' ) }</span>
		<div class="pmpro-block-inspector-scrollable">
		<PanelBody>                      
			{checkboxes}
		</PanelBody>
		</div>
		<InnerBlocks
			templateLock={ false }
		/>
	</div>,
	! isSelected && <div className="pmpro-block-require-membership-element" { ...blockProps }>
		<span className="pmpro-block-title">{ __( 'Membership Required', 'paid-memberships-pro' ) }</span>
		<InnerBlocks
			templateLock={ false }
		/>
	</div>,
	];
}
