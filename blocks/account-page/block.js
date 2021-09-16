/**
 * Block: PMPro Membership Account
 *
 * Displays the Membership Account page.
 *
 */

import blockJSON from './block.json';

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
     blockJSON,
     {
         title: __( 'Membership Account Page', 'paid-memberships-pro' ),
         description: __( 'Displays the sections of the Membership Account page as selected below.', 'paid-memberships-pro' ),
         icon: {
            background: '#2997c8',
            foreground: '#ffffff',
            src: 'admin-users',
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
