/**
 * Block: PMPro Membership Account: Memberships
 *
 * Displays the Membership Account > My Memberships page section.
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
         title: __( 'Membership Account: Memberships', 'paid-memberships-pro' ),
         description: __( 'Displays the member\'s membership information.', 'paid-memberships-pro' ),
         icon: {
            background: '#2997c8',
            foreground: '#ffffff',
            src: 'groups',
         },
         edit() {
             return [
                 <div className="pmpro-block-element">
                   <span className="pmpro-block-title">{ __( 'Paid Memberships Pro', 'paid-memberships-pro' ) }</span>
                   <span className="pmpro-block-subtitle">{ __( 'Membership Account: My Memberships', 'paid-memberships-pro' ) }</span>
                 </div>
            ];
         },
         save() {
           return null;
         },
       }
 );
