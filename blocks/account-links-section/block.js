/**
 * Block: PMPro Membership Account: Member Links
 *
 * Displays the Membership Account > Member Links page section.
 *
 */

import blockJSON from './block.json';

 /**
  * Internal block libraries
  */
 const { __ } = wp.i18n;
 const {
    registerBlockType
} = wp.blocks;

 /**
  * Register block
  */
 export default registerBlockType(
     blockJSON,
     {
         title: __( 'Membership Account: Links', 'paid-memberships-pro' ),
         description: __( 'Displays the member\'s member links. This block is only visible if other Add Ons or custom code have added links.', 'paid-memberships-pro' ),
         icon: {
            background: '#2997c8',
            foreground: '#ffffff',
            src: 'external',
         },
         edit(){
             return [
                 <div className="pmpro-block-element">
                   <span className="pmpro-block-title">{ __( 'Paid Memberships Pro', 'paid-memberships-pro' ) }</span>
                   <span className="pmpro-block-subtitle">{ __( 'Membership Account: Member Links', 'paid-memberships-pro' ) }</span>
                 </div>
            ];
         },
         save() {
           return null;
         },
       }
 );
