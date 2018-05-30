/**
 * Block: PMPro Checkout Button
 *
 * Add a styled link to the PMPro checkout page for a
 * specific level.
 *
 */
 /**
  * Block dependencies
  */
 import './style.scss';
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
         title: __( 'PMPro Account Invoices Section', 'paid-memberships-pro' ),
         description: __( 'Displays a user\'s invoices information', 'paid-memberships-pro' ),
         category: 'common',
         icon: 'archive',
         keywords: [
         ],
         supports: {
         },
         attributes: {
         },
         edit: props => {
             const { className } = props;
             return [
                <div className={ className }>
                  "Account Invoices Section Placeholder"
                </div>
            ];
         },
         save() {
           return null;
         },
       }
 );
