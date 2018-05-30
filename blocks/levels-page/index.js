/**
 * Block: PMPro levels Button
 *
 * Add a styled link to the PMPro levels page for a
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
     'pmpro/levels-page',
     {
         title: __( 'PMPro Levels Page', 'paid-memberships-pro' ),
         description: __( 'This page shows the membership level options available displayed in the order sorted via the Memberships > Membership Levels admin', 'paid-memberships-pro' ),
         category: 'common',
         icon: 'chart-bar',
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
                  "Levels Page Placeholder"
                </div>
            ];
         },
         save() {
           return null;
         },
       }
 );
