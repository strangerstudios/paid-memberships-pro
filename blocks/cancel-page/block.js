/**
 * Block: PMPro Membership Cancel
 *
 * Displays the Membership Cancel page.
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
         title: __( 'Membership Cancel Page', 'paid-memberships-pro' ),
         description: __( 'Generates the Membership Cancel page.', 'paid-memberships-pro' ),
         category: 'pmpro',
         icon: {
            background: '#2997c8',
            foreground: '#ffffff',
            src: 'no',
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
                   <span>Membership Cancel Page</span>
                 </div>
            ];
         },
         save() {
           return null;
         },
       }
 );
