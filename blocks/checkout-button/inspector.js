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
        const { attributes: { text, level, css_class }, setAttributes } = this.props;

        return (
          <InspectorControls>
              <PanelBody>
                 <TextControl
                     label={ __( 'Button Text', 'pmpro' ) }
                     help={ __( 'Text for checkout button', 'pmpro' ) }
                     value={ text }
                     onChange={ text => setAttributes( { text } ) }
                 />
              </PanelBody>
              <PanelBody>
                 <TextControl
                     label={ __( 'Level ID', 'pmpro' ) }
                     help={ __( 'Level id to check out', 'pmpro' ) }
                     value={ level }
                     onChange={ level => setAttributes( { level } ) }
                 />
              </PanelBody>
              <PanelBody>
                 <TextControl
                     label={ __( 'CSS Class', 'pmpro' ) }
                     help={ __( 'Additional Styling for Button', 'pmpro' ) }
                     value={ css_class }
                     onChange={ css_class => setAttributes( { css_class } ) }
                 />
              </PanelBody>
          </InspectorControls>
        );
    }
}
