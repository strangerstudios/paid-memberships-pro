/**
 * Block: PMPro confirmation Button
 *
 * Add a styled link to the PMPro confirmation page for a
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
     'pmpro/confirmation-page',
     {
         title: __( 'PMPro Confirmation Page', 'paid-memberships-pro' ),
         description: __( 'This page shows the confirmation message and details that are displayed after membership checkout.', 'paid-memberships-pro' ),
         category: 'common',
         icon: 'yes',
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
                  "Confirmation Page Placeholder"
                </div>
            ];
         },
         save() {
           return null;
         },
       }
 );
