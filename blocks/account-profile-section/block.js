/**
 * Block: PMPro Checkout Button
 *
 * Add a styled link to the PMPro checkout page for a
 * specific level.
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
     'pmpro/account-profile-section',
     {
         title: __( 'PMPro Page: Account Profile View', 'paid-memberships-pro' ),
         description: __( 'Dynamic page section that displays the member\'s profile as read-only information with a link to edit fields or change their password.', 'paid-memberships-pro' ),
         category: 'pmpro-pages',
         icon: {
            background: '#FFFFFF',
            foreground: '#1A688B',
            src: 'admin-users',
         },
         keywords: [
             __( 'fields', 'paid-memberships-pro' ),
             __( 'member', 'paid-memberships-pro' ),
             __( 'paid memberships pro', 'paid-memberships-pro' ),
             __( 'pmpro', 'paid-memberships-pro' ),
             __( 'user', 'paid-memberships-pro' ),
         ],
         supports: {
         },
         attributes: {
          title : {
            type: 'string',
            default: __( 'My Account', 'paid-memberships-pro' ),
          }
         },
         edit({ attributes, setAttributes }) {
          const updateTitle = ( event ) => {
           setAttributes( { title: event.target.value } );
          };
             return [
                 <div className="pmpro-block-element">
                 <span className="pmpro-block-title">{__('Paid Memberships Pro', 'paid-memberships-pro')}</span>
                 <span className="pmpro-block-subtitle">{__('Membership Account: Profile', 'paid-memberships-pro')}</span>
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
           return null
         },
       }
 );
