import { __ } from '@wordpress/i18n';

import InspectorControlsFragment from '../membership/inspectorControlsFragment';

/**
 * Add the visibility attributes to the block settings.
 *
 * @param {object} settings  The block settings.
 * @param {string} name 	The block name.
 */
function addVisibilityAttribute(settings, name) {
	if (typeof settings.attributes !== 'undefined') {
		if (name.startsWith('core/')) {
			settings.attributes = Object.assign(settings.attributes, {
				invert_restrictions: {
					type: "string",
					default: "0"
				},
				segment:{
					type: "string",
					default: "all"
				},
				levels: {
					type: "array",
					default:[]
				},
				show_noaccess: {
					type: "string",
					default: "0"
				}
			});
		}
	}
	return settings;
}

wp.hooks.addFilter(
	'blocks.registerBlockType',
	'paid-memberships-pro/core-visibility',
	addVisibilityAttribute
);


/**
 *  Render the Content Visibility block in the inspector controls sidebar.
 *
 * @param {object} props The block props.
 * @return {WPElement} Element to render.
 */
const membershipRequiredComponent = wp.compose.createHigherOrderComponent((BlockEdit) => {
	 return (props) => {

		const { Fragment } = wp.element;
		const {  isSelected } = props;
		 return (	
			<Fragment>
				<BlockEdit {...props} />
				{ isSelected && (props.name.startsWith('core/')) &&	InspectorControlsFragment(props) }
			</Fragment>
		);

	};
}, 'membershipRequiredComponent');

wp.hooks.addFilter(
	'editor.BlockEdit',
	'paid-memberships-pro/core-visibility',
	membershipRequiredComponent
);
