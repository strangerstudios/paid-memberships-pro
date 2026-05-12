import { __ } from '@wordpress/i18n';

import ContentVisibilityControls from './content-visibility-controls';

// Content Visibility Controls will not be added to these blocks in the widget context.
const WIDGET_EXCLUDED_BLOCKS = [ 'core/html', 'core/legacy-widget' ];

// WordPress sets window.pagenow to 'widgets' on the block-based Widgets screen
// and 'customize' in the Customizer, so we can detect both without touching the data stores.
const IS_WIDGETS_CONTEXT = window.pagenow === 'widgets' || window.pagenow === 'customize';

/**
 * Add the visibility attributes to the block settings.
 *
 * @param {object} settings  The block settings.
 * @param {string} name 	The block name.
 */
function addContentVisibilityAttribute(settings, name) {
	if (typeof settings.attributes !== 'undefined') {
		if (name.startsWith('core/')) {

			// Skip unsupported blocks when in widget editors.
			if (IS_WIDGETS_CONTEXT && WIDGET_EXCLUDED_BLOCKS.includes(name)) {
				return settings;
			}

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
		const { isSelected } = props;
		const isExcludedInWidgetContext = IS_WIDGETS_CONTEXT && WIDGET_EXCLUDED_BLOCKS.includes(props.name);
		return (
			<Fragment>
				<BlockEdit {...props} />
				{ isSelected && props.name.startsWith('core/') && !isExcludedInWidgetContext && ContentVisibilityControls(props) }
			</Fragment>
		);

	};
}, 'contentVisibilityComponent');

wp.hooks.addFilter(
	'editor.BlockEdit',
	'pmpro/content-visibility',
	contentVisibilityComponent
);
