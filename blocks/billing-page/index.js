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
     'pmpro/billing-page',
     {
         title: __( 'PMPro Billing Page', 'paid-memberships-pro' ),
         description: __( 'For members with an active subscription, this page shows the member’s billing information and allows them to update the payment method.', 'paid-memberships-pro' ),
         category: 'common',
         icon: 'format-aside',
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
                  "Billing Page Placeholder"
                </div>
            ];
         },
         save() {
           return null;
         },
       }
 );
