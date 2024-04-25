/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';


/**
 * WordPress dependencies
 */
import { CheckboxControl} from '@wordpress/components';

//Build a checkbox for each level
export default function levels ( props ) {

	const { attributes: { groups, levels }, setAttributes } = props;


	// Build an array of checkboxes for each level.
	const checkboxes = pmpro.all_level_values_and_labels.map(( level ) => {
		function setLevelsAttribute( nowChecked ) {
			if ( nowChecked ) {
				// Add the level.
				const newLevels = levels.slice();
				newLevels.push( level );
				setAttributes({ levels: newLevels });
			} else {
				// Remove the level.
				const newLevels = levels.filter( item => item.value !== level.value );
				setAttributes({ levels: newLevels });
			}
		}
		return [
			<CheckboxControl
				label={ level.label }
				checked={ levels.some( item  => item.value == level.value ) && groups.some( item => item.id == level.group_id )}
				onChange={ setLevelsAttribute }
				disabled={ ! ( groups.some( item => item.id == level.group_id ) ) }
			/>
		];
	});

	return checkboxes;
 
}