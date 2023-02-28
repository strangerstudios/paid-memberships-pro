/**
 * Block: PMPro Membership Billing
 *
 * Displays the Membership Billing page and form.
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
     'pmpro/billing-page',
     {
         title: __( 'PMPro Page: Billing', 'paid-memberships-pro' ),
         description: __( 'Dynamic page section to display the member\'s billing information. Members can update their subscription payment method from this form.', 'paid-memberships-pro' ),
         category: 'pmpro-pages',
         icon: {
            background: '#FFFFFF',
            foreground: '#1A688B',
            src: 'list-view',
        },
        keywords: [
            __( 'credit card', 'paid-memberships-pro' ),
            __( 'member', 'paid-memberships-pro' ),
            __( 'paid memberships pro', 'paid-memberships-pro' ),
            __( 'payment method', 'paid-memberships-pro' ),
            __( 'pmpro', 'paid-memberships-pro' ),
        ],
        supports: {
        },
        attributes: {
        },
         edit() {
             return [
                 <div className="pmpro-block-element">
                   <span className="pmpro-block-title">{ __( 'Paid Memberships Pro', 'paid-memberships-pro' ) }</span>
                   <span className="pmpro-block-subtitle">{ __( 'Membership Billing Page', 'paid-memberships-pro' ) }</span>
                 </div>
            ];
         },
         save() {
           return null;
         },
       }
 );
