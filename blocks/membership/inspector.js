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
        const { attributes: { levels }, setAttributes } = this.props;
        return (
          <InspectorControls>
              <PanelBody>
                  <TextControl
                      label={ __( 'Levels', 'paid-memberships-pro' ) }
                      help={ __( 'Level IDs to show content to separated by comma, 0 is not logged in. Defaults to showing content to all logged in members.', 'paid-memberships-pro' ) }
                      value={ levels }
                      onChange={ levels => setAttributes( { levels } ) }
                  />
              </PanelBody>
          </InspectorControls>
        );
    }
}
