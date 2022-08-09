/**
 * Block: PMPro Single Membership
 *
 *
 */

 /**
  * Internal block libraries
  */
 const { __ } = wp.i18n;
 const {
    registerBlockType
} = wp.blocks;
const {
    PanelBody,
    CheckboxControl,
    SelectControl,
} = wp.components;
const {
    InspectorControls,
    InnerBlocks,
    useBlockProps, 
} = wp.blockEditor;

const all_levels = [{ value: 0, label: "Non-Members" }].concat( pmpro.all_level_values_and_labels );

 /**
  * Register block
  */
 export default registerBlockType(
     'pmpro/single-level',
     {
         title: __( 'Single Membership Level', 'paid-memberships-pro' ),
         description: __( 'Nest blocks within this wrapper to control the inner block visibility by membership level or for non-members only.', 'paid-memberships-pro' ),
         category: 'pmpro',
         icon: {
            background: '#FFFFFF',
            foreground: '#1A688B',
            src: 'visibility',
         },
         keywords: [
            __( 'block visibility', 'paid-memberships-pro' ),
            __( 'conditional', 'paid-memberships-pro' ),
            __( 'content', 'paid-memberships-pro' ),
            __( 'hide', 'paid-memberships-pro' ),
            __( 'hidden', 'paid-memberships-pro' ),
            __( 'paid memberships pro', 'paid-memberships-pro' ),
            __( 'pmpro', 'paid-memberships-pro' ),
            __( 'private', 'paid-memberships-pro' ),
            __( 'restrict', 'paid-memberships-pro' ),
         ],
         attributes: {
             levels: {
                 type: 'array',
                 default:[]
             },
             uid: {
                 type: 'string',
                 default:'',
             },
             show_noaccess: {
                 type: 'boolean',
                 default: false,
             },
         },
         // parent: ['pmpro/checkout-button'],

         edit: props => {
              return (
        <div { ...useBlockProps() }>
            <InnerBlocks
                template={ [
                    [ 'pmpro/single-level-name', { level: 2, content: 'Example Nested Block Template' } ],
                    [ 'pmpro/single-level-price', { level: 2, content: 'Example Nested Block Template' } ],
                    [ 'pmpro/single-level-expiration', { level: 2, content: 'Example Nested Block Template' } ],
                    [ 'pmpro/single-level-description', { level: 2, content: 'Example Nested Block Template' } ],
                    [ 'pmpro/single-level-checkout', { level: 2, content: 'Example Nested Block Template' } ],
                ] }

            />
        </div>
    );
         },
         save: props => {
           const {  className } = props;
        		 return (
                        <div { ...useBlockProps.save() }>
                            <InnerBlocks.Content />
                        </div>
                    );
        	},
       }
 );
