/**
 * Internal block libraries
 */
const { __ } = wp.i18n;
const { Component } = wp.element;
const {
    PanelBody,
    PanelRow,
    TextControl,
    CheckboxControl,
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
        const { attributes: { levels, hide }, setAttributes } = this.props;

        return (
          <InspectorControls>
              <PanelBody>
                  <TextControl
                      label={ __( 'Levels', 'paid-memberships-pro' ) }
                      help={ __( 'Levels to show/hide separated by comma, 0 is not logged in', 'paid-memberships-pro' ) }
                      value={ levels }
                      onChange={ levels => setAttributes( { levels } ) }
                  />
              </PanelBody>
              <PanelBody>
                  <CheckboxControl
                      label="Hide from levels (as opposed to show to)"
                      checked={ hide }
                      onChange={ hide => setAttributes( {hide} ) }
                  />
              </PanelBody>
          </InspectorControls>
        );
    }
}
