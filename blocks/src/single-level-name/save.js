/**
 * WordPress dependencies
 */
import { __ } from "@wordpress/i18n";

export default function Save(props) {
	const getName = (level) => {
		return pmpro.all_levels_formatted_text[level]
			? pmpro.all_levels_formatted_text[level].name
			: "";
	};
	return getName(props.attributes.selected_membership_level);
}
