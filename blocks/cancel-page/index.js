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
     'pmpro/cancel-page',
     {
         title: __( 'PMPro Cancel Page', 'paid-memberships-pro' ),
         description: __( 'This page shows links for a member to cancel their membership or a link to return to the Membership Account page.', 'paid-memberships-pro' ),
         category: 'common',
         icon: 'no',
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
                  "Cancel Page Placeholder"
                </div>
            ];
         },
         save() {
           return null;
         },
       }
 );
