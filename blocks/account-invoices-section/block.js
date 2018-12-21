/**
 * Block: PMPro Membership Account: Invoices
 *
 * Displays the Membership Account > Invoices page section.
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
     'pmpro/account-invoices-section',
     {
         title: __( 'Membership Account: Invoices', 'paid-memberships-pro' ),
         description: __( 'Displays the member\'s invoices.', 'paid-memberships-pro' ),
         category: 'pmpro',
         icon: {
            background: '#2997c8',
            foreground: '#ffffff',
            src: 'archive',
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
                  <span>Membership Account: Invoices</span>
                </div>
            ];
         },
         save() {
           return null;
         },
       }
 );
