/**
 * Block: PMPro Membership Account: Member Links
 *
 * Displays the Membership Account > Member Links page section.
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
     'pmpro/account-links-section',
     {
         title: __( 'PMPro Page: Account Links', 'paid-memberships-pro' ),
         description: __( 'Dynamic page section that displays custom links available for the active member only. This block is only visible if other Add Ons or custom code have added links.', 'paid-memberships-pro' ),
         category: 'pmpro-pages',
         icon: {
            background: '#FFFFFF',
            foreground: '#1A688B',
            src: 'external',
         },
         keywords: [
             __( 'access', 'paid-memberships-pro' ),
             __( 'account', 'paid-memberships-pro' ),
             __( 'link', 'paid-memberships-pro' ),
             __( 'member', 'paid-memberships-pro' ),
             __( 'paid memberships pro', 'paid-memberships-pro' ),
             __( 'pmpro', 'paid-memberships-pro' ),
             __( 'quick link', 'paid-memberships-pro' ),
             __( 'user', 'paid-memberships-pro' ),
         ],
         supports: {
         },
         attributes: {
          title : {
            type: 'string',
            default: __( 'Member Links', 'paid-memberships-pro' ),
          }
         },
         edit({ attributes, setAttributes }) {
          const updateTitle = ( event ) => {
           setAttributes( { title: event.target.value } );
          };
             return [
                <div className="pmpro-block-element">
                  <span className="pmpro-block-title">{ __( 'Paid Memberships Pro', 'paid-memberships-pro' ) }</span>
                  <span className="pmpro-block-subtitle">{ __( 'Membership Account: Member Links', 'paid-memberships-pro' ) }</span>
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
