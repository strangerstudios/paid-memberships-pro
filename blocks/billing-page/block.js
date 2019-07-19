/**
 * Block: PMPro Membership Billing
 *
 * Displays the Membership Billing page and form.
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
     'pmpro/billing-page',
     {
         title: __( 'Membership Billing Page', 'paid-memberships-pro' ),
         description: __( 'Displays the member\'s billing information and allows them to update the payment method.', 'paid-memberships-pro' ),
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
        },
         edit: props => {
             const { className } = props;
             return [
                 <div className={ className }>
                   <span>Paid Memberships Pro</span>
                   <span>Membership Billing Page</span>
                 </div>
            ];
         },
         save() {
           return null;
         },
       }
 );
