/**
 * Block: Single Level Checkout Button
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
    useBlockProps, 
} = wp.blockEditor;

 /**
  * Register block
  */
export default registerBlockType(
    'pmpro/single-level-checkout',
    {
        title: __( 'Level Checkout Button', 'paid-memberships-pro' ),
        description: __( 'Nest blocks within this wrapper to control the inner block visibility by membership level or for non-members only.', 'paid-memberships-pro' ),
        category: 'pmpro',
        icon: {
            background: '#FFFFFF',
            foreground: '#1A688B',
            src: 'visibility',
        },
        parent: ['pmpro/single-level'],
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
                default: []
            },
            selected_level: {
                type: 'string',
                default:''
            },
        },
        edit: props => {
            return ( 
                <div { ...useBlockProps() }>
                    { 'Checkout Button?'}
                </div>
            );
        },
    }
);
