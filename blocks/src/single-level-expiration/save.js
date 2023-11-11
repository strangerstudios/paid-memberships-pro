/**
 * WordPress dependencies
 */
import { __ } from "@wordpress/i18n";

export default function Save(props) {
	const getExpirationText = (level) => {
		return pmpro.all_levels_formatted_text[level]
			? pmpro.all_levels_formatted_text[level].formatted_expiration
			: "";
	};
	return getExpirationText(props.attributes.selected_membership_level);
}
