/**
 * Block: PMPro Membership Confirmation
 *
 * Displays the Membership Confirmation template.
 *
 */
 /**
  * Block dependencies
  */
 import './editor.css';
 import classnames from 'classnames';
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
    PanelRow,
    TextControl,
} = wp.components;

const {
    RichText,
    InspectorControls,
} = wp.editor;

 /**
  * Register block
  */
 export default registerBlockType(
     'pmpro/confirmation-page',
     {
         title: __( 'Membership Confirmation Page', 'paid-memberships-pro' ),
         description: __( 'Displays the member\'s Membership Confirmation after Membership Checkout.', 'paid-memberships-pro' ),
         category: 'pmpro',
         icon: {
            background: '#2997c8',
            foreground: '#ffffff',
            src: 'yes',
         },
         keywords: [ __( 'pmpro', 'paid-memberships-pro' ) ],
         supports: {
         },
         attributes: {
         },
         edit: props => {
             const { className } = props;
             return [
                <div className={ className }>
                   <span>Paid Memberships Pro</span>
                   <span>Membership Confirmation Page</span>
                </div>
            ];
         },
         save() {
           return null;
         },
       }
 );
