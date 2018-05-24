/**
 * Block: PMPro invoice Button
 *
 * Add a styled link to the PMPro invoice page for a
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
     'pmpro/invoice-page',
     {
         title: __( 'Invoice Page', 'pmpro' ),
         description: __( 'This page shows a single membership invoice or a list of all membership invoices for the current user.', 'pmpro' ),
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
                  "Invoice Page Placeholder"
                </div>
            ];
         },
         save() {
           return null;
         },
       }
 );
