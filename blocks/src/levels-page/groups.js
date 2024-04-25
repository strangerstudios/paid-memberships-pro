/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';


/**
 * WordPress dependencies
 */
import { CheckboxControl } from '@wordpress/components';

export default function groups ( props ) {
	const { attributes: { groups }, setAttributes } = props;

	// Build an array of Groups checkboxes.	
	const checkboxes = pmpro.all_groups.map(( group ) => {
		function setGroupsAttribute( nowChecked ) {
			if ( nowChecked ) {
				// Add the group.
				const newGroups = groups.slice();
				newGroups.push( group );
				setAttributes({ groups: newGroups });
			} else {
				// Remove the group.
				const newGroups = groups.filter( item =>  item.id !== group.id );
				setAttributes({ groups: newGroups });
			}
		}
		return [
			<CheckboxControl
				label={ group.name }	
				checked={ groups.some( item =>  item.id === group.id ) }
				onChange={ setGroupsAttribute }
			/>
		];
	});

	return checkboxes;
}