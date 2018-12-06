/**
 * Internal block libraries
 */
const { __ } = wp.i18n;
const { Component } = wp.element;
const {
    PanelBody,
    PanelRow,
    TextControl,
} = wp.components;
const {
    InspectorControls,
} = wp.editor;

/**
 * Create an Inspector Controls wrapper Component
 */ 
export default class Inspector extends Component {

    constructor() {
        super( ...arguments );
    }

    render() {
        const { attributes: { pmpro_default_level }, setAttributes } = this.props;

        return (
          <InspectorControls>
          <PanelBody>
             <TextControl
                 label={ __( 'Membership Level', 'paid-memberships-pro' ) }
                 help={ __( 'Choose a default level for Membership Checkout.', 'paid-memberships-pro' ) }
                 value={ pmpro_default_level }
                 onChange={ pmpro_default_level => setAttributes( { pmpro_default_level } ) }
             />
          </PanelBody>
          </InspectorControls>
        );
    }
}
