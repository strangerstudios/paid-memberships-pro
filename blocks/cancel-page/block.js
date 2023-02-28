/**
 * Block: PMPro Membership Cancel
 *
 * Displays the Membership Cancel page.
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
     'pmpro/cancel-page',
     {
         title: __( 'PMPro Page: Cancel', 'paid-memberships-pro' ),
         description: __( 'Dynamic page section where members can cancel their membership and active subscription if applicable.', 'paid-memberships-pro' ),
         category: 'pmpro-pages',
         icon: {
            background: '#FFFFFF',
            foreground: '#1A688B',
            src: 'no',
         },
         keywords: [
             __( 'member', 'paid-memberships-pro' ),
             __( 'paid memberships pro', 'paid-memberships-pro' ),
             __( 'payment method', 'paid-memberships-pro' ),
             __( 'pmpro', 'paid-memberships-pro' ),
             __( 'terminate', 'paid-memberships-pro' ),
         ],
         supports: {
         },
         attributes: {
         },
         edit(){
             return [
                 <div className="pmpro-block-element">
                   <span className="pmpro-block-title">{ __( 'Paid Memberships Pro', 'paid-memberships-pro' ) }</span>
                   <span className="pmpro-block-subtitle">{ __( 'Membership Cancel Page', 'paid-memberships-pro' ) }</span>
                 </div>
            ];
         },
         save() {
           return null;
         },
       }
 );
