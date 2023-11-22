import { __ } from '@wordpress/i18n';

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
						<PanelBody className='pmpro-required-memberships-wrapper' title={__('Require Memberships', 'paid-memberships-pro')}>
							<div className='pmpro-required-selectors'>
								<label>{__('Select: ', 'paid-memberships-pro')}</label>
								<button className='pmpro-selector-all button-link' onClick={() => toggleAllLevels(true, props, levels)}>{__('All')}</button>
								<span> | </span>
								<button className='pmpro-selector-none button-link' onClick={() => toggleAllLevels(false, props, levels)}>{__('None')}</button>
							</div>
							<div className={['pmpro-required-memberships-level-checkbox-wrapper',  levels.length > 5 ? 'pmpro-block-inspector-scrollable' : ''].join(' ')}>
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
							</div>
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

const toggleAllLevels = ( toggle, props, levels ) => {
	const { setAttributes } = props;
	toggle ? setAttributes({restrictedLevels: levels.map((level) => level.value)}) : setAttributes({restrictedLevels: []});
	document.querySelectorAll('pmpro-required-memberships-wrapper input[type="checkbox"]').forEach((el) => {
		el.checked = toggle;
	});
}
