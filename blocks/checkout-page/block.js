/**
 * Block: PMPro Membership Checkout
 *
 * Displays the Membership Checkout form.
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
const {
    SelectControl,
} = wp.components;

 /**
  * Register block
  */
 export default registerBlockType(
     'pmpro/checkout-page',
     {
         title: __( 'Membership Checkout Form', 'paid-memberships-pro' ),
         description: __( 'Dynamic form that allows users to complete free registration or paid checkout for the selected membership level.', 'paid-memberships-pro' ),
         category: 'pmpro',
         icon: {
            background: '#FFFFFF',
            foreground: '#658B24',
            src: 'list-view',
         },
         keywords: [
             __( 'member', 'paid-memberships-pro' ),
             __( 'paid memberships pro', 'paid-memberships-pro' ),
             __( 'pmpro', 'paid-memberships-pro' ),
             __( 'buy', 'paid-memberships-pro' ),
             __( 'purchase', 'paid-memberships-pro' ),
             __( 'sell', 'paid-memberships-pro' ),
         ],
         supports: {
         },
         attributes: {
             pmpro_default_level: {
                 type: 'string',
                 source: 'meta',
                 meta: 'pmpro_default_level',
             },
         },
         edit: props => {
             const { attributes: { pmpro_default_level }, className, setAttributes, isSelected } = props;
             return [
                isSelected && <Inspector { ...{ setAttributes, ...props} } />,
                <div className="pmpro-block-element">
                  <span className="pmpro-block-title">{ __( 'Paid Memberships Pro', 'paid-memberships-pro' ) }</span>
                  <span className="pmpro-block-subtitle">{ __( 'Membership Checkout Form', 'paid-memberships-pro' ) }</span>
                  <hr />
                  <SelectControl
                      label={ __( 'Membership Level', 'paid-memberships-pro' ) }
                      value={ pmpro_default_level }
                      onChange={ pmpro_default_level => setAttributes( { pmpro_default_level } ) }
                      options={ window.pmpro.all_level_values_and_labels }
                  />
                </div>
            ];
         },
         save() {
           return null;
         },
       }
 );
