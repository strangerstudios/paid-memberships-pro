/**
 * Internal block libraries
 */
const { __ } = wp.i18n;
const { Component } = wp.element;
const {
    PanelBody,
    PanelRow,
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
        const { attributes: { membership, profile, invoices, links }, setAttributes } = this.props;

        return (
          <InspectorControls>
              <PanelBody>
                <CheckboxControl
                    label="Show 'My Memberships' Section"
                    checked={ membership }
                    onChange={ membership => setAttributes( {membership} ) }
                />
              </PanelBody>
              <PanelBody>
                <CheckboxControl
                  label="Show 'Profile' Section"
                  checked={ profile }
                  onChange={ profile => setAttributes( {profile} ) }
                  />
              </PanelBody>
              <PanelBody>
                <CheckboxControl
                  label="Show 'Invoices' Section"
                  checked={ invoices }
                  onChange={ invoices => setAttributes( {invoices} ) }
                  />
              </PanelBody>
              <PanelBody>
                <CheckboxControl
                  label="Show 'Member Links' Section"
                  checked={ links }
                  onChange={ links => setAttributes( {links} ) }
                  />
              </PanelBody>
          </InspectorControls>
        );
    }
}
