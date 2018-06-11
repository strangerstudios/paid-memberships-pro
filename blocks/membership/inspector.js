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
        const { attributes: { levels, uid }, setAttributes } = this.props;

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
              <PanelBody>
                  <TextControl
                      label={ __( 'Unique ID', 'paid-memberships-pro' ) }
                      help={ __( 'If you have multiple membership blocks, this is essential in differentiating them. Choose any unique id.', 'paid-memberships-pro' ) }
                      value={ uid }
                      onChange={ uid => setAttributes( { uid } ) }
                  />
              </PanelBody>
          </InspectorControls>
        );
    }
}
