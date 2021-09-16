/**
 * Block: PMPro Membership Levels
 *
 * Displays the Membership Levels template.
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
         title: __( 'Membership Levels List', 'paid-memberships-pro' ),
         description: __( 'Displays a list of Membership Levels. To change the order, go to Memberships > Settings > Levels.', 'paid-memberships-pro' ),
         icon: {
            background: '#2997c8',
            foreground: '#ffffff',
            src: 'list-view',
         },
         edit() {
             return [
                 <div className="pmpro-block-element">
                   <span className="pmpro-block-title">{ __( 'Paid Memberships Pro', 'paid-memberships-pro' ) }</span>
                   <span className="pmpro-block-subtitle">{ __( 'Membership Levels List', 'paid-memberships-pro' ) }</span>
                 </div>
            ];
         },
         save() {
           return null;
         },
       }
 );
