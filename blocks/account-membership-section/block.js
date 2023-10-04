/**
 * Block: PMPro Membership Account: Memberships
 *
 * Displays the Membership Account > My Memberships page section.
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
     'pmpro/account-membership-section',
     {
         title: __( 'PMPro Page: Account Memberships', 'paid-memberships-pro' ),
         description: __( 'Dynamic page section to display the member\'s active membership information with links to view all membership options, update billing information, and change or cancel membership.', 'paid-memberships-pro' ),
         category: 'pmpro-pages',
         icon: {
            background: '#FFFFFF',
            foreground: '#1A688B',
            src: 'groups',
         },
         keywords: [
             __( 'active', 'paid-memberships-pro' ),
             __( 'member', 'paid-memberships-pro' ),
             __( 'paid memberships pro', 'paid-memberships-pro' ),
             __( 'pmpro', 'paid-memberships-pro' ),
             __( 'purchases', 'paid-memberships-pro' ),
             __( 'user', 'paid-memberships-pro' ),
         ],
         supports: {
         },
         attributes: {
          title : {
            type: 'string',
            default: __( 'My Memberships', 'paid-memberships-pro' ),
          }
         },
         edit({ attributes, setAttributes }) {
          const updateTitle = ( event ) => {
           setAttributes( { title: event.target.value } );
          };
             return [
                 <div className="pmpro-block-element">
                   <span className="pmpro-block-title">{ __( 'Paid Memberships Pro', 'paid-memberships-pro' ) }</span>
                   <span className="pmpro-block-subtitle">{ __( 'Membership Account: My Memberships', 'paid-memberships-pro' ) }</span>
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
