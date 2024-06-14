import { __ } from '@wordpress/i18n';

import ContentVisibilityControls from './content-visibility-controls';

/**
 * Add the visibility attributes to the block settings.
 *
 * @param {object} settings  The block settings.
 * @param {string} name 	The block name.
 */
function addContentVisibilityAttribute(settings, name) {
	if (typeof settings.attributes !== 'undefined') {
		if (name.startsWith('core/')) {
			settings.attributes = Object.assign(settings.attributes, {
				"visibilityBlockEnabled": {
					"type": 'boolean',
					"default": false
				},
				"invert_restrictions": {
					"type": "string",
					"default": "0"	
				},
				"segment":{
					"type": "string",
					"default": "all"
				},
				"levels": {
					"type": "array",
					"default":[]
				},
				"show_noaccess": {
					"type": "string",
					"default": "0"
				}
			});
		}
	}
	return settings;
}

wp.hooks.addFilter(
	'blocks.registerBlockType',
	'pmpro/content-visibility',
	addContentVisibilityAttribute
);

/**
 *  Render the Content Visibility block in the inspector controls sidebar.
 *
 * @param {object} props The block props.
 * @return {WPElement} Element to render.
 */
const contentVisibilityComponent = wp.compose.createHigherOrderComponent((BlockEdit) => {
	 return (props) => {

		const { Fragment } = wp.element;
		const {  isSelected } = props;
		 return (	
			<Fragment>
				<BlockEdit {...props} />
				{ isSelected && (props.name.startsWith('core/')) &&	ContentVisibilityControls(props) }
			</Fragment>
		);

	};
}, 'contentVisibilityComponent');

wp.hooks.addFilter(
	'editor.BlockEdit',
	'pmpro/content-visibility',
	contentVisibilityComponent
);
