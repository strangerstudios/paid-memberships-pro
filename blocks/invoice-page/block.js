/**
 * Block: PMPro Membership Invoices
 *
 * Displays the Membership Invoices template.
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
     'pmpro/invoice-page',
     {
         title: __( 'PMPro Page: Invoice', 'paid-memberships-pro' ),
         description: __( 'Dynamic page section that displays a list of all invoices (purchase history) for the active member. Each invoice can be selected and viewed in full detail.', 'paid-memberships-pro' ),
         category: 'pmpro-pages',
         icon: {
            background: '#FFFFFF',
            foreground: '#1A688B',
            src: 'archive',
         },
         keywords: [
             __( 'history', 'paid-memberships-pro' ),
             __( 'order', 'paid-memberships-pro' ),
             __( 'paid memberships pro', 'paid-memberships-pro' ),
             __( 'pmpro', 'paid-memberships-pro' ),
             __( 'purchases', 'paid-memberships-pro' ),
             __( 'receipt', 'paid-memberships-pro' ),
         ],
         supports: {
         },
         attributes: {
         },
         edit(){
             return [
                 <div className="pmpro-block-element">
                   <span className="pmpro-block-title">{ __( 'Paid Memberships Pro', 'paid-memberships-pro' ) }</span>
                   <span className="pmpro-block-subtitle">{ __( 'Membership Invoices', 'paid-memberships-pro' ) }</span>
                 </div>
            ];
         },
         save() {
           return null;
         },
       }
 );
