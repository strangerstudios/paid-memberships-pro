/**
 * Block: PMPro Membership Invoices
 *
 * Displays the Membership Invoices template.
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
     'pmpro/invoice-page',
     {
         title: __( 'Membership Invoice Page', 'paid-memberships-pro' ),
         description: __( 'Displays the member\'s  Membership Invoices.', 'paid-memberships-pro' ),
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
                   <span>Membership Invoices</span>
                 </div>
            ];
         },
         save() {
           return null;
         },
       }
 );
