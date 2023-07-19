/**
 * Internal block libraries.
 */
const { __ } = wp.i18n;
const { Component } = wp.element;
const {
    SelectControl,
    PanelBody,
} = wp.components;
const {
    InspectorControls,
} = wp.blockEditor;

const all_levels = [{ value: 0, label: __("Choose a level", 'paid-memberships-pro') }].concat(pmpro.all_level_values_and_labels);

/**
 * Create an Inspector Controls wrapper Component to add a "Select a level" dropdown.
 */
export default class Inspector extends Component {
    
        constructor() {
            super(...arguments);
        }
    
        render() {
            const { attributes: { selected_level }, setAttributes } = this.props;
    
            return (
                <InspectorControls>
                    <PanelBody>
                        <SelectControl
                            label={__("Select a level", 'paid-memberships-pro')}
                            value={selected_level}
                            options={all_levels}
                            onChange={selected_level => setAttributes({ selected_level })}
                        />
                    </PanelBody>
                </InspectorControls>
            );
        }
    }