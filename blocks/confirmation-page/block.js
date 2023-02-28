/**
 * Block: PMPro Membership Confirmation
 *
 * Displays the Membership Confirmation template.
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
     'pmpro/confirmation-page',
     {
         title: __( 'PMPro Page: Confirmation', 'paid-memberships-pro' ),
         description: __( 'Dynamic page section that displays a confirmation message and purchase information for the active member immediately after membership registration and checkout.', 'paid-memberships-pro' ),
         category: 'pmpro-pages',
         icon: {
            background: '#FFFFFF',
            foreground: '#1A688B',
            src: 'yes',
         },
         keywords: [
             __( 'member', 'paid-memberships-pro' ),
             __( 'buy', 'paid-memberships-pro' ),
             __( 'paid memberships pro', 'paid-memberships-pro' ),
             __( 'pmpro', 'paid-memberships-pro' ),
             __( 'purchase', 'paid-memberships-pro' ),
             __( 'receipt', 'paid-memberships-pro' ),
             __( 'success', 'paid-memberships-pro' ),
         ],
         supports: {
         },
         attributes: {
         },
         edit(){
             return [
                <div className="pmpro-block-element">
                   <span className="pmpro-block-title">{ __( 'Paid Memberships Pro', 'paid-memberships-pro' ) }</span>
                   <span className="pmpro-block-subtitle">{ __( 'Membership Confirmation Page', 'paid-memberships-pro' ) }</span>
                </div>
            ];
         },
         save() {
           return null;
         },
       }
 );
