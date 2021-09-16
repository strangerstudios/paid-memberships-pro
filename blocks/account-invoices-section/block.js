/**
 * Block: PMPro Membership Account: Invoices
 *
 * Displays the Membership Account > Invoices page section.
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
         title: __( 'Membership Account: Invoices', 'paid-memberships-pro' ),
         description: __( 'Displays the member\'s invoices.', 'paid-memberships-pro' ),
         icon: {
            background: '#2997c8',
            foreground: '#ffffff',
            src: 'archive',
         },
         edit() {
             return [
                <div className="pmpro-block-element">
                  <span className="pmpro-block-title">{ __( 'Paid Memberships Pro', 'paid-memberships-pro' ) }</span>
                  <span className="pmpro-block-subtitle"> { __( 'Membership Account: Invoices', 'paid-memberships-pro' ) }</span>
                </div>
            ];
         },
         save() {
           return null;
         },
       }
 );
