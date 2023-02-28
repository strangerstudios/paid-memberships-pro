/**
 * Block: PMPro Membership Account
 *
 * Displays the Membership Account page.
 *
 */
 /**
  * Block dependencies
  */
 import Inspector from './inspector';
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
     'pmpro/account-page',
     {
         title: __( 'PMPro Page: Account (Full)', 'paid-memberships-pro' ),
         description: __( 'Dynamic page section to display the selected sections of the Membership Account page including Memberships, Profile, Invoices, and Member Links. These sections can also be added via separate blocks.', 'paid-memberships-pro' ),
         category: 'pmpro-pages',
         icon: {
            background: '#FFFFFF',
            foreground: '#1A688B',
            src: 'admin-users',
         },
         keywords: [
             __( 'account', 'paid-memberships-pro' ),
             __( 'billing', 'paid-memberships-pro' ),
             __( 'invoice', 'paid-memberships-pro' ),
             __( 'links', 'paid-memberships-pro' ),
             __( 'member', 'paid-memberships-pro' ),
             __( 'order', 'paid-memberships-pro' ),
             __( 'paid memberships pro', 'paid-memberships-pro' ),
             __( 'pmpro', 'paid-memberships-pro' ),
             __( 'profile', 'paid-memberships-pro' ),
             __( 'purchases', 'paid-memberships-pro' ),
             __( 'quick link', 'paid-memberships-pro' ),
             __( 'receipt', 'paid-memberships-pro' ),
             __( 'user', 'paid-memberships-pro' ),
         ],
         supports: {
         },
         attributes: {
             membership: {
                 type: 'boolean',
                 default: false,
             },
             profile: {
                 type: 'boolean',
                 default: false,
             },
             invoices: {
                 type: 'boolean',
                 default: false,
             },
             links: {
                 type: 'boolean',
                 default: false,
             },
         },
         edit: props => {
             const { setAttributes, isSelected } = props;
             return [
                isSelected && <Inspector { ...{ setAttributes, ...props} } />,
                <div className="pmpro-block-element">
                  <span className="pmpro-block-title">{ __( 'Paid Memberships Pro', 'paid-memberships-pro' ) }</span>
                  <span className="pmpro-block-subtitle">{ __( 'Membership Account Page', 'paid-memberships-pro' ) }</span>
                </div>
            ];
         },
         save() {
           return null;
         },
       }
 );
