/**
 * Block: PMPro Membership Checkout
 *
 * Displays the Membership Checkout form.
 *
 */
 /**
  * Block dependencies
  */
 import './editor.css';
 import classnames from 'classnames';
 import Inspector from './inspector';
 /**
  * Internal block libraries
  */
 const { __ } = wp.i18n;
 const {
    registerBlockType,
    AlignmentToolbar,
    BlockControls,
    BlockAlignmentToolbar,
} = wp.blocks;
const {
    PanelBody,
    SelectControl,
} = wp.components;

const {
    InspectorControls,
} = wp.editor;

 /**
  * Register block
  */
 export default registerBlockType(
     'pmpro/checkout-page',
     {
         title: __( 'Membership Checkout Form', 'paid-memberships-pro' ),
         description: __( 'Displays the Membership Checkout form.', 'paid-memberships-pro' ),
         category: 'pmpro',
         icon: {
            background: '#2997c8',
            foreground: '#ffffff',
            src: 'list-view',
         },
         keywords: [ __( 'pmpro', 'paid-memberships-pro' ) ],
         supports: {
         },
         attributes: {
             pmpro_default_level: {
                 type: 'string',
                 source: 'meta',
                 meta: 'pmpro_default_level',
             },
         },
         edit: props => {
             const { attributes: { pmpro_default_level }, className, setAttributes, isSelected } = props;
             return [
                isSelected && <Inspector { ...{ setAttributes, ...props} } />,
                <div className={ className }>
                  <span>Paid Memberships Pro</span>
                  <span>Membership Checkout Form</span>
                  <SelectControl
                      label={ __( 'Membership Level', 'paid-memberships-pro' ) }
                      value={ pmpro_default_level }
                      onChange={ pmpro_default_level => setAttributes( { pmpro_default_level } ) }
                      options={ window.pmpro.all_level_values_and_labels }
                  />
                </div>
            ];
         },
         save() {
           return null;
         },
       }
 );
