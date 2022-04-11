/**
 * Block: PMPro Membership Levels
 *
 * Displays the Membership Levels template.
 *
 */

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
     'pmpro/levels-page',
     {
         title: __( 'Membership Levels and Pricing Table', 'paid-memberships-pro' ),
         description: __( 'Dynamic page section that displays a list of membership levels and pricing, linked to membership checkout. To reorder the display, navigate to Memberships > Settings > Levels.', 'paid-memberships-pro' ),
         category: 'pmpro',
         icon: {
            background: '#FFFFFF',
            foreground: '#658B24',
            src: 'list-view',
         },
         keywords: [
             __( 'level', 'paid-memberships-pro' ),
             __( 'paid memberships pro', 'paid-memberships-pro' ),
             __( 'pmpro', 'paid-memberships-pro' ),
             __( 'price', 'paid-memberships-pro' ),
             __( 'pricing table', 'paid-memberships-pro' ),
         ],
         supports: {
         },
         attributes: {
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
