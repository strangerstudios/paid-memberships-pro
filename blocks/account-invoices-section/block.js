/**
 * Block: PMPro Membership Account: Invoices
 *
 * Displays the Membership Account > Invoices page section.
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
     'pmpro/account-invoices-section',
     {
         title: __( 'PMPro Page: Account Invoices', 'paid-memberships-pro' ),
         description: __( 'Dynamic page section that displays a list of the last 5 membership invoices for the active member.', 'paid-memberships-pro' ),
         category: 'pmpro-pages',
         icon: {
            background: '#FFFFFF',
            foreground: '#1A688B',
            src: 'archive',
         },
         keywords: [
             __( 'account', 'paid-memberships-pro' ),
             __( 'member', 'paid-memberships-pro' ),
             __( 'order', 'paid-memberships-pro' ),
             __( 'paid memberships pro', 'paid-memberships-pro' ),
             __( 'pmpro', 'paid-memberships-pro' ),
             __( 'purchases', 'paid-memberships-pro' ),
             __( 'receipt', 'paid-memberships-pro' ),
             __( 'user', 'paid-memberships-pro' ),
         ],
         supports: {
         },
         attributes: {
          title : {
            type: 'string',
            default: __( 'Past Invoices', 'paid-memberships-pro' )
          }
         },
         edit({ attributes, setAttributes }) {
          const updateTitle = ( event ) => {
           setAttributes( { title: event.target.value } );
          };
             return [
                <div className="pmpro-block-element">
                  <span className="pmpro-block-title">{ __( 'Paid Memberships Pro', 'paid-memberships-pro' ) }</span>
                  <span className="pmpro-block-subtitle"> { __( 'Membership Account: Invoices', 'paid-memberships-pro' ) }</span>
                  <input
                   placeholder={ __( 'No title will be shown.', 'paid-memberships-pro' ) }
                   type="text"
                   value={ attributes.title }
                   className="block-editor-plain-text"
                   onChange={ updateTitle }
                  />
                </div>
            ];
         },
         save() {
           return null;
         },
       }
 );
