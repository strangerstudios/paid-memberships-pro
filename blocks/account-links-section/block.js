/**
 * Block: PMPro Membership Account: Member Links
 *
 * Displays the Membership Account > Member Links page section.
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
     'pmpro/account-links-section',
     {
         title: __( 'Membership Account: Links', 'paid-memberships-pro' ),
         description: __( 'Displays the member\'s member links. This block is only visible if other Add Ons or custom code have added links.', 'paid-memberships-pro' ),
         category: 'pmpro',
         icon: {
            background: '#2997c8',
            foreground: '#ffffff',
            src: 'external',
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
                   <span>Membership Account: Member Links</span>
                 </div>
            ];
         },
         save() {
           return null;
         },
       }
 );
