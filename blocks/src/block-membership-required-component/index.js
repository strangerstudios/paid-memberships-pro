import { __ } from '@wordpress/i18n';

/**
 * Add the visibility attributes to the block settings.
 *
 * @param {object} settings  The block settings.
 * @param {string} name 	The block name.
 */
function addVisibilityAttribute(settings, name) {
	if (typeof settings.attributes !== 'undefined') {
		if (name == 'core/paragraph') {
			settings.attributes = Object.assign(settings.attributes, {
				hideOnMobile: {
					type: 'boolean',
				},
				restrictedLevels: {
					type: 'array',
					default: [],
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
 *  Add the visibility controls to the block inspector.
 * 
 * 
 */
const membershipRequiredComponent = wp.compose.createHigherOrderComponent((BlockEdit) => {
	return (props) => {
		const { Fragment } = wp.element;
		const { ToggleControl } = wp.components;
		const { CheckboxControl } = wp.components;
		const { InspectorControls } = wp.blockEditor;
		const { attributes, setAttributes, isSelected } = props;
		const { PanelBody } = wp.components
 		const levels = pmpro.all_level_values_and_labels;

		return (
			<Fragment>
				<BlockEdit {...props} />
				{isSelected && (props.name.startsWith('core/')) &&
					<InspectorControls>
						<PanelBody title={__('Require Memberships', '')}>
						{levels.map((level) => {
 								return (
							<CheckboxControl
								label={level.label}
								checked={attributes.restrictedLevels.includes(level.value)}
								onChange={() => {
									let newValue = [...attributes.restrictedLevels];
									if (newValue.includes(level.value)) {
										newValue = newValue.filter((item) => item !== level.value);
									} else {
										newValue.push(level.value);
									}
									setAttributes({restrictedLevels: newValue});
								}}
							/>
							);
							})}
						</PanelBody>
					</InspectorControls>
				}
			</Fragment>
		);
	};
}, 'membershipRequiredComponent');


wp.hooks.addFilter(
	'editor.BlockEdit',
	'paid-memberships-pro/core-visibility',
	membershipRequiredComponent
);

