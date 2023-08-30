/**
 * WordPress dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';

export default function Save(props) {
    const blockProps = useBlockProps.save();
	const all_levels = [{ value: 0, label: __("Choose a level", 'paid-memberships-pro') }].concat(pmpro.all_level_values_and_labels);
	const getName = (level_id) => { return all_levels.find( (level) => level.value == level_id )?.label; };

	return (
        <div {...blockProps}>
            {getName(props.attributes.selected_level)}
        </div>
    );
}